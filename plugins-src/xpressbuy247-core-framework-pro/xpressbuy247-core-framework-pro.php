<?php
/**
 * Plugin Name: XpressBuy247 Core Framework Pro
 * Description: Provides constants, autoloader, logger, REST helpers, and diagnostics.
 * Version: X.0.0.1
 * Author: XpressBuy247
 */
defined('ABSPATH') || exit;

namespace XpressBuy247\CoreFrameworkPro;

require_once __DIR__ . '/src/Core/Plugin.php';

\XpressBuy247\CoreFrameworkPro\Core\Plugin::boot();
