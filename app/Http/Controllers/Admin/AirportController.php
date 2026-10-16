<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Airport;
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
            $search = $request->string('search');
            $query->where(function ($q) use ($search): void {
                $q->where('identifier', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('iata_code', 'like', "%{$search}%")
                    ->orWhere('icao_code', 'like', "%{$search}%")
                    ->orWhere('municipality', 'like', "%{$search}%");
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
        $chunk = [];

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) === 0 || (count($row) === 1 && $row[0] === '')) {
                continue;
            }

            $record = array_combine($columns, $row);

            if ($record === false || blank($record['identifier'])) {
                continue;
            }

            $record['latitude'] = blank($record['latitude']) ? null : (float) $record['latitude'];
            $record['longitude'] = blank($record['longitude']) ? null : (float) $record['longitude'];
            $record['elevation'] = blank($record['elevation']) ? null : (int) $record['elevation'];
            $record['iata_code'] = blank($record['iata_code']) ? null : $record['iata_code'];
            $record['icao_code'] = blank($record['icao_code']) ? null : $record['icao_code'];
            $record['country_code'] = blank($record['country_code']) ? null : $record['country_code'];
            $record['region_code'] = blank($record['region_code']) ? null : $record['region_code'];
            $record['municipality'] = blank($record['municipality']) ? null : $record['municipality'];
            $record['time_zone'] = blank($record['time_zone']) ? null : $record['time_zone'];

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

        return redirect()
            ->route('admin.airports.index')
            ->with('success', "Processed {$processed} airports. Total airports in database: {$total}.");
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
}
