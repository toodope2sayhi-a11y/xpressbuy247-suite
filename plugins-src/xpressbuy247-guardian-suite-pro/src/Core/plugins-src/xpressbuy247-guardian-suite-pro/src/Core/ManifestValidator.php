<?php
/**
 * XpressBuy247 Guardian Suite Pro – Manifest Validator
 * Checks plugin manifests during activation for safety and consistency.
 */

namespace XpressBuy247\GuardianSuitePro\Core;

defined('ABSPATH') || exit;

class ManifestValidator
{
    /**
     * Run validation for all installed plugins that contain manifest.json
     */
    public static function run_check(): void
    {
        $plugins = wp_get_active_and_valid_plugins();
        foreach ($plugins as $pluginPath) {
            $pluginDir = dirname($pluginPath);
            $manifest = $pluginDir . '/manifest.json';
            if (file_exists($manifest)) {
                self::validate($manifest, $pluginPath);
            }
        }
    }

    /**
     * Validate a single manifest file.
     */
    private static function validate(string $manifestPath, string $pluginPath): void
    {
        $json = file_get_contents($manifestPath);
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("[Guardian] Invalid JSON in manifest for {$pluginPath}");
            return;
        }

        $name = $data['plugin_name'] ?? 'Unknown Plugin';
        $slug = $data['slug'] ?? basename($pluginPath);
        $checksum = $data['build_metadata']['checksum'] ?? null;
        $dependencies = $data['dependencies']['required'] ?? [];

        // Check checksum
        $realChecksum = hash_file('sha256', $manifestPath);
        if ($checksum && $checksum !== $realChecksum) {
            deactivate_plugins($pluginPath);
            error_log("[Guardian] Checksum mismatch for {$name} — plugin deactivated.");
            return;
        }

        // Check required dependencies
        foreach ($dependencies as $dep) {
            $depDir = WP_PLUGIN_DIR . '/' . $dep;
            if (!is_dir($depDir)) {
                deactivate_plugins($pluginPath);
                error_log("[Guardian] Missing required dependency ({$dep}) for {$name} — plugin deactivated.");
                return;
            }
        }

        // Passed validation
        error_log("[Guardian] {$name} manifest verified successfully.");
    }
}

// Hook validator to plugin activation events
add_action('activated_plugin', function() {
    \XpressBuy247\GuardianSuitePro\Core\ManifestValidator::run_check();
});
