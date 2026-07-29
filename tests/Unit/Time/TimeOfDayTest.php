<?php

declare(strict_types=1);

namespace GbCore\Tests\Unit\Time;

use GbCore\Time\TimeOfDay;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(TimeOfDay::class)]
final class TimeOfDayTest extends TestCase
{
    public function test_fromString_parses_normal_time(): void
    {
        $t = TimeOfDay::fromString('12:30');
        self::assertSame(12 * 60 + 30, $t->totalMinutes);
        self::assertSame('12:30', $t->toString());
    }

    public function test_fromString_accepts_overnight_notation(): void
    {
        $t = TimeOfDay::fromString('25:30');
        self::assertSame(25 * 60 + 30, $t->totalMinutes);
        self::assertSame('25:30', $t->toString());
    }

    public function test_fromString_zero_padding_on_output(): void
    {
        $t = TimeOfDay::fromString('9:05');
        self::assertSame('09:05', $t->toString());
    }

    public function test_fromString_rejects_invalid_format(): void
    {
        $this->expectException(InvalidArgumentException::class);
        TimeOfDay::fromString('not-a-time');
    }

    public function test_fromString_rejects_minute_overflow(): void
    {
        $this->expectException(InvalidArgumentException::class);
        TimeOfDay::fromString('12:60');
    }

    public function test_isValid_returns_true_for_valid(): void
    {
        self::assertTrue(TimeOfDay::isValid('00:00'));
        self::assertTrue(TimeOfDay::isValid('12:30'));
        self::assertTrue(TimeOfDay::isValid('25:30'));
    }

    public function test_isValid_returns_false_for_invalid(): void
    {
        self::assertFalse(TimeOfDay::isValid(''));
        self::assertFalse(TimeOfDay::isValid('not-a-time'));
        self::assertFalse(TimeOfDay::isValid('12:60'));
        self::assertFalse(TimeOfDay::isValid('48:00'), '上限超過 (48:00) も false');
    }

    #[DataProvider('boundaryProvider')]
    public function test_constructor_enforces_range(int $minutes, bool $shouldThrow): void
    {
        if ($shouldThrow) {
            $this->expectException(InvalidArgumentException::class);
        }
        $t = new TimeOfDay($minutes);
        self::assertSame($minutes, $t->totalMinutes);
    }

    /** @return array<string, array{int, bool}> */
    public static function boundaryProvider(): array
    {
        return [
            '下限 0'      => [0, false],
            '上限 47:59'  => [48 * 60 - 1, false],
            '下限未満'    => [-1, true],
            '上限超過'    => [48 * 60, true],
        ];
    }

    public function test_equals_and_comparisons(): void
    {
        $a = TimeOfDay::fromString('10:00');
        $b = TimeOfDay::fromString('10:00');
        $c = TimeOfDay::fromString('10:30');

        self::assertTrue($a->equals($b));
        self::assertFalse($a->equals($c));
        self::assertTrue($a->isBefore($c));
        self::assertFalse($c->isBefore($a));
        self::assertTrue($c->isAtOrAfter($a));
        self::assertTrue($a->isAtOrAfter($b));
    }

    public function test_addMinutes_returns_new_instance_and_preserves_original(): void
    {
        $a = TimeOfDay::fromString('10:00');
        $b = $a->addMinutes(90);

        self::assertSame('11:30', $b->toString());
        self::assertSame('10:00', $a->toString(), '不変性: 元オブジェクトは変化しない');
        self::assertNotSame($a, $b);
    }
}
