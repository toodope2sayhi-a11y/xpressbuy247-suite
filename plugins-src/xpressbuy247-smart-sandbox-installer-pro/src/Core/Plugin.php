<?php
namespace XpressBuy247\SandboxInstallerPro\Core;

defined('ABSPATH') || exit;

final class Plugin {
    public static function boot(): void {
        \add_action('plugins_loaded', [__CLASS__, 'init']);
    }
    public static function init(): void {
        // Placeholder — wire real features in subsequent phases.
    }
}
