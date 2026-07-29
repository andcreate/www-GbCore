<?php

declare(strict_types=1);

namespace GbCore\Tests\Unit\Time;

use GbCore\Time\OpeningHours;
use GbCore\Time\TimeOfDay;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(OpeningHours::class)]
final class OpeningHoursTest extends TestCase
{
    public function test_same_day_hours(): void
    {
        $hours = OpeningHours::fromStrings('12:00', '24:00');

        self::assertFalse($hours->isOvernight());
        self::assertSame(12 * 60, $hours->openMinutes());
        self::assertSame(24 * 60, $hours->closeMinutesNormalized());
        self::assertSame(12 * 60, $hours->durationMinutes());
    }

    public function test_overnight_hours_normalize_close(): void
    {
        $hours = OpeningHours::fromStrings('20:00', '05:00');

        self::assertTrue($hours->isOvernight());
        self::assertSame(20 * 60, $hours->openMinutes());
        self::assertSame(29 * 60, $hours->closeMinutesNormalized(), 'close に +24h が加算される');
        self::assertSame(9 * 60, $hours->durationMinutes());
    }

    public function test_close_at_0000_is_treated_as_overnight(): void
    {
        // "00:00" と "24:00" は TimeOfDay では別値になる。
        // open=18:00, close=00:00 は close < open なので日跨ぎ判定になり、
        // 正規化後は 24:00 と同じ 1440 分に落ち着く。
        $hours = OpeningHours::fromStrings('18:00', '00:00');

        self::assertTrue($hours->isOvernight());
        self::assertSame(24 * 60, $hours->closeMinutesNormalized());
        self::assertSame(6 * 60, $hours->durationMinutes());
    }

    public function test_constructor_rejects_same_open_and_close(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new OpeningHours(TimeOfDay::fromString('10:00'), TimeOfDay::fromString('10:00'));
    }

    public function test_fromStrings_accepts_overnight_notation_on_close(): void
    {
        // 終了を 25:00 相当で記述した場合は非日跨ぎとして扱う
        $hours = OpeningHours::fromStrings('20:00', '25:00');
        self::assertFalse($hours->isOvernight());
        self::assertSame(25 * 60, $hours->closeMinutesNormalized());
    }
}
