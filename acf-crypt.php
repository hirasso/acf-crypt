<?php

/**
 * Plugin Name: acf-encrypted
 * Description: Encrypt selected fields, to protect sensitive data in your database
 * Version: 1.0.0
 * Requires PHP: 8.2
 * Author: Rasso Hilber
 * Author URI: https://rassohilber.com
 * License: GPL-2.0-or-later
 * GitHub Plugin URI: hirasso/acf-encrypt
 */

use Hirasso\ACFCrypt\ACFCrypt;

/** Exit if accessed directly */
if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/strauss/autoload.php';
if (is_readable(__DIR__ . '/vendor')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

define('ACF_ENCRYPTION_PLUGIN_URI', untrailingslashit(plugin_dir_url(__FILE__)));
define('ACF_ENCRYPTION_PLUGIN_DIR', untrailingslashit(__DIR__));

ACFCrypt::init();
