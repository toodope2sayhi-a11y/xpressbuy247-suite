<?php
/**
 * XpressBuy247 Guardian Suite Pro – Admin Log Viewer
 * Displays daily Guardian logs in WordPress Admin with download option.
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
        add_action('admin_post_guardian_download_log', [__CLASS__, 'download_log']);
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
        echo '<p>Welcome to the Guardian Suite Dashboard. Use the Logs tab to view and download validation reports.</p></div>';
    }

    /**
     * Display logs and download option
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

        // Download button
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        echo '<input type="hidden" name="action" value="guardian_download_log">';
        echo '<input type="hidden" name="file" value="' . esc_attr($selectedFile) . '">';
        wp_nonce_field('guardian_download_log', '_wpnonce_guardian_log');
        echo '<p><button type="submit" class="button button-primary">⬇️ Download This Log</button></p>';
        echo '</form><hr>';

        // Display the file content
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

    /**
     * Handle log file download
     */
    public static function download_log(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die('Permission denied.');
        }

        check_admin_referer('guardian_download_log', '_wpnonce_guardian_log');

        $file = sanitize_file_name($_POST['file'] ?? '');
        if (!$file) {
            wp_die('Invalid file.');
        }

        $upload_dir = wp_upload_dir();
        $filePath = $upload_dir['basedir'] . '/guardian/logs/' . $file;

        if (!file_exists($filePath)) {
            wp_die('File not found.');
        }

        header('Content-Description: File Transfer');
        header('Content-Type: text/plain');
        header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit;
    }
}

// Initialize admin features
add_action('plugins_loaded', function () {
    if (is_admin()) {
        \XpressBuy247\GuardianSuitePro\Core\GuardianAdminLogs::init();
    }
});
