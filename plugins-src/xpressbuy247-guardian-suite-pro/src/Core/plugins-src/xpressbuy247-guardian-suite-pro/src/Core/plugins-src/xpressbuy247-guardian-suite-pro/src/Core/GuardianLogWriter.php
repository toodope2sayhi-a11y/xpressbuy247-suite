<?php
/**
 * XpressBuy247 Guardian Suite Pro – Log Writer
 * Writes Guardian messages to /uploads/guardian/logs/
 */

namespace XpressBuy247\GuardianSuitePro\Core;

defined('ABSPATH') || exit;

class GuardianLogWriter
{
    /**
     * Write a message into the Guardian log
     */
    public static function write(string $message): void
    {
        $upload_dir = wp_upload_dir();
        $base = $upload_dir['basedir'] . '/guardian/logs';
        if (!file_exists($base)) {
            wp_mkdir_p($base);
        }

        $date = gmdate('Y-m-d');
        $file = "{$base}/manifest-checks-{$date}.log";

        $timestamp = gmdate('[Y-m-d H:i:s]');
        $line = "{$timestamp} {$message}\n";

        file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
    }
}
