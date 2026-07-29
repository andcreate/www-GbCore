<?php

/**
 * GbCore ユニットテスト用 bootstrap。
 *
 * WordPress は読み込まない。読み込む必要が生じたら、それは GbCore に
 * 入れてはいけないものが混ざった合図である。
 *
 * PSR-4 の解決は composer ではなく src/Autoloader.php に任せる。
 * 本番環境も composer の vendor/ を使わないため、テストが本番と同じ経路を通る。
 * (composer autoload は PHPUnit 本体を読むためにだけ使う)
 */

declare(strict_types=1);

require_once __DIR__ . '/../src/Autoloader.php';
\GbCore\Autoloader::register();
