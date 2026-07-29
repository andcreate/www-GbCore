<?php

declare(strict_types=1);

namespace GbCore\Contracts;

/**
 * 「ある日に、どのリソースが、何時から何時まで利用可能か」を返す読み取り契約。
 *
 * リソースの実体(人・設備・部屋など)と、その可用性がどこに保存されているかは
 * 実装側の関心事であり、本インターフェースは一切関知しない。
 *
 * 利用側はこの契約にのみ依存することで:
 *   - 可用性の供給元を差し替えられる
 *   - テストでインメモリ実装を差し込め、ストレージ無しで検証できる
 */
interface ResourceAvailabilityProvider
{
    /**
     * 指定営業日に利用可能なリソースと、その時間帯を取得する。
     *
     * @param string $date YYYY-MM-DD
     * @return array<int, array{start: string, end: string}> resourceId => {start: HH:MM, end: HH:MM}
     */
    public function getAvailabilityForDate(string $date): array;

    /**
     * 指定日付範囲に 1 日でも利用可能な時間帯を持つリソース ID の一覧を取得する。
     *
     * 「今後 N 日に 1 度でも空きがあるリソース全部」を 1 クエリで引く用途を想定し、
     * 日別 API を N 回呼ぶ実装を利用側に強いないために分けている。
     *
     * @param string $startDate YYYY-MM-DD(含む)
     * @param string $endDate   YYYY-MM-DD(含む)
     * @return list<int>
     */
    public function getAvailableResourceIds(string $startDate, string $endDate): array;
}
