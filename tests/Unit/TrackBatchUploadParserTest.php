<?php

namespace Tests\Unit;

use App\Support\TrackBatchUploadParser;
use PHPUnit\Framework\TestCase;

class TrackBatchUploadParserTest extends TestCase
{
    public function test_it_promotes_common_context_and_date_for_ordered_tracks(): void
    {
        $parsed = TrackBatchUploadParser::parse([
            '02 St Stephen - 20250821 OD Caledonia.mp3',
            '01 Bertha - 20250821 OD Caledonia.mp3',
            '10 ChinaCat Rider Eclipse - 20250821 OD Caledonia.mp3',
            '08 Doin That Rag - 20250821 OD Caledonia.mp3',
        ]);

        $this->assertSame('OD Caledonia', $parsed['title']);
        $this->assertSame('2025-08-21', $parsed['recorded_at']);
        $this->assertCount(4, $parsed['tracks']);
        $this->assertSame(1, $parsed['tracks'][0]['track_number']);
        $this->assertSame('Bertha', $parsed['tracks'][0]['title']);
        $this->assertNull($parsed['tracks'][0]['notes']);
        $this->assertSame(2, $parsed['tracks'][1]['track_number']);
        $this->assertSame(8, $parsed['tracks'][2]['track_number']);
        $this->assertSame('Doin That Rag', $parsed['tracks'][2]['title']);
    }

    public function test_it_keeps_set_notes_when_context_is_promoted(): void
    {
        $parsed = TrackBatchUploadParser::parse([
            '101 Into the Mystic - 20260703 Fairhaven Wedding.mp3',
            '109 For What It\'s Worth - 20260703 Fairhaven Wedding.mp3',
            '201 Ain\'t No Sunshine - 20260703 Fairhaven Wedding.mp3',
            '204 Son of a Preacher Man - 20260703 Fairhaven Wedding.mp3',
        ]);

        $this->assertSame('Fairhaven Wedding', $parsed['title']);
        $this->assertSame('2026-07-03', $parsed['recorded_at']);
        $this->assertSame(101, $parsed['tracks'][0]['track_number']);
        $this->assertSame('Set 1, track 1', $parsed['tracks'][0]['notes']);
        $this->assertSame(204, $parsed['tracks'][3]['track_number']);
        $this->assertSame('Son of a Preacher Man', $parsed['tracks'][3]['title']);
        $this->assertSame('Set 2, track 4', $parsed['tracks'][3]['notes']);
    }

    public function test_it_handles_open_mic_context_after_title(): void
    {
        $parsed = TrackBatchUploadParser::parse([
            '01 Dave and Bob - Open Mic 20251030.mp3',
            '02 Chad - Open Mic 20251030.mp3',
            '10 Chris F - Open Mic 20251030.mp3',
            '16 Ace - Open Mic 20251030.mp3',
        ]);

        $this->assertSame('Open Mic', $parsed['title']);
        $this->assertSame('2025-10-30', $parsed['recorded_at']);
        $this->assertSame(1, $parsed['tracks'][0]['track_number']);
        $this->assertSame('Dave and Bob', $parsed['tracks'][0]['title']);
        $this->assertNull($parsed['tracks'][0]['notes']);
        $this->assertSame(16, $parsed['tracks'][3]['track_number']);
        $this->assertSame('Ace', $parsed['tracks'][3]['title']);
    }
}
