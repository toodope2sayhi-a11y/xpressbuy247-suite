<?php
/**
 * XpressBuy247 Guardian Suite Pro – Admin Log Viewer
 * Displays daily Guardian logs in WordPress Admin.
 */

namespace XpressBuy247\GuardianSuitePro\Core;

defined('ABSPATH') || exit;

class GuardianAdminLogs
{
    /**
     * Register the Guardian menu and submenu
     */
    public static function init(): void
    {
        add_action('admin_menu', [__CLASS__, 'register_menu']);
    }

    public static function register_menu(): void
    {
        add_menu_page(
            'Guardian Suite',
            'Guardian',
            'manage_options',
            'guardian-dashboard',
            [__CLASS__, 'render_dashboard'],
            'dashicons-shield',
            65
        );

        add_submenu_page(
            'guardian-dashboard',
            'Guardian Logs',
            'Logs',
            'manage_options',
            'guardian-logs',
            [__CLASS__, 'render_logs']
        );
    }

    /**
     * Simple dashboard (placeholder)
     */
    public static function render_dashboard(): void
    {
        echo '<div class="wrap"><h1>🛡️ XpressBuy247 Guardian Suite Pro</h1>';
        echo '<p>Welcome to the Guardian Suite Dashboard. Use the Logs tab to view validation reports.</p></div>';
    }

    /**
     * Display logs
     */
    public static function render_logs(): void
    {
        $upload_dir = wp_upload_dir();
        $log_dir = $upload_dir['basedir'] . '/guardian/logs';
        echo '<div class="wrap"><h1>🗂 Guardian Logs</h1>';

        if (!is_dir($log_dir)) {
            echo '<p><strong>No logs found yet.</strong></p></div>';
            return;
        }

        $files = glob($log_dir . '/manifest-checks-*.log');
        rsort($files);

        if (empty($files)) {
            echo '<p><strong>No logs available.</strong></p></div>';
            return;
        }

        echo '<form method="get">';
        echo '<input type="hidden" name="page" value="guardian-logs">';
        echo '<label for="file">Select a log file:</label> ';
        echo '<select name="file" id="file" onchange="this.form.submit()">';
        foreach ($files as $file) {
            $basename = basename($file);
            $selected = (isset($_GET['file']) && $_GET['file'] === $basename) ? 'selected' : '';
            echo "<option value='{$basename}' {$selected}>{$basename}</option>";
        }
        echo '</select>';
        echo '</form><hr>';

        $selectedFile = $_GET['file'] ?? basename($files[0]);
        $filePath = $log_dir . '/' . sanitize_file_name($selectedFile);

        if (file_exists($filePath)) {
            $content = file_get_contents($filePath);
            echo '<div style="background:#111;color:#0f0;padding:10px;border-radius:8px;max-height:500px;overflow:auto;font-family:monospace;">';
            echo nl2br(esc_html($content));
            echo '</div>';
        } else {
            echo '<p>File not found.</p>';
        }

        echo '</div>';
    }
}

// Initialize the admin menu on plugin load
add_action('plugins_loaded', function () {
    if (is_admin()) {
        \XpressBuy247\GuardianSuitePro\Core\GuardianAdminLogs::init();
    }
});
