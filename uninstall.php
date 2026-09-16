<?php
/**
 * Remove plugin data on uninstall.
 *
 * @package VisualSiteStudio
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

delete_option('vss_rules');
delete_option('vss_widgets');
delete_option('vss_snippets');
delete_option('vss_settings');
