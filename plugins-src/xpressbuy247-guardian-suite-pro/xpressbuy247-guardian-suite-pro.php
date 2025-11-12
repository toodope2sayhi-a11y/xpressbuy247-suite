<?php
/**
 * Plugin Name: XpressBuy247 Guardian Suite Pro
 * Description: Rollback engine, integrity scans, JSON logs, email alerts, Guardian signatures.
 * Version: X.0.0.1
 * Author: XpressBuy247
 */
defined('ABSPATH') || exit;

namespace XpressBuy247\GuardianSuitePro;

require_once __DIR__ . '/src/Core/Plugin.php';

\XpressBuy247\GuardianSuitePro\Core\Plugin::boot();
