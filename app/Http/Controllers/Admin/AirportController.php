<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Airport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AirportController extends Controller
{
    /**
     * Display a paginated listing of imported airports.
     */
    public function index(Request $request): View
    {
        $query = Airport::query();

        if ($request->filled('search')) {
            $pattern = $this->likePattern((string) $request->string('search'));

            $query->where(function ($q) use ($pattern): void {
                $q->whereRaw("identifier LIKE ? ESCAPE '!'", [$pattern])
                    ->orWhereRaw("name LIKE ? ESCAPE '!'", [$pattern])
                    ->orWhereRaw("iata_code LIKE ? ESCAPE '!'", [$pattern])
                    ->orWhereRaw("icao_code LIKE ? ESCAPE '!'", [$pattern])
                    ->orWhereRaw("municipality LIKE ? ESCAPE '!'", [$pattern]);
            });
        }

        if ($request->filled('country')) {
            $query->where('country_code', $request->string('country'));
        }

        $airports = $query->orderBy('identifier')
            ->paginate(25)
            ->withQueryString();

        return view('admin.airports.index', [
            'airports' => $airports,
            'search' => $request->string('search'),
            'country' => $request->string('country'),
        ]);
    }

    /**
     * Show the airport CSV import form.
     */
    public function import(): View
    {
        return view('admin.airports.import');
    }

    /**
     * Import airports from an uploaded CSV file.
     *
     * Expected columns: identifier, iata_code, icao_code, name, type,
     * latitude, longitude, elevation, country_code, region_code,
     * municipality, time_zone
     */
    public function performImport(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:20480'],
        ]);

        $path = $data['file']->getRealPath();
        $handle = fopen($path, 'r');

        if ($handle === false) {
            return back()
                ->withInput()
                ->with('error', 'Unable to read the uploaded file.');
        }

        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);

            return back()
                ->withInput()
                ->with('error', 'The CSV file appears to be empty.');
        }

        $columns = [
            'identifier',
            'iata_code',
            'icao_code',
            'name',
            'type',
            'latitude',
            'longitude',
            'elevation',
            'country_code',
            'region_code',
            'municipality',
            'time_zone',
        ];

        $inserted = 0;
        $processed = 0;
        $skipped = 0;
        $chunk = [];

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) === 0 || (count($row) === 1 && $row[0] === '')) {
                continue;
            }

            // array_combine() throws a ValueError (PHP 8) when a row has a
            // different number of columns than the header list, so a single
            // malformed line must be filtered out here.
            if (count($row) !== count($columns)) {
                $skipped++;

                continue;
            }

            $record = array_combine($columns, $row);

            if ($record === false || blank($record['identifier'])) {
                $skipped++;

                continue;
            }

            $record['latitude'] = blank($record['latitude']) ? null : (float) $record['latitude'];
            $record['longitude'] = blank($record['longitude']) ? null : (float) $record['longitude'];
            $record['elevation'] = blank($record['elevation']) ? null : (int) $record['elevation'];

            // MySQL enforces column lengths in strict mode, whereas SQLite silently
            // accepts overlong values. Trim to the schema limits so a single bad row
            // cannot abort an entire import chunk on production.
            $record['identifier'] = mb_substr((string) $record['identifier'], 0, 255);
            $record['name'] = mb_substr((string) $record['name'], 0, 255);
            $record['type'] = mb_substr((string) $record['type'], 0, 255);
            $record['iata_code'] = blank($record['iata_code']) ? null : mb_substr((string) $record['iata_code'], 0, 255);
            $record['icao_code'] = blank($record['icao_code']) ? null : mb_substr((string) $record['icao_code'], 0, 255);
            $record['country_code'] = blank($record['country_code']) ? null : mb_substr((string) $record['country_code'], 0, 2);
            $record['region_code'] = blank($record['region_code']) ? null : mb_substr((string) $record['region_code'], 0, 10);
            $record['municipality'] = blank($record['municipality']) ? null : mb_substr((string) $record['municipality'], 0, 255);
            $record['time_zone'] = blank($record['time_zone']) ? null : mb_substr((string) $record['time_zone'], 0, 255);

            $chunk[] = $record;
            $processed++;

            if (count($chunk) >= 1000) {
                Airport::query()->upsert($chunk, ['identifier'], $columns);
                $chunk = [];
            }
        }

        if (count($chunk) > 0) {
            Airport::query()->upsert($chunk, ['identifier'], $columns);
        }

        fclose($handle);

        $total = Airport::query()->count();

        $message = "Processed {$processed} airports. Total airports in database: {$total}.";

        if ($skipped > 0) {
            $message .= " Skipped {$skipped} row(s) that did not match the expected ".count($columns).' columns.';
        }

        return redirect()
            ->route('admin.airports.index')
            ->with('success', $message);
    }

    /**
     * Toggle whether an airport is marked as a crew hub.
     */
    public function toggleHub(Airport $airport): RedirectResponse
    {
        $airport->update(['is_hub' => ! $airport->is_hub]);

        return back()
            ->with('success', "{$airport->identifier} is now ".($airport->is_hub ? 'a crew hub' : 'no longer a crew hub').'.');
    }

    /**
     * Build a LIKE pattern that matches the operator's text literally.
     *
     * `%` and `_` are wildcards, so searching for "K_" would otherwise list
     * every airport starting with K. The escape character is spelled out in the
     * query too, because the default one is not the same on every engine.
     */
    private function likePattern(string $value): string
    {
        $escaped = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $value);

        return '%'.$escaped.'%';
    }

    /**
     * Search airports for Select2 autocomplete.
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $pattern = $this->likePattern((string) $request->string('query', ''));

            $airports = Airport::query()
                ->where(function ($q) use ($pattern): void {
                    $q->whereRaw("identifier LIKE ? ESCAPE '!'", [$pattern])
                        ->orWhereRaw("name LIKE ? ESCAPE '!'", [$pattern])
                        ->orWhereRaw("municipality LIKE ? ESCAPE '!'", [$pattern]);
                })
                ->orderBy('identifier')
                ->limit(20)
                ->get()
                ->map(fn ($airport) => [
                    'id' => $airport->id,
                    'identifier' => $airport->identifier,
                    'text' => $airport->identifier.' — '.$airport->name,
                ]);

            return response()->json(['results' => $airports->values()]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'error' => 'Unable to search airports right now.',
            ], 500);
        }
    }
}
