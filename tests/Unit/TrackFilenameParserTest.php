<?php

namespace Tests\Unit;

use App\Support\TrackFilenameParser;
use PHPUnit\Framework\TestCase;

class TrackFilenameParserTest extends TestCase
{
    public function test_it_parses_track_first_filename(): void
    {
        $parsed = TrackFilenameParser::parse('204 Son of a Preacher Man - 20260703 Fairhaven Wedding.mp3');

        $this->assertSame(204, $parsed['track_number']);
        $this->assertSame('Son of a Preacher Man', $parsed['title']);
        $this->assertSame('2026-07-03', $parsed['recorded_at']);
        $this->assertSame("Set 2, track 4\nFairhaven Wedding", $parsed['notes']);
    }

    public function test_it_parses_named_date_context_and_set_filename(): void
    {
        $parsed = TrackFilenameParser::parse('July 3 26 Fairhaven Wedding Set Two - 4 Son of a Preacher Man.mp3');

        $this->assertSame(204, $parsed['track_number']);
        $this->assertSame('Son of a Preacher Man', $parsed['title']);
        $this->assertSame('2026-07-03', $parsed['recorded_at']);
        $this->assertSame("Set 2, track 4\nFairhaven Wedding", $parsed['notes']);
    }

    public function test_it_parses_slug_style_filename(): void
    {
        $parsed = TrackFilenameParser::parse('2026-07-03-set-2-04-son-of-a-preacher-man---fairhaven-wedding.mp3');

        $this->assertSame(204, $parsed['track_number']);
        $this->assertSame('son of a preacher man', $parsed['title']);
        $this->assertSame('2026-07-03', $parsed['recorded_at']);
        $this->assertSame("Set 2, track 4\nfairhaven wedding", $parsed['notes']);
    }

    public function test_it_does_not_treat_time_as_track_number(): void
    {
        $parsed = TrackFilenameParser::parse('20210801 2139 Warning Signs mix 03.mp3');

        $this->assertNull($parsed['track_number']);
        $this->assertSame('Warning Signs', $parsed['title']);
        $this->assertSame('2021-08-01', $parsed['recorded_at']);
        $this->assertSame("Time: 21:39\nmix 03", $parsed['notes']);
    }

    public function test_it_parses_set_and_track_words(): void
    {
        $parsed = TrackFilenameParser::parse('Set 2 Track 04 Son of a Preacher Man.mp3');

        $this->assertSame(204, $parsed['track_number']);
        $this->assertSame('Son of a Preacher Man', $parsed['title']);
        $this->assertNull($parsed['recorded_at']);
        $this->assertSame('Set 2, track 4', $parsed['notes']);
    }

    public function test_it_parses_leading_order_number_without_set(): void
    {
        $parsed = TrackFilenameParser::parse('08 Doin That Rag - 20250821 OD Caledonia.mp3');

        $this->assertSame(8, $parsed['track_number']);
        $this->assertSame('Doin That Rag', $parsed['title']);
        $this->assertSame('2025-08-21', $parsed['recorded_at']);
        $this->assertSame('OD Caledonia', $parsed['notes']);
    }

    public function test_it_parses_date_then_title_without_track_or_notes(): void
    {
        $parsed = TrackFilenameParser::parse('19800915 Hit me with your best shot.mp3');

        $this->assertNull($parsed['track_number']);
        $this->assertSame('Hit me with your best shot', $parsed['title']);
        $this->assertSame('1980-09-15', $parsed['recorded_at']);
        $this->assertNull($parsed['notes']);
    }
}
