<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDownloadRequest;
use App\Http\Requests\UpdateDownloadRequest;
use App\Models\Download;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class DownloadController extends Controller
{
    public function index(): View
    {
        $downloads = Download::query()->latest()->paginate(15);

        return view('admin.downloads.index', ['downloads' => $downloads]);
    }

    public function create(): View
    {
        return view('admin.downloads.create');
    }

    public function store(StoreDownloadRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            // Sanitize filename to prevent path traversal attacks
            $originalName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());
            $filename = uniqid('dl_', true).'_'.$originalName;
            $file->storeAs('downloads', $filename, 'local');

            $validated['file_path'] = $filename;
            $validated['size_bytes'] = $file->getSize();
        }

        Download::query()->create($validated);

        return redirect()
            ->route('admin.downloads.index')
            ->with('success', 'Download added to the crew center.');
    }

    public function edit(Download $download): View
    {
        return view('admin.downloads.edit', ['download' => $download]);
    }

    public function update(UpdateDownloadRequest $request, Download $download): RedirectResponse
    {
        $validated = $request->validated();

        if ($request->hasFile('file')) {
            // Remove old file
            if ($download->file_path && Storage::disk('local')->exists('downloads/'.$download->file_path)) {
                Storage::disk('local')->delete('downloads/'.$download->file_path);
            }

            $file = $request->file('file');
            // Sanitize filename to prevent path traversal attacks
            $originalName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());
            $filename = uniqid('dl_', true).'_'.$originalName;
            $file->storeAs('downloads', $filename, 'local');

            $validated['file_path'] = $filename;
            $validated['size_bytes'] = $file->getSize();
        }

        $download->update($validated);

        return redirect()
            ->route('admin.downloads.index')
            ->with('success', 'Download updated.');
    }

    public function destroy(Download $download): RedirectResponse
    {
        if ($download->file_path && Storage::disk('local')->exists('downloads/'.$download->file_path)) {
            Storage::disk('local')->delete('downloads/'.$download->file_path);
        }

        $download->delete();

        return redirect()
            ->route('admin.downloads.index')
            ->with('success', 'Download removed.');
    }
}
