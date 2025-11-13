<?php
/**
 * Plugin Name: XpressBuy247 Smart Sandbox Installer Pro
 * Description: Safe trial activation, REST/CLI verify/upload/activate, admin bar quick actions, log rotation.
 * Version: vA.0.0.7
 * Author: XpressBuy247
 * Author URI: https://xpressbuy247.com
 */

namespace XpressBuy247\SandboxInstallerPro;

defined('ABSPATH') || exit;

require_once __DIR__ . '/src/Core/Plugin.php';

\Core\Plugin::boot();
