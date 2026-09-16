<?php
/**
 * REST API for the visual editor and admin screens.
 *
 * @package VisualSiteStudio
 */

if (!defined('ABSPATH')) {
    exit;
}

class VSS_REST {

    const NAMESPACE = 'vss/v1';

    /**
     * Register routes.
     */
    public function register() {
        register_rest_route(
            self::NAMESPACE,
            '/state',
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array($this, 'get_state'),
                    'permission_callback' => array($this, 'permissions'),
                ),
                array(
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => array($this, 'save_state'),
                    'permission_callback' => array($this, 'permissions'),
                ),
            )
        );

        register_rest_route(
            self::NAMESPACE,
            '/export',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'export'),
                'permission_callback' => array($this, 'permissions'),
            )
        );

        register_rest_route(
            self::NAMESPACE,
            '/import',
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array($this, 'import'),
                'permission_callback' => array($this, 'permissions'),
            )
        );
    }

    /**
     * @return bool
     */
    public function permissions() {
        return VSS_Store::current_user_can();
    }

    /**
     * @return WP_REST_Response
     */
    public function get_state() {
        return rest_ensure_response($this->payload());
    }

    /**
     * @param WP_REST_Request $request Request.
     * @return WP_REST_Response
     */
    public function save_state($request) {
        $params = $request->get_json_params();
        if (!is_array($params)) {
            $params = array();
        }

        if (array_key_exists('rules', $params)) {
            VSS_Store::save_rules($params['rules']);
        }
        if (array_key_exists('widgets', $params)) {
            VSS_Store::save_widgets($params['widgets']);
        }
        if (array_key_exists('snippets', $params)) {
            VSS_Store::save_snippets($params['snippets']);
        }
        if (array_key_exists('settings', $params)) {
            VSS_Store::save_settings($params['settings']);
        }

        return rest_ensure_response($this->payload());
    }

    /**
     * @return WP_REST_Response
     */
    public function export() {
        return rest_ensure_response(VSS_Store::export_all());
    }

    /**
     * @param WP_REST_Request $request Request.
     * @return WP_REST_Response
     */
    public function import($request) {
        $params = $request->get_json_params();
        if (!is_array($params)) {
            return new WP_REST_Response(array('message' => __('Invalid import file.', 'visual-site-studio')), 400);
        }

        if (isset($params['rules'])) {
            VSS_Store::save_rules($params['rules']);
        }
        if (isset($params['widgets'])) {
            VSS_Store::save_widgets($params['widgets']);
        }
        if (isset($params['snippets'])) {
            VSS_Store::save_snippets($params['snippets']);
        }
        if (isset($params['settings'])) {
            VSS_Store::save_settings($params['settings']);
        }

        return rest_ensure_response($this->payload());
    }

    /**
     * @return array<string, mixed>
     */
    private function payload() {
        return array(
            'rules'    => VSS_Store::rules(),
            'widgets'  => VSS_Store::widgets(),
            'snippets' => VSS_Store::snippets(),
            'settings' => VSS_Store::settings(),
        );
    }
}
