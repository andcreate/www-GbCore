# GbCore

スケジューリング系 WordPress プラグインが共有する、**依存ゼロの時刻値オブジェクトと契約**。

```
GbCore\Time\TimeOfDay                           0 時起点の分で表す時刻 VO (日跨ぎ 47:59 まで)
GbCore\Time\OpeningHours                        1 営業日の開始・終了時刻 (日跨ぎ正規化)
GbCore\Contracts\ResourceAvailabilityProvider   リソース可用性の読み取り契約
```

## 3 つの不変条件

1. **WordPress を知らない** — `wp_*` / `WP_*` / フック名 / グローバル変数を一切含まない
2. **依存ゼロ** — GbCore 内部以外に依存しない
3. **汎用語彙のみ** — 特定業務の語彙を名前にもシグネチャにも持ち込まない

この 3 つはレビューではなく CI が担保する。`phpstan.neon.dist` に WordPress スタブを
入れていないため、`wp_*` を 1 つ書いた時点で静的解析が落ちる。

## 配布形態

composer パッケージではなく **WordPress プラグイン**として配る。

利用側プラグインは本番環境で composer の `vendor/` を使わず、各自の軽量 PSR-4
オートローダーでクラスを解決している。composer パッケージにすると
「CI では解決するが本番では fatal」という乖離が生まれるため、
プラグインとして配置し `spl_autoload_register` で遅延解決させる。

読み込み順には依存しない。GbCore と利用側プラグインのどちらが先に
読み込まれても動作する。

## 使い方

```php
use GbCore\Time\OpeningHours;
use GbCore\Time\TimeOfDay;

$hours = OpeningHours::fromStrings('20:00', '05:00');

$hours->isOvernight();             // true
$hours->closeMinutesNormalized();  // 29 * 60  (+24h 換算)
$hours->durationMinutes();         // 9 * 60

TimeOfDay::fromString('25:30')->toString();  // '25:30'
TimeOfDay::isValid('12:60');                 // false
```

可用性の供給側は `ResourceAvailabilityProvider` を実装する。

```php
use GbCore\Contracts\ResourceAvailabilityProvider;

final class MyProvider implements ResourceAvailabilityProvider
{
    /** @return array<int, array{start: string, end: string}> */
    public function getAvailabilityForDate(string $date): array
    {
        return [12 => ['start' => '10:00', 'end' => '18:00']];
    }

    /** @return list<int> */
    public function getAvailableResourceIds(string $startDate, string $endDate): array
    {
        return [12];
    }
}
```

## 開発

```bash
composer install
composer test      # PHPUnit
composer analyse   # PHPStan level 9
```

テストは composer の autoload ではなく `src/Autoloader.php` を通す。
本番と同じ解決経路を検証するため。

## ライセンス

GPL-2.0-or-later
