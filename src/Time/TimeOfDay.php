<?php

declare(strict_types=1);

namespace GbCore\Time;

use InvalidArgumentException;

/**
 * 営業日起点の時刻を「0 時起点からの分」で表現する不変オブジェクト。
 *
 * 日跨ぎの営業時間(例: open=20:00, close=05:00)を扱うため、値の範囲は [0, 47:59] 相当
 * を許容する(例: "25:30" = 25 時 30 分 = 翌 1 時 30 分 = 1530 分)。
 *
 * 等価判定・差分計算・HH:MM 書式化を一貫して 1 箇所で扱い、
 * 呼び出し側のバグ(sprintf のミスや分計算の二重実装)を防ぐ。
 */
final class TimeOfDay
{
    private const MIN_TOTAL_MINUTES = 0;
    private const MAX_TOTAL_MINUTES = 48 * 60 - 1;

    public function __construct(public readonly int $totalMinutes)
    {
        if (
            $totalMinutes < self::MIN_TOTAL_MINUTES
            || $totalMinutes > self::MAX_TOTAL_MINUTES
        ) {
            throw new InvalidArgumentException(sprintf(
                'TimeOfDay は [%d, %d] の範囲内でなければなりません: %d',
                self::MIN_TOTAL_MINUTES,
                self::MAX_TOTAL_MINUTES,
                $totalMinutes
            ));
        }
    }

    /**
     * "HH:MM" 形式文字列から生成。
     * 日跨ぎ表現 "25:30" も受け入れる。
     */
    public static function fromString(string $hhmm): self
    {
        if (!preg_match('/^(\d{1,2}):(\d{2})$/', $hhmm, $m)) {
            throw new InvalidArgumentException('HH:MM 形式ではありません: ' . $hhmm);
        }
        $h = (int) $m[1];
        $min = (int) $m[2];
        if ($min >= 60) {
            throw new InvalidArgumentException('分は 0-59 の範囲: ' . $hhmm);
        }
        return new self($h * 60 + $min);
    }

    /**
     * `fromString` が例外を投げずに受け入れる文字列かどうかを判定する非 throw API。
     *
     * 範囲外時刻 (例: "48:00") もここでは false を返す(MAX_TOTAL_MINUTES を超えるため)。
     */
    public static function isValid(string $hhmm): bool
    {
        try {
            self::fromString($hhmm);
            return true;
        } catch (InvalidArgumentException) {
            return false;
        }
    }

    /**
     * "HH:MM" 表記を返す。日跨ぎ時は "25:30" のように 24 以降を許容。
     */
    public function toString(): string
    {
        return sprintf('%02d:%02d', intdiv($this->totalMinutes, 60), $this->totalMinutes % 60);
    }

    public function equals(self $other): bool
    {
        return $this->totalMinutes === $other->totalMinutes;
    }

    public function isBefore(self $other): bool
    {
        return $this->totalMinutes < $other->totalMinutes;
    }

    public function isAtOrAfter(self $other): bool
    {
        return $this->totalMinutes >= $other->totalMinutes;
    }

    public function addMinutes(int $minutes): self
    {
        return new self($this->totalMinutes + $minutes);
    }
}
