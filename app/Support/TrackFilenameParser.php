<?php

namespace App\Support;

use Carbon\CarbonImmutable;

class TrackFilenameParser
{
    /**
     * @return array{track_number: int|null, title: string|null, recorded_at: string|null, notes: string|null}
     */
    public static function parse(string $filename): array
    {
        $text = pathinfo($filename, PATHINFO_FILENAME);
        $text = self::normalizeBaseText($text);

        $date = self::extractDate($text);
        $text = self::cleanWorkingText($date['text']);

        $context = null;

        if (str_contains($text, ' | ')) {
            [$text, $context] = array_pad(explode(' | ', $text, 2), 2, null);
            $text = self::cleanWorkingText($text);
            $context = self::humanize($context);
        }

        $set = self::extractSetNumber($text);
        $text = self::cleanWorkingText($set['text']);

        if (! $context && str_contains($text, ' - ')) {
            $parts = array_map('trim', explode(' - ', $text, 2));

            if ($set['number'] && preg_match('/^\s*\d{1,3}\b/', $parts[1] ?? '')) {
                $context = self::humanize($parts[0] ?? '');
                $text = $parts[1] ?? $text;
            } else {
                $text = $parts[0] ?? $text;
                $context = self::humanize($parts[1] ?? '');
            }
        }

        $track = self::extractTrackNumber($text, $set['number']);
        $text = self::cleanWorkingText($track['text']);

        $mixNotes = self::extractMixNotes($text);
        $text = self::cleanWorkingText($mixNotes['text']);

        $timeNote = self::extractLeadingTime($text);
        $text = self::cleanWorkingText($timeNote['text']);

        $notes = [];

        if ($track['set'] && $track['track_in_set']) {
            $notes[] = "Set {$track['set']}, track {$track['track_in_set']}";
        }

        if ($context) {
            $notes[] = $context;
        }

        if ($timeNote['note']) {
            $notes[] = $timeNote['note'];
        }

        if ($mixNotes['note']) {
            $notes[] = $mixNotes['note'];
        }

        return [
            'track_number' => $track['number'],
            'title' => self::humanize($text) ?: null,
            'recorded_at' => $date['date'],
            'notes' => $notes ? implode("\n", array_unique($notes)) : null,
        ];
    }

    protected static function normalizeBaseText(string $text): string
    {
        $text = preg_replace('/[-_]{3,}/', ' | ', $text) ?? $text;
        $text = str_replace('_', ' ', $text);
        $text = preg_replace('/\s+/', ' ', $text) ?? $text;

        return trim($text);
    }

    protected static function cleanWorkingText(string $text): string
    {
        $text = trim($text);
        $text = trim($text, " \t\n\r\0\x0B-");

        if (! str_contains($text, ' - ')) {
            $text = str_replace('-', ' ', $text);
        }

        $text = preg_replace('/\s+/', ' ', $text) ?? $text;

        return trim($text);
    }

    /**
     * @return array{date: string|null, text: string}
     */
    protected static function extractDate(string $text): array
    {
        $patterns = [
            '/\b((?:19|20)\d{2})[-_. ]?(\d{2})[-_. ]?(\d{2})\b/',
            '/\b((?:jan|january|feb|february|mar|march|apr|april|may|jun|june|jul|july|aug|august|sep|sept|september|oct|october|nov|november|dec|december)\.?)[ ]+(\d{1,2})(?:st|nd|rd|th)?[,]?[ ]+(\d{2,4})\b/i',
        ];

        foreach ($patterns as $pattern) {
            if (! preg_match($pattern, $text, $matches, PREG_OFFSET_CAPTURE)) {
                continue;
            }

            $date = str_contains($pattern, 'jan|january')
                ? self::parseNamedMonthDate($matches)
                : self::parseNumericDate($matches);

            if (! $date) {
                continue;
            }

            $matchedText = $matches[0][0];
            $text = trim(str_replace($matchedText, ' ', $text));

            return [
                'date' => $date,
                'text' => preg_replace('/\s+/', ' ', $text) ?? $text,
            ];
        }

        return ['date' => null, 'text' => $text];
    }

    protected static function parseNumericDate(array $matches): ?string
    {
        return self::validDate((int) $matches[1][0], (int) $matches[2][0], (int) $matches[3][0]);
    }

    protected static function parseNamedMonthDate(array $matches): ?string
    {
        $year = (int) $matches[3][0];

        if ($year < 100) {
            $year += $year >= 70 ? 1900 : 2000;
        }

        try {
            $month = CarbonImmutable::parse($matches[1][0])->month;
        } catch (\Throwable) {
            return null;
        }

        return self::validDate($year, $month, (int) $matches[2][0]);
    }

    protected static function validDate(int $year, int $month, int $day): ?string
    {
        if (! checkdate($month, $day, $year)) {
            return null;
        }

        return CarbonImmutable::create($year, $month, $day)->toDateString();
    }

    /**
     * @return array{number: int|null, text: string}
     */
    protected static function extractSetNumber(string $text): array
    {
        if (! preg_match('/\bset[ -]*(\d+|one|two|three|four|five|six|seven|eight|nine)\b/i', $text, $matches)) {
            return ['number' => null, 'text' => $text];
        }

        $number = self::numberWordToInt($matches[1]) ?? (int) $matches[1];
        $text = trim(str_replace($matches[0], ' ', $text));

        return [
            'number' => $number > 0 ? $number : null,
            'text' => preg_replace('/\s+/', ' ', $text) ?? $text,
        ];
    }

    protected static function numberWordToInt(string $value): ?int
    {
        return [
            'one' => 1,
            'two' => 2,
            'three' => 3,
            'four' => 4,
            'five' => 5,
            'six' => 6,
            'seven' => 7,
            'eight' => 8,
            'nine' => 9,
        ][strtolower($value)] ?? null;
    }

    /**
     * @return array{number: int|null, set: int|null, track_in_set: int|null, text: string}
     */
    protected static function extractTrackNumber(string $text, ?int $setNumber): array
    {
        $number = null;
        $trackInSet = null;

        if (preg_match('/^\s*(\d{1,2})[.-](\d{1,2})\b/', $text, $matches)) {
            $setNumber = (int) $matches[1];
            $trackInSet = (int) $matches[2];
            $number = ($setNumber * 100) + $trackInSet;
            $text = trim(substr($text, strlen($matches[0])));
        } elseif ($setNumber && preg_match('/^\s*(?:track\s*)?(\d{1,2})\b/i', $text, $matches)) {
            $trackInSet = (int) $matches[1];
            $number = ($setNumber * 100) + $trackInSet;
            $text = trim(substr($text, strlen($matches[0])));
        } elseif (preg_match('/^\s*track\s*(\d{1,2})\b/i', $text, $matches)) {
            $trackInSet = (int) $matches[1];
            $number = $trackInSet;
            $text = trim(substr($text, strlen($matches[0])));
        } elseif (preg_match('/^\s*(\d{3})\b/', $text, $matches)) {
            $number = (int) $matches[1];
            $setNumber = intdiv($number, 100);
            $trackInSet = $number % 100;
            $text = trim(substr($text, strlen($matches[0])));
        } elseif (preg_match('/^\s*(\d{1,2})\b/', $text, $matches)) {
            $number = (int) $matches[1];
            $trackInSet = $number;
            $text = trim(substr($text, strlen($matches[0])));
        }

        return [
            'number' => $number,
            'set' => $setNumber,
            'track_in_set' => $trackInSet,
            'text' => $text,
        ];
    }

    /**
     * @return array{note: string|null, text: string}
     */
    protected static function extractMixNotes(string $text): array
    {
        if (! preg_match('/\b((?:mix|take|version|v)\s*\d+|rough|master|final)\b$/i', $text, $matches)) {
            return ['note' => null, 'text' => $text];
        }

        $text = trim(substr($text, 0, -strlen($matches[0])));

        return [
            'note' => self::humanize($matches[0]),
            'text' => $text,
        ];
    }

    /**
     * @return array{note: string|null, text: string}
     */
    protected static function extractLeadingTime(string $text): array
    {
        if (! preg_match('/^\s*([01]\d|2[0-3])([0-5]\d)\b/', $text, $matches)) {
            return ['note' => null, 'text' => $text];
        }

        $text = trim(substr($text, strlen($matches[0])));

        return [
            'note' => "Time: {$matches[1]}:{$matches[2]}",
            'text' => $text,
        ];
    }

    protected static function humanize(?string $text): string
    {
        $text = trim((string) $text);
        $text = str_replace(['_', '-'], ' ', $text);
        $text = preg_replace('/\s+/', ' ', $text) ?? $text;

        return trim($text);
    }
}
