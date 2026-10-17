<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PilotRosterController extends Controller
{
    /**
     * The crew roster: every pilot in good standing, with their flying record.
     *
     * This is the one page that lists the whole crew, so it is also the reason
     * names are shortened to a first name and a surname initial: nothing here
     * should let a visitor build up a list of the crew's full names.
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->string('search'));

        $pilots = User::query()
            ->approved()
            ->with('homeHub')
            ->when($search !== '', function ($query) use ($search) {
                $pattern = $this->likePattern($search);

                $query->where(function ($query) use ($pattern) {
                    $query->whereRaw("name LIKE ? ESCAPE '!'", [$pattern])
                        ->orWhereHas('homeHub', fn ($query) => $query->whereRaw("identifier LIKE ? ESCAPE '!'", [$pattern]));
                });
            })
            ->withCount('pireps')
            ->withSum('pireps', 'block_minutes')
            ->orderBy('name')
            ->paginate(24)
            ->withQueryString();

        return view('crew-center.pilots.index', [
            'user' => $request->user(),
            'pilots' => $pilots,
            'search' => $search,
        ]);
    }

    /**
     * Build a LIKE pattern that matches the pilot's text literally.
     *
     * `%` and `_` are wildcards, so searching for "K_" would otherwise list
     * every pilot whose hub starts with K. The escape character is spelled out
     * in the query too, because the default one is not the same on every
     * engine.
     */
    private function likePattern(string $value): string
    {
        $escaped = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $value);

        return '%'.$escaped.'%';
    }
}
