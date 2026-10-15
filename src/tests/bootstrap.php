<?php
// PHPUnit starts before WordPress defines ABSPATH; keep web requests blocked.
if (PHP_SAPI !== 'cli') {
    exit;
}

$_tests_dir = getenv('WP_TESTS_DIR');
if (!$_tests_dir) $_tests_dir = '/tmp/wordpress-tests-lib';

$polyfills_path = getenv('WP_TESTS_PHPUNIT_POLYFILLS_PATH');
if ($polyfills_path && !defined('WP_TESTS_PHPUNIT_POLYFILLS_PATH')) {
    define('WP_TESTS_PHPUNIT_POLYFILLS_PATH', $polyfills_path);
}

require_once $_tests_dir . '/includes/functions.php';

function _manually_load_plugin() {
    require dirname(__FILE__) . '/../simply-rets.php';
}

tests_add_filter('muplugins_loaded', '_manually_load_plugin');

require $_tests_dir . '/includes/bootstrap.php';
