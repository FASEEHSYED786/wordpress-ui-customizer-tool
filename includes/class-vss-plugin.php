<?php
/**
 * Plugin bootstrap.
 *
 * @package VisualSiteStudio
 */

if (!defined('ABSPATH')) {
    exit;
}

class VSS_Plugin {

    /**
     * @var VSS_Plugin|null
     */
    private static $instance = null;

    /**
     * @return VSS_Plugin
     */
    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Seed sellable defaults on first activation.
     */
    public static function activate() {
        if (false === get_option(VSS_Store::OPTION_SETTINGS, false)) {
            VSS_Store::save_settings(VSS_Store::default_settings());
        }
        if (false === get_option(VSS_Store::OPTION_RULES, false)) {
            update_option(VSS_Store::OPTION_RULES, array(), false);
        }
        if (false === get_option(VSS_Store::OPTION_WIDGETS, false)) {
            VSS_Store::save_widgets(
                array(
                    array(
                        'id'      => 'vss_shortcuts',
                        'title'   => __('Site shortcuts', 'visual-site-studio'),
                        'content' => '<p>' . esc_html__('Welcome to your site control panel.', 'visual-site-studio') . '</p><ul><li><a href="post-new.php">' . esc_html__('Write a post', 'visual-site-studio') . '</a></li><li><a href="upload.php">' . esc_html__('Media library', 'visual-site-studio') . '</a></li></ul>',
                        'roles'   => array('administrator', 'editor', 'author'),
                    ),
                )
            );
        }
        if (false === get_option(VSS_Store::OPTION_SNIPPETS, false)) {
            VSS_Store::save_snippets(
                array(
                    array(
                        'id'         => 'vss-announcement',
                        'slug'       => 'announcement',
                        'title'      => __('Announcement', 'visual-site-studio'),
                        'content'    => '<strong>' . esc_html__('Now live:', 'visual-site-studio') . '</strong> ' . esc_html__('Edit this banner from Site Studio → Dynamic content.', 'visual-site-studio'),
                        'visibility' => 'all',
                        'start'      => '',
                        'end'        => '',
                    ),
                )
            );
        }
    }

    /**
     * Wire subsystems.
     */
    public function boot() {
        load_plugin_textdomain('visual-site-studio', false, dirname(plugin_basename(VSS_FILE)) . '/languages');

        $rest = new VSS_REST();
        add_action('rest_api_init', array($rest, 'register'));

        (new VSS_Assets())->register();
        (new VSS_Admin())->register();
        (new VSS_Dashboard())->register();
        (new VSS_Dynamic_Content())->register();
    }
}
