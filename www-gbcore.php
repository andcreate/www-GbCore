<?php

/**
 * Plugin Name: GbCore
 * Description: スケジューリング系プラグインが共有する時刻値オブジェクトと契約のカーネル。WordPress API に依存しない。
 * Version: 1.0.0
 * Requires PHP: 8.1
 * Author URI: https://github.com/andcreate
 * License: GPL-2.0-or-later
 * Text Domain: gbcore
 *
 * プラグインヘッダを namespace の後ではなく先頭に置いているのは PSR-12 の要求
 * (PSR12.Files.FileHeader: ファイル docblock は開始タグの直後)。
 * WordPress は先頭 8KB を正規表現で読むだけなので、この位置でも認識される。
 */

declare(strict_types=1);

namespace GbCore;

// 直接アクセス禁止
if (!defined('ABSPATH')) {
    exit;
}

// プラグインバージョン定数（get_file_data の毎リクエスト I/O を回避）
const GBCORE_VERSION = '1.0.0';

/**
 * このファイルがすることはオートローダーの登録 1 本だけ。
 *
 * フック登録も CPT 定義も持たない（持った時点で「WordPress を知らない」が壊れる）。
 * 実際のクラスは spl_autoload_register による遅延解決なので、
 * 本プラグインと利用側プラグインのどちらが先に読み込まれても動く。
 */
require_once __DIR__ . '/src/Autoloader.php';
Autoloader::register();
