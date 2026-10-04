<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

class ProjectDownloadController extends Controller
{
    public function __invoke(Request $request, Project $project)
    {
        if (! $project->isVisibleTo($request)) {
            return response()->view('audio.project-restricted', [
                'visibility' => $project->visibility,
            ], 403);
        }

        $tracks = $project->tracks()->whereNotNull('storage_path')
            ->where('storage_path', '!=', '')
            ->orderBy('track_number')->orderBy('id')->get();

        abort_if($tracks->isEmpty(), 404, 'No tracks are available to download.');
        abort_unless(class_exists(ZipArchive::class), 503, 'ZIP downloads are temporarily unavailable.');

        $disk = Storage::disk(config('filesystems.default'));
        $temporaryFiles = [];
        $zip = new ZipArchive;
        $zipOpen = false;

        try {
            $archive = tempnam(sys_get_temp_dir(), 'relay_zip_');
            if ($archive === false) {
                throw new RuntimeException('Unable to create temporary ZIP file.');
            }
            $temporaryFiles[] = $archive;
            if ($zip->open($archive, ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('Unable to open ZIP archive.');
            }
            $zipOpen = true;

            foreach ($tracks as $index => $track) {
                $source = $disk->readStream($track->storage_path);
                if (! is_resource($source)) {
                    throw new RuntimeException('Unable to read track '.$track->id);
                }

                try {
                    $path = tempnam(sys_get_temp_dir(), 'relay_track_');
                    if ($path === false) {
                        throw new RuntimeException('Unable to create temporary audio file.');
                    }
                    $temporaryFiles[] = $path;
                    $destination = fopen($path, 'wb');
                    if ($destination === false) {
                        throw new RuntimeException('Unable to open temporary audio file.');
                    }
                    try {
                        if (stream_copy_to_stream($source, $destination) === false) {
                            throw new RuntimeException('Unable to copy track '.$track->id);
                        }
                    } finally {
                        fclose($destination);
                    }
                } finally {
                    fclose($source);
                }

                $extension = preg_replace('/[^a-z0-9]/i', '', pathinfo($track->storage_path, PATHINFO_EXTENSION));
                $name = sprintf('%02d-%s', $index + 1, Str::limit(Str::slug($track->title) ?: 'track', 150, ''));
                $name .= $extension ? '.'.$extension : '';

                if (! $zip->addFile($path, $name) || ! $zip->setCompressionName($name, ZipArchive::CM_STORE)) {
                    throw new RuntimeException('Unable to add track to ZIP archive.');
                }
            }

            if (! $zip->close()) {
                throw new RuntimeException('Unable to finish ZIP archive.');
            }
            $zipOpen = false;

            // The response owns the completed archive; source files can be removed now.
            $response = response()->download($archive, (Str::slug($project->title) ?: 'project').'.zip', [
                'Content-Type' => 'application/zip',
                'Cache-Control' => 'private, no-store',
            ])->deleteFileAfterSend(true);
            array_shift($temporaryFiles);

            return $response;
        } finally {
            if ($zipOpen) {
                $zip->close();
            }
            foreach ($temporaryFiles as $path) {
                if (is_file($path)) {
                    unlink($path);
                }
            }
        }
    }
}
