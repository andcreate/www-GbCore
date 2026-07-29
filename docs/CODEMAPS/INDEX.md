<!-- Generated: 2026-07-30 | Files scanned: 8 | Token estimate: ~350 -->

# GbCore Codemaps Index

**Plugin**: GbCore
**Version**: 1.0.0
**Status**: Stable
**Activation Scope**: Site-level (フックを持たないため、どちらでも副作用はない)

---

## Quick Links

| Document | Purpose |
|----------|---------|
| **[architecture.md](architecture.md)** | 3 クラスの責務、不変条件、ロード経路、CI |
| **[dependencies.md](dependencies.md)** | 依存ゼロの根拠、開発時のみの依存、CI 資材 |

**存在しない codemap とその理由** — 書き忘れではなく、対象が無い。

| 通常あるもの | 無い理由 |
|---|---|
| `backend.md` | REST ルート・フック・サービス層を持たない |
| `frontend.md` | スクリプト・スタイル・ブロックを持たない |
| `data.md` | CPT・post_meta・wp_options・テーブル・トランジェントを持たない |

これが増えたら設計が壊れた合図。

---

## Architecture at a Glance

```
www-gbcore.php (entry: Autoloader 登録のみ)
    │
    └─ src/
       ├─ Autoloader.php          spl_autoload_register 1 本。多重登録ガード付き
       ├─ Time/
       │  ├─ TimeOfDay.php        0 時起点の分で表す時刻 VO (日跨ぎ 47:59 まで)
       │  └─ OpeningHours.php     1 営業日の開始・終了。日跨ぎを +24h 正規化
       └─ Contracts/
          └─ ResourceAvailabilityProvider.php   リソース可用性の読み取り契約
```

---

## 3 つの不変条件

1. **WordPress を知らない** — `wp_*` / `WP_*` / フック名 / グローバル変数を含まない
2. **依存ゼロ** — GbCore 内部以外に依存しない
3. **汎用語彙のみ** — 特定業務の語彙を名前にもシグネチャにも持ち込まない

いずれもレビューではなく CI が担保する。詳細は [architecture.md](architecture.md)。

---

## 逆依存 (誰が使っているか)

本リポジトリには**意図的に記載しない**。GbCore が利用側を知らないことは不変条件①②の
帰結であり、ここに consumer 一覧を持つと「知らない」が嘘になる。
利用側の一覧は、利用側エコシステムのドキュメントが持つ。
