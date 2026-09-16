<?php
/**
 * Allowlist sanitization for selectors and CSS declarations.
 *
 * @package VisualSiteStudio
 */

if (!defined('ABSPATH') && !defined('VSS_STANDALONE_TEST')) {
    exit;
}

class VSS_Sanitizer {

    const PROPERTIES = array(
        'color',
        'background-color',
        'font-size',
        'font-weight',
        'font-family',
        'text-align',
        'text-decoration',
        'line-height',
        'letter-spacing',
        'display',
        'opacity',
        'margin',
        'margin-top',
        'margin-right',
        'margin-bottom',
        'margin-left',
        'padding',
        'padding-top',
        'padding-right',
        'padding-bottom',
        'padding-left',
        'border',
        'border-radius',
        'box-shadow',
        'width',
        'max-width',
        'height',
        'max-height',
        'visibility',
    );

    /**
     * Keep only characters that can appear in a CSS selector.
     *
     * @param string $selector Raw selector.
     * @return string
     */
    public static function selector($selector) {
        $selector = is_string($selector) ? $selector : '';
        $selector = preg_replace('/[^\w\s\-.#:>+~\[\]="\'*,()]/', '', $selector);
        $selector = str_replace(array('<', '`'), '', (string) $selector);
        $selector = trim(preg_replace('/\s+/', ' ', (string) $selector));
        return substr($selector, 0, 400);
    }

    /**
     * Sanitize a map of CSS property => value.
     *
     * @param mixed $properties Incoming properties.
     * @return array<string, string>
     */
    public static function properties($properties) {
        if (!is_array($properties)) {
            return array();
        }

        $clean = array();
        foreach ($properties as $property => $value) {
            $property = strtolower(trim((string) $property));
            if (!in_array($property, self::PROPERTIES, true)) {
                continue;
            }
            $value = self::declaration_value((string) $value);
            if ('' === $value) {
                continue;
            }
            $clean[$property] = $value;
        }

        return $clean;
    }

    /**
     * Strip dangerous tokens from a freeform CSS value or custom block.
     *
     * @param string $css Raw CSS.
     * @return string
     */
    public static function custom_css($css) {
        $css = is_string($css) ? $css : '';
        $css = function_exists('wp_strip_all_tags') ? wp_strip_all_tags($css) : strip_tags($css);
        $css = preg_replace('/<\/?style/i', '', $css);
        $css = preg_replace('/@import/i', '', $css);
        $css = preg_replace('/expression\s*\(/i', '', $css);
        $css = preg_replace('/javascript\s*:/i', '', $css);
        $css = preg_replace('/-moz-binding/i', '', $css);
        $css = preg_replace('/behavior\s*:/i', '', $css);
        $css = preg_replace('/url\s*\(\s*["\']?\s*javascript:/i', '', $css);
        return trim(substr((string) $css, 0, 4000));
    }

    /**
     * Sanitize a single declaration value.
     *
     * @param string $value Raw value.
     * @return string
     */
    public static function declaration_value($value) {
        $value = trim($value);
        if ('' === $value) {
            return '';
        }
        if (preg_match('/[<>{}]|expression|javascript:|@import/i', $value)) {
            return '';
        }
        return substr($value, 0, 200);
    }

    /**
     * Compile rules into a style tag body.
     *
     * @param array<int, array<string, mixed>> $rules Stored rules.
     * @return string
     */
    public static function compile($rules) {
        $out = '';
        if (!is_array($rules)) {
            return $out;
        }

        foreach ($rules as $rule) {
            if (!is_array($rule) || empty($rule['enabled'])) {
                continue;
            }
            $selector = self::selector(isset($rule['selector']) ? $rule['selector'] : '');
            if ('' === $selector) {
                continue;
            }
            $parts = array();
            $properties = self::properties(isset($rule['properties']) ? $rule['properties'] : array());
            foreach ($properties as $property => $value) {
                $parts[] = $property . ':' . $value . ' !important';
            }
            $custom = self::custom_css(isset($rule['custom_css']) ? $rule['custom_css'] : '');
            if ('' !== $custom) {
                $parts[] = rtrim($custom, ';');
            }
            if (empty($parts)) {
                continue;
            }
            $out .= $selector . '{' . implode(';', $parts) . ";}\n";
        }

        return $out;
    }
}
