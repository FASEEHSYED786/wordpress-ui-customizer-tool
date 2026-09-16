<?php
/**
 * Custom WordPress dashboard widgets for client sites.
 *
 * @package VisualSiteStudio
 */

if (!defined('ABSPATH')) {
    exit;
}

class VSS_Dashboard {

    /**
     * Hook widgets.
     */
    public function register() {
        add_action('wp_dashboard_setup', array($this, 'setup'));
    }

    /**
     * Register each saved widget the current user is allowed to see.
     */
    public function setup() {
        $settings = VSS_Store::settings();
        $user     = wp_get_current_user();
        $roles    = (array) $user->roles;

        if (!empty($settings['welcome_text'])) {
            wp_add_dashboard_widget(
                'vss_welcome',
                esc_html($settings['brand_name'] ? $settings['brand_name'] : __('Welcome', 'visual-site-studio')),
                array($this, 'render_welcome')
            );
        }

        foreach (VSS_Store::widgets() as $widget) {
            if (!empty($widget['roles']) && empty(array_intersect($roles, $widget['roles']))) {
                continue;
            }
            $id = isset($widget['id']) ? $widget['id'] : uniqid('vss_', true);
            wp_add_dashboard_widget(
                'vss_widget_' . sanitize_key($id),
                isset($widget['title']) ? $widget['title'] : __('Studio widget', 'visual-site-studio'),
                function () use ($widget) {
                    echo wp_kses_post(isset($widget['content']) ? $widget['content'] : '');
                }
            );
        }
    }

    /**
     * Welcome widget body.
     */
    public function render_welcome() {
        $settings = VSS_Store::settings();
        echo wp_kses_post($settings['welcome_text']);
        if (VSS_Store::current_user_can()) {
            echo '<p><a class="button" href="' . esc_url(admin_url('admin.php?page=visual-site-studio')) . '">' . esc_html__('Open Site Studio', 'visual-site-studio') . '</a></p>';
        }
    }
}
