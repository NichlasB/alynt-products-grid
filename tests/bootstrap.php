<?php

define('ALYNT_PG_TESTS_PLUGIN_DIR', dirname(__DIR__));

if (!defined('ABSPATH')) {
    define('ABSPATH', ALYNT_PG_TESTS_PLUGIN_DIR . '/');
}

require_once ALYNT_PG_TESTS_PLUGIN_DIR . '/vendor/autoload.php';

if (!function_exists('plugin_dir_path')) {
    function plugin_dir_path($file) {
        return rtrim(str_replace('\\', '/', dirname($file)), '/') . '/';
    }
}

if (!function_exists('plugin_dir_url')) {
    function plugin_dir_url($file) {
        return 'http://example.com/wp-content/plugins/' . basename(dirname($file)) . '/';
    }
}

if (!function_exists('plugin_basename')) {
    function plugin_basename($file) {
        return basename(dirname($file)) . '/' . basename($file);
    }
}

if (!function_exists('register_activation_hook')) {
    function register_activation_hook($file, $callback) {
    }
}

if (!function_exists('register_deactivation_hook')) {
    function register_deactivation_hook($file, $callback) {
    }
}

if (!function_exists('apply_filters')) {
    function apply_filters($hook_name, $value) {
        return $value;
    }
}

if (!function_exists('get_option')) {
    function get_option($option, $default = false) {
        if ('active_plugins' === $option) {
            return array('woocommerce/woocommerce.php');
        }

        return $default;
    }
}

if (!function_exists('add_action')) {
    function add_action($hook_name, $callback, $priority = 10, $accepted_args = 1) {
    }
}

if (!function_exists('add_shortcode')) {
    function add_shortcode($tag, $callback) {
    }
}

require_once ALYNT_PG_TESTS_PLUGIN_DIR . '/alynt-products-grid.php';
