<?php
/**
 * Option storage for rules, widgets, snippets, and settings.
 *
 * @package VisualSiteStudio
 */

if (!defined('ABSPATH')) {
    exit;
}

class VSS_Store {

    const OPTION_RULES = 'vss_rules';
    const OPTION_WIDGETS = 'vss_widgets';
    const OPTION_SNIPPETS = 'vss_snippets';
    const OPTION_SETTINGS = 'vss_settings';

    /**
     * Default plugin settings.
     *
     * @return array<string, mixed>
     */
    public static function default_settings() {
        return array(
            'min_role'     => 'administrator',
            'brand_name'   => 'Visual Site Studio',
            'hide_wp_logo' => false,
            'welcome_text' => '',
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function settings() {
        $saved = get_option(self::OPTION_SETTINGS, array());
        if (!is_array($saved)) {
            $saved = array();
        }
        return array_merge(self::default_settings(), $saved);
    }

    /**
     * @param array<string, mixed> $settings Incoming settings.
     * @return array<string, mixed>
     */
    public static function save_settings($settings) {
        $current = self::settings();
        $roles   = array('administrator', 'editor', 'author');
        $min     = isset($settings['min_role']) ? sanitize_key($settings['min_role']) : $current['min_role'];
        if (!in_array($min, $roles, true)) {
            $min = 'administrator';
        }

        $clean = array(
            'min_role'     => $min,
            'brand_name'   => sanitize_text_field(isset($settings['brand_name']) ? $settings['brand_name'] : $current['brand_name']),
            'hide_wp_logo' => !empty($settings['hide_wp_logo']),
            'welcome_text' => wp_kses_post(isset($settings['welcome_text']) ? $settings['welcome_text'] : $current['welcome_text']),
        );

        update_option(self::OPTION_SETTINGS, $clean, false);
        return $clean;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function rules() {
        $rules = get_option(self::OPTION_RULES, array());
        return is_array($rules) ? array_values($rules) : array();
    }

    /**
     * @param array<int, mixed> $rules Incoming rules.
     * @return array<int, array<string, mixed>>
     */
    public static function save_rules($rules) {
        $clean = array();
        if (!is_array($rules)) {
            $rules = array();
        }

        foreach ($rules as $rule) {
            if (!is_array($rule)) {
                continue;
            }
            $id = isset($rule['id']) ? sanitize_text_field($rule['id']) : '';
            if ('' === $id) {
                $id = wp_generate_uuid4();
            }
            $scope = isset($rule['scope']) ? sanitize_key($rule['scope']) : 'global';
            if (!in_array($scope, array('global', 'page'), true)) {
                $scope = 'global';
            }
            $clean[] = array(
                'id'         => $id,
                'label'      => sanitize_text_field(isset($rule['label']) ? $rule['label'] : ''),
                'selector'   => VSS_Sanitizer::selector(isset($rule['selector']) ? $rule['selector'] : ''),
                'properties' => VSS_Sanitizer::properties(isset($rule['properties']) ? $rule['properties'] : array()),
                'custom_css' => VSS_Sanitizer::custom_css(isset($rule['custom_css']) ? $rule['custom_css'] : ''),
                'scope'      => $scope,
                'page_id'    => isset($rule['page_id']) ? absint($rule['page_id']) : 0,
                'path'       => sanitize_text_field(isset($rule['path']) ? $rule['path'] : ''),
                'enabled'    => !isset($rule['enabled']) || !empty($rule['enabled']),
            );
        }

        update_option(self::OPTION_RULES, $clean, false);
        return $clean;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function widgets() {
        $widgets = get_option(self::OPTION_WIDGETS, array());
        return is_array($widgets) ? array_values($widgets) : array();
    }

    /**
     * @param array<int, mixed> $widgets Incoming widgets.
     * @return array<int, array<string, mixed>>
     */
    public static function save_widgets($widgets) {
        $clean = array();
        if (!is_array($widgets)) {
            $widgets = array();
        }

        foreach ($widgets as $widget) {
            if (!is_array($widget)) {
                continue;
            }
            $id = isset($widget['id']) ? sanitize_key($widget['id']) : '';
            if ('' === $id) {
                $id = 'vss_' . wp_generate_uuid4();
            }
            $roles = array();
            if (!empty($widget['roles']) && is_array($widget['roles'])) {
                foreach ($widget['roles'] as $role) {
                    $roles[] = sanitize_key($role);
                }
            }
            $clean[] = array(
                'id'      => $id,
                'title'   => sanitize_text_field(isset($widget['title']) ? $widget['title'] : __('Client widget', 'visual-site-studio')),
                'content' => wp_kses_post(isset($widget['content']) ? $widget['content'] : ''),
                'roles'   => array_values(array_filter($roles)),
            );
        }

        update_option(self::OPTION_WIDGETS, $clean, false);
        return $clean;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function snippets() {
        $snippets = get_option(self::OPTION_SNIPPETS, array());
        return is_array($snippets) ? array_values($snippets) : array();
    }

    /**
     * @param array<int, mixed> $snippets Incoming snippets.
     * @return array<int, array<string, mixed>>
     */
    public static function save_snippets($snippets) {
        $clean = array();
        if (!is_array($snippets)) {
            $snippets = array();
        }

        $visibilities = array('all', 'logged_in', 'logged_out');
        foreach ($snippets as $snippet) {
            if (!is_array($snippet)) {
                continue;
            }
            $id = isset($snippet['id']) ? sanitize_text_field($snippet['id']) : '';
            if ('' === $id) {
                $id = wp_generate_uuid4();
            }
            $slug = isset($snippet['slug']) ? sanitize_title($snippet['slug']) : '';
            if ('' === $slug) {
                $slug = sanitize_title(isset($snippet['title']) ? $snippet['title'] : $id);
            }
            $visibility = isset($snippet['visibility']) ? sanitize_key($snippet['visibility']) : 'all';
            if (!in_array($visibility, $visibilities, true)) {
                $visibility = 'all';
            }
            $clean[] = array(
                'id'         => $id,
                'slug'       => $slug,
                'title'      => sanitize_text_field(isset($snippet['title']) ? $snippet['title'] : $slug),
                'content'    => wp_kses_post(isset($snippet['content']) ? $snippet['content'] : ''),
                'visibility' => $visibility,
                'start'      => sanitize_text_field(isset($snippet['start']) ? $snippet['start'] : ''),
                'end'        => sanitize_text_field(isset($snippet['end']) ? $snippet['end'] : ''),
            );
        }

        update_option(self::OPTION_SNIPPETS, $clean, false);
        return $clean;
    }

    /**
     * Capability required to use the editor / settings.
     *
     * @return string
     */
    public static function capability() {
        $settings = self::settings();
        $map      = array(
            'administrator' => 'manage_options',
            'editor'        => 'edit_pages',
            'author'        => 'edit_posts',
        );
        $role = isset($settings['min_role']) ? $settings['min_role'] : 'administrator';
        return isset($map[$role]) ? $map[$role] : 'manage_options';
    }

    /**
     * Whether the current user can use Visual Site Studio.
     *
     * @return bool
     */
    public static function current_user_can() {
        return current_user_can(self::capability());
    }

    /**
     * Rules that apply on the current front-end request.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function rules_for_current_page() {
        $page_id = is_singular() ? get_queried_object_id() : 0;
        $path    = self::request_path();
        $matched = array();

        foreach (self::rules() as $rule) {
            $scope = isset($rule['scope']) ? $rule['scope'] : 'global';
            if ('global' === $scope) {
                $matched[] = $rule;
                continue;
            }
            if (!empty($rule['page_id']) && (int) $rule['page_id'] === (int) $page_id) {
                $matched[] = $rule;
                continue;
            }
            if (!empty($rule['path']) && $rule['path'] === $path) {
                $matched[] = $rule;
            }
        }

        return $matched;
    }

    /**
     * Normalized request path.
     *
     * @return string
     */
    public static function request_path() {
        $path = isset($_SERVER['REQUEST_URI']) ? wp_unslash($_SERVER['REQUEST_URI']) : '/';
        $path = strtok($path, '?');
        $path = is_string($path) ? $path : '/';
        return untrailingslashit($path);
    }

    /**
     * Full export payload.
     *
     * @return array<string, mixed>
     */
    public static function export_all() {
        return array(
            'version'  => VSS_VERSION,
            'exported' => gmdate('c'),
            'rules'    => self::rules(),
            'widgets'  => self::widgets(),
            'snippets' => self::snippets(),
            'settings' => self::settings(),
        );
    }
}
