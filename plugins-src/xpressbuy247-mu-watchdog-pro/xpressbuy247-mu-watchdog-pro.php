<?php
/**
 * Plugin Name: XpressBuy247 MU Watchdog Pro
 * Description: Fatal monitor, Safe Mode switch, timed auto-disable, alert emails.
 * Version: X.0.0.1
 * Author: XpressBuy247
 */
defined('ABSPATH') || exit;

namespace XpressBuy247\WatchdogPro;

require_once __DIR__ . '/src/Core/Plugin.php';

\XpressBuy247\WatchdogPro\Core\Plugin::boot();
