<?php
declare(strict_types=1);

namespace XpressBuy247\UltraSandboxPro\Core;

defined('ABSPATH') || exit;

use WP_Error;

final class Plugin {
    public const SLUG = 'xpressbuy247-ultra-sandbox-pro';
    public const CAP  = 'manage_options';
    public const NONCE_ACTION = 'xb247_ultra_sandbox';
    public const LOG_DIR = 'guardian/logs';

    public static function boot(): void {
        add_action('plugins_loaded', [__CLASS__, 'init_constants'], 1);
        add_action('plugins_loaded', [__CLASS__, 'integrity_check'], 2);
        add_action('plugins_loaded', [__CLASS__, 'wire'], 5);
        register_activation_hook(self::plugin_file(), [__CLASS__, 'on_activate']);
        register_deactivation_hook(self::plugin_file(), [__CLASS__, 'on_deactivate']);
    }

    public static function init_constants(): void {
        if (!defined('XB247_USP_UPLOADS')) {
            $uploads = wp_get_upload_dir();
            define('XB247_USP_UPLOADS', trailingslashit($uploads['basedir']));
        }
    }

    public static function integrity_check(): void {
        if (!defined('ABSPATH') || __NAMESPACE__ === '') {
            self::panic('Integrity check failed: bad bootstrap.');
        }
    }

    public static function wire(): void {
        require_once __DIR__ . '/Services/Log.php';
        require_once __DIR__ . '/Services/RateLimiter.php';
        require_once __DIR__ . '/Services/SafeActivator.php';
        require_once __DIR__ . '/Services/Routes.php';
        require_once __DIR__ . '/Services/Cli.php';
        require_once __DIR__ . '/../Admin/Bar.php';
        require_once __DIR__ . '/Services/Cleaner.php';
        require_once __DIR__ . '/Services/Viewer.php';

        Services\Routes::register();
        Services\Cli::register();
        Admin\Bar::register();
        Services\Cleaner::register();
        Services\Viewer::register();
    }

    public static function on_activate(): void {
        $dir = XB247_USP_UPLOADS . self::LOG_DIR . '/sandbox';
        if (!wp_mkdir_p($dir)) {
            self::panic('Cannot create sandbox log directory.');
        }
        $ht = $dir . '/.htaccess';
        if (!file_exists($ht)) {
            @file_put_contents($ht, "Order deny,allow\nDeny from all\n");
        }
        Services\Log::info('Ultra Sandbox Pro activated.');
    }

    public static function on_deactivate(): void {
        Services\Log::info('Ultra Sandbox Pro deactivated.');
    }

    public static function plugin_file(): string {
        return WP_PLUGIN_DIR . '/' . self::SLUG . '/' . self::SLUG . '.php';
    }

    public static function panic(string $message, $context = []): void {
        require_once __DIR__ . '/Services/Log.php';
        Services\Log::error($message, $context);
        if (is_admin()) { wp_die(esc_html($message)); }
        throw new \RuntimeException($message);
    }
}
