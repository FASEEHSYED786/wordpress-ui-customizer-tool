<?php
/**
 * Scheduled, audience-aware content snippets.
 *
 * @package VisualSiteStudio
 */

if (!defined('ABSPATH')) {
    exit;
}

class VSS_Dynamic_Content {

    /**
     * Register shortcode and block.
     */
    public function register() {
        add_shortcode('vss_content', array($this, 'shortcode'));
        add_shortcode('custom_content', array($this, 'legacy_shortcode'));
        add_action('init', array($this, 'register_block'));
    }

    /**
     * Dynamic Gutenberg block (rendered in PHP).
     */
    public function register_block() {
        if (!function_exists('register_block_type')) {
            return;
        }

        register_block_type(
            'vss/content',
            array(
                'api_version'     => 2,
                'render_callback' => array($this, 'render_block'),
                'attributes'      => array(
                    'slug' => array(
                        'type'    => 'string',
                        'default' => '',
                    ),
                ),
            )
        );
    }

    /**
     * @param array<string, string> $attributes Block attributes.
     * @return string
     */
    public function render_block($attributes) {
        $slug = isset($attributes['slug']) ? $attributes['slug'] : '';
        return $this->render_slug($slug);
    }

    /**
     * @param array<string, string> $atts Shortcode attributes.
     * @return string
     */
    public function shortcode($atts) {
        $atts = shortcode_atts(
            array(
                'slug' => '',
                'id'   => '',
            ),
            $atts,
            'vss_content'
        );

        if ($atts['slug']) {
            return $this->render_slug($atts['slug']);
        }
        if ($atts['id']) {
            return $this->render_id($atts['id']);
        }

        return $this->render_first();
    }

    /**
     * Back-compat with the old Custom Content Display shortcode.
     *
     * @return string
     */
    public function legacy_shortcode() {
        $legacy = get_option('ccd_custom_content');
        if (is_string($legacy) && '' !== $legacy) {
            return wp_kses_post($legacy);
        }
        return $this->render_first();
    }

    /**
     * @param string $slug Snippet slug.
     * @return string
     */
    public function render_slug($slug) {
        $slug = sanitize_title($slug);
        foreach (VSS_Store::snippets() as $snippet) {
            if (isset($snippet['slug']) && $snippet['slug'] === $slug) {
                return $this->maybe_output($snippet);
            }
        }
        return '';
    }

    /**
     * @param string $id Snippet id.
     * @return string
     */
    public function render_id($id) {
        $id = sanitize_text_field($id);
        foreach (VSS_Store::snippets() as $snippet) {
            if (isset($snippet['id']) && $snippet['id'] === $id) {
                return $this->maybe_output($snippet);
            }
        }
        return '';
    }

    /**
     * First enabled snippet, used when no slug is given.
     *
     * @return string
     */
    private function render_first() {
        foreach (VSS_Store::snippets() as $snippet) {
            $html = $this->maybe_output($snippet);
            if ('' !== $html) {
                return $html;
            }
        }
        return '';
    }

    /**
     * Honour schedule + login visibility.
     *
     * @param array<string, mixed> $snippet Snippet.
     * @return string
     */
    private function maybe_output($snippet) {
        if (!$this->is_visible($snippet)) {
            return '';
        }
        $content = isset($snippet['content']) ? $snippet['content'] : '';
        return '<div class="vss-snippet">' . wp_kses_post($content) . '</div>';
    }

    /**
     * @param array<string, mixed> $snippet Snippet.
     * @return bool
     */
    private function is_visible($snippet) {
        $now = current_time('timestamp');
        if (!empty($snippet['start'])) {
            $start = strtotime($snippet['start']);
            if ($start && $now < $start) {
                return false;
            }
        }
        if (!empty($snippet['end'])) {
            $end = strtotime($snippet['end']);
            if ($end && $now > $end) {
                return false;
            }
        }

        $visibility = isset($snippet['visibility']) ? $snippet['visibility'] : 'all';
        if ('logged_in' === $visibility && !is_user_logged_in()) {
            return false;
        }
        if ('logged_out' === $visibility && is_user_logged_in()) {
            return false;
        }

        return true;
    }
}
