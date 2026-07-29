<?php

declare(strict_types=1);

namespace GbCore\Time;

use InvalidArgumentException;

/**
 * 1 営業日の開始・終了時刻を表す不変オブジェクト。
 *
 * 日跨ぎ営業(close < open)を自動判定し、スロット展開時の終了分を
 * +24h 換算して返す closeMinutesNormalized() を提供する。
 * この正規化ロジックを 1 箇所に閉じることで、呼び出し側の計算ミスを防ぐ。
 */
final class OpeningHours
{
    public function __construct(
        public readonly TimeOfDay $open,
        public readonly TimeOfDay $close,
    ) {
        if ($open->equals($close)) {
            throw new InvalidArgumentException('open と close は同一時刻にできません');
        }
    }

    public static function fromStrings(string $open, string $close): self
    {
        return new self(TimeOfDay::fromString($open), TimeOfDay::fromString($close));
    }

    /**
     * 日跨ぎ営業か(終了時刻が開始より早い)。
     * 例: open=20:00, close=05:00 → true
     */
    public function isOvernight(): bool
    {
        return $this->close->isBefore($this->open);
    }

    /**
     * スロット展開用の「正規化された終了分」を返す。
     * 日跨ぎ時は +24h して open < close' となるようにする。
     */
    public function closeMinutesNormalized(): int
    {
        $close = $this->close->totalMinutes;
        return $this->isOvernight() ? $close + 24 * 60 : $close;
    }

    public function openMinutes(): int
    {
        return $this->open->totalMinutes;
    }

    /**
     * 営業時間の総分数(日跨ぎ正規化込み)。
     */
    public function durationMinutes(): int
    {
        return $this->closeMinutesNormalized() - $this->openMinutes();
    }
}
