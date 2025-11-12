<?php
/**
 * Plugin Name: XpressBuy247 Smart Sandbox Installer Pro
 * Description: Safe trial activation, REST/CLI verify/upload/activate, admin bar quick actions, log rotation.
 * Version: X.0.0.1
 * Author: XpressBuy247
 */
defined('ABSPATH') || exit;

namespace XpressBuy247\SandboxInstallerPro;

require_once __DIR__ . '/src/Core/Plugin.php';

\XpressBuy247\SandboxInstallerPro\Core\Plugin::boot();
