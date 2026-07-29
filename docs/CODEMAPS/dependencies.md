<!-- Generated: 2026-07-30 | Files scanned: 8 | Token estimate: ~500 -->

# GbCore Dependencies

## 実行時依存: ゼロ

```
GbCore
└── (なし)
```

`src/` 全体で使っている外部シンボルは PHP 組み込みのみ。

| 使用シンボル | 種別 |
|---|---|
| `InvalidArgumentException` | PHP 組み込み例外 |
| `preg_match` / `sprintf` / `intdiv` | PHP 組み込み関数 |
| `spl_autoload_register` / `str_starts_with` / `substr` / `str_replace` / `is_file` | PHP 組み込み (Autoloader) |

**WordPress API は 1 つも使っていない。** 唯一 WordPress を意識しているのは
`www-gbcore.php` の `defined('ABSPATH')` ガードとプラグインヘッダで、
これはエントリポイントの作法であって `src/` には持ち込まない。

### 検証方法

```bash
# WordPress API の混入検査 (0 件であるべき)
grep -rnE '\b(wp_|WP_|add_action|add_filter|apply_filters|do_action|get_option|get_post)' src/

# 静的解析でも同じことが担保される。
# phpstan.neon.dist に WordPress スタブを入れていないため、
# wp_* を書いた時点で「未定義関数」で落ちる。
vendor/bin/phpstan analyse
```

---

## 開発時のみの依存 (require-dev)

| パッケージ | 用途 |
|---|---|
| `phpunit/phpunit:^10.5` | ユニットテスト |
| `phpstan/phpstan:^2.0` | 静的解析 (level 9) |

`composer.json` の `require` は `php: >=8.1` のみ。

**本番では composer を使わない。** 利用側プラグインは `vendor/` を持たず、
各自の軽量 PSR-4 オートローダーでクラスを解決している。composer パッケージとして
配ると「CI では解決するが本番では fatal」という乖離が生まれるため、
**WordPress プラグインとして配布**し `spl_autoload_register` で遅延解決させる。

PHP 要件が `>=8.1` なのは `readonly` プロパティと昇格コンストラクタを使うため。
CI と本番 Docker は 8.2。

---

## CI 資材

```
.github/workflows/ci.yml   → andcreate/.github の gb-plugin-ci.yml に委譲 (10 行)
phpcs.xml.dist             PSR-12 (整形)
phpcs-security.xml.dist    WordPress.Security (常に無検出であるべき)
phpstan.neon.dist          level 9 / WordPress スタブ無し / ベースライン無し
phpunit.xml                tests/bootstrap.php + tests/Unit
.gitattributes             * text=auto eol=lf
```

呼び出している共有ワークフローは **public リポジトリ** にあるため、
Semgrep ルールの取得に追加の認証は不要 (既定の `GITHUB_TOKEN` で足りる)。

`sibling-repos` は**指定していない**。ここに何かを足したくなった時点で、
不変条件②「依存ゼロ」が壊れている。

`.gitattributes` が必要な理由: PSR-12 の `Generic.Files.LineEndings` は `"\n"` を
要求するため、これが無いと Windows で `core.autocrlf=true` のまま clone しただけで
phpcs が全ファイル赤くなる。

---

## 逆依存 (誰が GbCore を使っているか)

**本リポジトリには記載しない。** 利用側を知らないことが不変条件①②の帰結であり、
ここに consumer 一覧を持つと「知らない」が嘘になる。破壊的変更の影響範囲は
利用側エコシステムのドキュメントが管理する。

破壊的変更を入れるときの手順だけをここに書く。

1. `www-gbcore.php` のプラグインヘッダ `Version:` と定数 `GBCORE_VERSION` を上げる
   (**2 箇所とも**。片方だけ上げると WordPress の表示と実行時定数がずれる)
2. 利用側は自分の CI (`sibling-repos: www-GbCore`) で壊れたことを検知する。
   本リポジトリ側から利用側を追う仕組みは持たない
