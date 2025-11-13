<?php
/**
 * Plugin Name: XpressBuy247 Ultra Sandbox Pro
 * Description: Enterprise sandbox for safe plugin installs & activations with Guardian integration, REST & CLI verify/upload/activate-safe, rate-limited admin actions, admin bar tools, rolling logs, and auto-rollback on failure.
 * Version: vA.0.1.0
 * Author: XpressBuy247
 * Author URI: https://xpressbuy247.com
 */
declare(strict_types=1);

namespace XpressBuy247\UltraSandboxPro;

defined('ABSPATH') || exit;

require_once __DIR__ . '/src/Core/Plugin.php';

Core\Plugin::boot();
