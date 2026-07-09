<?php

namespace App\Support;

class TrackBatchUploadParser
{
    /**
     * @param  array<int, string>  $filenames
     * @return array{
     *     title: string|null,
     *     recorded_at: string|null,
     *     tracks: array<int, array{
     *         source_filename: string,
     *         track_number: int|null,
     *         title: string|null,
     *         recorded_at: string|null,
     *         notes: string|null
     *     }>
     * }
     */
    public static function parse(array $filenames): array
    {
        $tracks = array_map(function (string $filename): array {
            return [
                'source_filename' => $filename,
                ...TrackFilenameParser::parse($filename),
            ];
        }, array_values($filenames));

        $commonContext = self::mostCommonFilledValue(
            array_map(fn (array $track): ?string => self::contextFromNotes($track['notes']), $tracks)
        );

        $commonDate = self::mostCommonFilledValue(
            array_column($tracks, 'recorded_at')
        );

        if ($commonContext) {
            $tracks = array_map(function (array $track) use ($commonContext): array {
                $track['notes'] = self::removeNoteLine($track['notes'], $commonContext);

                return $track;
            }, $tracks);
        }

        usort($tracks, function (array $a, array $b): int {
            if ($a['track_number'] && $b['track_number']) {
                return $a['track_number'] <=> $b['track_number'];
            }

            if ($a['track_number']) {
                return -1;
            }

            if ($b['track_number']) {
                return 1;
            }

            return strnatcasecmp($a['source_filename'], $b['source_filename']);
        });

        return [
            'title' => $commonContext,
            'recorded_at' => $commonDate,
            'tracks' => $tracks,
        ];
    }

    protected static function mostCommonFilledValue(array $values): ?string
    {
        $values = array_values(array_filter($values, fn (?string $value): bool => filled($value)));

        if ($values === []) {
            return null;
        }

        $counts = array_count_values($values);
        arsort($counts);

        $value = array_key_first($counts);

        return is_string($value) ? $value : null;
    }

    protected static function contextFromNotes(?string $notes): ?string
    {
        foreach (explode("\n", (string) $notes) as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            if (preg_match('/^(set \d+, track \d+|time:|mix\s*\d+|take\s*\d+|v\s*\d+|rough|master|final)/i', $line)) {
                continue;
            }

            return $line;
        }

        return null;
    }

    protected static function removeNoteLine(?string $notes, string $lineToRemove): ?string
    {
        $lines = array_filter(
            array_map('trim', explode("\n", (string) $notes)),
            fn (string $line): bool => $line !== '' && $line !== $lineToRemove,
        );

        return $lines ? implode("\n", $lines) : null;
    }
}
