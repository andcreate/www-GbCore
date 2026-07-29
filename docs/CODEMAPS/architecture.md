<!-- Generated: 2026-07-30 | Files scanned: 8 | Token estimate: ~800 -->

# GbCore Architecture

依存ゼロ・WordPress 非依存の値オブジェクトと契約だけを持つカーネル。
**判断に迷ったら「これは 3 つの不変条件を壊すか?」だけを見る。**

---

## ロード経路

```
WordPress plugin loader
    └─ www-gbcore.php
         ├─ ABSPATH ガード
         ├─ const GBCORE_VERSION           get_file_data の毎リクエスト I/O 回避
         └─ require src/Autoloader.php → Autoloader::register()
                                            │
                                            └─ spl_autoload_register (遅延解決)
```

`require_once` は **Autoloader の 1 本だけ**。フック登録もクラス初期化もしない。

- 読み込み順に依存しない。GbCore と利用側のどちらが先でも動く
- `Autoloader::register()` は多重登録ガード (`self::$registered`) 付き。
  プラグイン本体とテスト bootstrap の双方から呼ばれても二重登録されない
- ハンドラは `[self::class, 'loadClass']` ではなく**クロージャで包む**。
  PHPStan の `AutoloadFunctionsSourceLocator` は登録済みオートローダーを自スコープから
  直接呼ぶため、private メソッドを直接登録すると解析が止まる (2026-07-29 実測)

---

## クラス一覧

### `GbCore\Time\TimeOfDay` (src/Time/TimeOfDay.php)

0 時起点の**分**で時刻を表す不変オブジェクト。範囲は `[0, 47:59]` = `[0, 2879]` 分。
日跨ぎ営業を扱うため 24 時以降の表記 (`"25:30"` = 1530 分) を受け入れる。

```php
__construct(public readonly int $totalMinutes)   // 範囲外は InvalidArgumentException
static fromString(string $hhmm): self            // "9:05" も可。分 >= 60 は拒否
static isValid(string $hhmm): bool               // 非 throw 版。"48:00" は false
toString(): string                               // "%02d:%02d"。"25:30" を許容
equals(self): bool
isBefore(self): bool
isAtOrAfter(self): bool
addMinutes(int): self                            // 新インスタンスを返す
```

**なぜ 1 箇所に閉じるのか** — `sprintf` の書式ミスと分計算の二重実装を防ぐため。
等価判定・差分・書式化を呼び出し側に散らすと、必ず片方だけ直る。

### `GbCore\Time\OpeningHours` (src/Time/OpeningHours.php)

1 営業日の開始・終了を持つ不変オブジェクト。`open === close` は拒否。

```php
__construct(public readonly TimeOfDay $open, public readonly TimeOfDay $close)
static fromStrings(string $open, string $close): self
isOvernight(): bool                  // close < open
closeMinutesNormalized(): int        // 日跨ぎなら +24*60
openMinutes(): int
durationMinutes(): int               // closeMinutesNormalized() - openMinutes()
```

**正規化を 1 箇所に閉じる理由** — `+ 24 * 60` のリテラルが呼び出し側に散ると、
スロット展開のたびに付け忘れ・二重加算が起きる。

境界の扱い: `close = "00:00"` は `close < open` なので日跨ぎ判定になり、
正規化後は `"24:00"` と同じ 1440 分に落ち着く (テストで固定)。

### `GbCore\Contracts\ResourceAvailabilityProvider` (src/Contracts/…)

「ある日に、どのリソースが、何時から何時まで利用可能か」の**読み取り契約**。
リソースの実体 (人・設備・部屋) と保存先は実装側の関心事で、本 interface は関知しない。

```php
getAvailabilityForDate(string $date): array<int, array{start: string, end: string}>
    // $date = YYYY-MM-DD、キー = resourceId、値 = HH:MM
    // 例: ['2026-07-30' に対して] [12 => ['start' => '20:00', 'end' => '29:00']]

getAvailableResourceIds(string $startDate, string $endDate): list<int>
    // 範囲内に 1 日でも空きがあるリソース ID。両端含む。例: [12, 34]
```

**なぜ 2 本に分けるのか** — 「今後 N 日に 1 度でも空きがあるリソース全部」を
1 クエリで引けるようにするため。日別 API を N 回呼ぶ実装を利用側に強いない。

---

## 3 つの不変条件を CI が担保する仕組み

| 不変条件 | 担保する仕組み | 破ったときの挙動 |
|---|---|---|
| WordPress を知らない | `phpstan.neon.dist` に **WordPress スタブを入れない** | `wp_*` を 1 つ書くと未定義関数で PHPStan が落ちる |
| 依存ゼロ | `.github/workflows/ci.yml` に `sibling-repos` を書かない | 他リポジトリのクラスを使うと解析・テストが落ちる |
| 汎用語彙のみ | `phpcs-security.xml.dist` が常に無検出であるべき前提 + レビュー | WordPress.Security が反応したら混入の合図 |

PHPStan は **level 9**。依存ゼロで WP の緩い型定義と戦う必要がないため、
他プラグインの雛形 (level 5) まで下げる理由がない。ベースラインは持たない。

---

## テスト

```
tests/bootstrap.php                      src/Autoloader.php を登録するだけ (WordPress は読まない)
tests/Unit/Time/TimeOfDayTest.php        13 tests
tests/Unit/Time/OpeningHoursTest.php      5 tests
```

PSR-4 の解決を **composer ではなく `src/Autoloader.php`** に任せている。
本番も composer の `vendor/` を使わないため、テストが本番と同じ経路を通る。
composer autoload は PHPUnit 本体を読むためにだけ使う。

`ResourceAvailabilityProvider` に単体テストは無い (振る舞いを持たない interface)。
契約の検証は実装側リポジトリの責務。
