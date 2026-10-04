<?php

namespace App\Support;

use Illuminate\Filesystem\FilesystemAdapter;
use League\Flysystem\Local\LocalFilesystemAdapter;
use RuntimeException;

class LocalAudioFile
{
    public static function withPath(FilesystemAdapter $disk, string $path, callable $callback): mixed
    {
        if ($disk->getAdapter() instanceof LocalFilesystemAdapter) {
            $localPath = $disk->path($path);
            if (! is_file($localPath)) {
                throw new RuntimeException('Audio file does not exist: '.$path);
            }

            return $callback($localPath);
        }

        $temporaryPath = tempnam(sys_get_temp_dir(), 'relay_audio_');
        if ($temporaryPath === false) {
            throw new RuntimeException('Unable to create temporary audio file.');
        }

        $source = null;
        $destination = null;
        try {
            $source = $disk->readStream($path);
            $destination = fopen($temporaryPath, 'wb');
            if (! is_resource($source) || ! is_resource($destination)) {
                throw new RuntimeException('Unable to open audio stream: '.$path);
            }
            if (stream_copy_to_stream($source, $destination) === false) {
                throw new RuntimeException('Unable to copy audio stream: '.$path);
            }
            fclose($destination);
            $destination = null;

            return $callback($temporaryPath);
        } finally {
            if (is_resource($source)) {
                fclose($source);
            }
            if (is_resource($destination)) {
                fclose($destination);
            }
            unlink($temporaryPath);
        }
    }
}
