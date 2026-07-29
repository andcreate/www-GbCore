<?php

declare(strict_types=1);

namespace GbCore;

/**
 * GbCore の PSR-4 スタイル簡易オートローダー。
 *
 * 本番環境は composer の vendor/ を使わないため、composer の autoload には依存しない。
 * テストもこのオートローダー経由でクラスを解決し、本番と同じ経路を検証する。
 */
final class Autoloader
{
    private const NAMESPACE_PREFIX = 'GbCore\\';

    private static bool $registered = false;

    /**
     * 多重登録を防ぐ。
     *
     * 本プラグインのブートストラップとテスト bootstrap の双方から呼ばれても、
     * spl_autoload_register のスタックに同じハンドラが二重に積まれないようにする。
     */
    public static function register(): void
    {
        if (self::$registered) {
            return;
        }
        self::$registered = true;

        // [self::class, 'loadClass'] ではなくクロージャで包む。
        // PHPStan の AutoloadFunctionsSourceLocator は登録済みオートローダーを
        // 自分のスコープから直接呼ぶため、private メソッドを直接登録すると
        // 「Call to private method」で解析が止まる (2026-07-29 に実測)。
        // クロージャならクラススコープ内で解決されるので private のまま公開できる。
        spl_autoload_register(static function (string $class): void {
            self::loadClass($class);
        });
    }

    private static function loadClass(string $class): void
    {
        if (!str_starts_with($class, self::NAMESPACE_PREFIX)) {
            return;
        }

        $relative = substr($class, strlen(self::NAMESPACE_PREFIX));
        $path = __DIR__ . '/' . str_replace('\\', '/', $relative) . '.php';
        if (is_file($path)) {
            require_once $path;
        }
    }

    private function __construct()
    {
    }
}
