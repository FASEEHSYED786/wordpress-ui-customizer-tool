<?php
/**
 * Script and style loading for editor, admin, and compiled CSS.
 *
 * @package VisualSiteStudio
 */

if (!defined('ABSPATH')) {
    exit;
}

class VSS_Assets {

    /**
     * Hook asset loaders.
     */
    public function register() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin'));
        add_action('enqueue_block_editor_assets', array($this, 'enqueue_block'));
        add_action('wp_head', array($this, 'print_saved_css'), 100);
        add_action('admin_bar_menu', array($this, 'admin_bar'), 80);
        add_action('wp_footer', array($this, 'editor_shell'));
        add_filter('plugin_action_links_' . plugin_basename(VSS_FILE), array($this, 'action_links'));
    }

    /**
     * Front-end editor assets (logged-in editors only).
     */
    public function enqueue_frontend() {
        if (is_admin() || !VSS_Store::current_user_can()) {
            return;
        }

        wp_enqueue_style(
            'vss-editor',
            VSS_URL . 'assets/css/editor.css',
            array(),
            VSS_VERSION
        );
        wp_enqueue_script(
            'vss-editor',
            VSS_URL . 'assets/js/editor.js',
            array(),
            VSS_VERSION,
            true
        );
        wp_localize_script('vss-editor', 'vssData', $this->editor_data());
    }

    /**
     * Settings screen assets.
     *
     * @param string $hook Current admin page.
     */
    public function enqueue_admin($hook) {
        if ('toplevel_page_visual-site-studio' !== $hook) {
            return;
        }

        wp_enqueue_style(
            'vss-admin',
            VSS_URL . 'assets/css/admin.css',
            array(),
            VSS_VERSION
        );
        wp_enqueue_script(
            'vss-admin',
            VSS_URL . 'assets/js/admin.js',
            array(),
            VSS_VERSION,
            true
        );
        wp_localize_script('vss-admin', 'vssAdmin', array(
            'restUrl' => esc_url_raw(rest_url(VSS_REST::NAMESPACE . '/')),
            'nonce'   => wp_create_nonce('wp_rest'),
            'state'   => array(
                'rules'    => VSS_Store::rules(),
                'widgets'  => VSS_Store::widgets(),
                'snippets' => VSS_Store::snippets(),
                'settings' => VSS_Store::settings(),
            ),
        ));
    }

    /**
     * Gutenberg block for dynamic snippets.
     */
    public function enqueue_block() {
        wp_enqueue_script(
            'vss-block',
            VSS_URL . 'assets/js/block.js',
            array('wp-blocks', 'wp-element', 'wp-components', 'wp-i18n', 'wp-block-editor'),
            VSS_VERSION,
            true
        );
        wp_localize_script('vss-block', 'vssBlock', array(
            'snippets' => VSS_Store::snippets(),
        ));
    }

    /**
     * Print compiled CSS for visitors.
     */
    public function print_saved_css() {
        $css = VSS_Sanitizer::compile(VSS_Store::rules_for_current_page());
        if ('' === $css) {
            return;
        }
        echo '<style id="vss-saved-css">' . $css . '</style>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    /**
     * Admin bar toggle.
     *
     * @param WP_Admin_Bar $bar Toolbar.
     */
    public function admin_bar($bar) {
        if (!VSS_Store::current_user_can()) {
            return;
        }

        $settings = VSS_Store::settings();
        $title    = $settings['brand_name'] ? $settings['brand_name'] : __('Visual Site Studio', 'visual-site-studio');

        if (!empty($settings['hide_wp_logo'])) {
            $bar->remove_node('wp-logo');
        }

        $bar->add_node(
            array(
                'id'    => 'vss-studio',
                'title' => esc_html($title),
                'href'  => admin_url('admin.php?page=visual-site-studio'),
            )
        );

        if (!is_admin()) {
            $bar->add_node(
                array(
                    'id'     => 'vss-toggle-editor',
                    'parent' => 'vss-studio',
                    'title'  => __('Toggle visual editor', 'visual-site-studio'),
                    'href'   => '#vss-toggle',
                )
            );
        }

        $bar->add_node(
            array(
                'id'     => 'vss-settings',
                'parent' => 'vss-studio',
                'title'  => __('Studio settings', 'visual-site-studio'),
                'href'   => admin_url('admin.php?page=visual-site-studio'),
            )
        );
    }

    /**
     * Mount point for the editor chrome.
     */
    public function editor_shell() {
        if (is_admin() || !VSS_Store::current_user_can()) {
            return;
        }
        echo '<div id="vss-root" hidden></div>';
    }

    /**
     * Plugins screen shortcut.
     *
     * @param array<string, string> $links Plugin links.
     * @return array<string, string>
     */
    public function action_links($links) {
        $links['settings'] = '<a href="' . esc_url(admin_url('admin.php?page=visual-site-studio')) . '">' . esc_html__('Open Studio', 'visual-site-studio') . '</a>';
        return $links;
    }

    /**
     * Localized editor config.
     *
     * @return array<string, mixed>
     */
    private function editor_data() {
        $settings = VSS_Store::settings();
        return array(
            'restUrl'  => esc_url_raw(rest_url(VSS_REST::NAMESPACE . '/state')),
            'nonce'    => wp_create_nonce('wp_rest'),
            'pageId'   => is_singular() ? get_queried_object_id() : 0,
            'path'     => VSS_Store::request_path(),
            'brand'    => $settings['brand_name'],
            'rules'    => VSS_Store::rules(),
            'i18n'     => array(
                'save'     => __('Save styles', 'visual-site-studio'),
                'saved'    => __('Styles saved', 'visual-site-studio'),
                'error'    => __('Could not save styles.', 'visual-site-studio'),
                'select'   => __('Click any element to restyle it', 'visual-site-studio'),
                'nothing'  => __('No element selected', 'visual-site-studio'),
            ),
        );
    }
}
