<?php
/**
 * Plugin Name: XpressBuy247 Core Framework Pro
 * Description: Core constants, autoloader, logger, REST helpers, and diagnostics for all XB247 plugins.
 * Version: X.0.0.2
 * Author: XpressBuy247
 * Requires PHP: 7.4
 * Requires at least: 6.4
 */

declare(strict_types=1);

namespace XpressBuy247\CoreFrameworkPro;

defined('ABSPATH') || exit;

require_once __DIR__ . '/src/Core/Plugin.php';

\XpressBuy247\CoreFrameworkPro\Core\Plugin::boot();
