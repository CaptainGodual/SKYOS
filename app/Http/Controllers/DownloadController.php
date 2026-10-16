<?php

namespace App\Http\Controllers;

use App\Models\Download;
use Illuminate\Support\Facades\Storage;

class DownloadController extends Controller
{
    /**
     * Securely serve a download file to authenticated, approved pilots.
     */
    public function download(Download $download)
    {
        $user = auth()->user();

        if (! $user || ! $user->isApproved()) {
            abort(403, 'You must be an approved pilot to download files.');
        }

        $path = 'downloads/'.$download->file_path;

        if (! Storage::disk('local')->exists($path)) {
            abort(404, 'File not found.');
        }

        return Storage::disk('local')->download($path, $download->name);
    }
}
