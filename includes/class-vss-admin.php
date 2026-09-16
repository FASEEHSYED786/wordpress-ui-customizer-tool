<?php
/**
 * Settings screen: widgets, snippets, white-label, import/export.
 *
 * @package VisualSiteStudio
 */

if (!defined('ABSPATH')) {
    exit;
}

class VSS_Admin {

    /**
     * Register the menu.
     */
    public function register() {
        add_action('admin_menu', array($this, 'menu'));
    }

    /**
     * Top-level Studio menu.
     */
    public function menu() {
        add_menu_page(
            __('Visual Site Studio', 'visual-site-studio'),
            __('Site Studio', 'visual-site-studio'),
            VSS_Store::capability(),
            'visual-site-studio',
            array($this, 'render'),
            'dashicons-art',
            58
        );
    }

    /**
     * Render the app shell. State is hydrated via vssAdmin.
     */
    public function render() {
        if (!VSS_Store::current_user_can()) {
            wp_die(esc_html__('You do not have permission to manage Visual Site Studio.', 'visual-site-studio'));
        }
        ?>
        <div class="wrap vss-admin">
            <header class="vss-admin__header">
                <div>
                    <p class="vss-admin__eyebrow"><?php esc_html_e('Commercial WordPress toolkit', 'visual-site-studio'); ?></p>
                    <h1><?php esc_html_e('Visual Site Studio', 'visual-site-studio'); ?></h1>
                    <p class="vss-admin__lede"><?php esc_html_e('Restyle the front end without touching theme files, give clients a clean dashboard, and schedule content from one place.', 'visual-site-studio'); ?></p>
                </div>
                <a class="button button-primary button-hero" href="<?php echo esc_url(home_url('/')); ?>" target="_blank" rel="noopener noreferrer">
                    <?php esc_html_e('Open visual editor', 'visual-site-studio'); ?>
                </a>
            </header>

            <nav class="vss-tabs" role="tablist">
                <button type="button" class="vss-tabs__btn is-active" data-vss-tab="overview"><?php esc_html_e('Overview', 'visual-site-studio'); ?></button>
                <button type="button" class="vss-tabs__btn" data-vss-tab="widgets"><?php esc_html_e('Client dashboard', 'visual-site-studio'); ?></button>
                <button type="button" class="vss-tabs__btn" data-vss-tab="snippets"><?php esc_html_e('Dynamic content', 'visual-site-studio'); ?></button>
                <button type="button" class="vss-tabs__btn" data-vss-tab="settings"><?php esc_html_e('White label', 'visual-site-studio'); ?></button>
                <button type="button" class="vss-tabs__btn" data-vss-tab="backup"><?php esc_html_e('Backup', 'visual-site-studio'); ?></button>
            </nav>

            <section class="vss-panel is-active" data-vss-panel="overview">
                <div class="vss-grid">
                    <article class="vss-card">
                        <h2><?php esc_html_e('How to sell and use this', 'visual-site-studio'); ?></h2>
                        <ol class="vss-steps">
                            <li><?php esc_html_e('Visit any front-end page while logged in.', 'visual-site-studio'); ?></li>
                            <li><?php esc_html_e('Use the admin bar → Toggle visual editor (or the floating Studio button).', 'visual-site-studio'); ?></li>
                            <li><?php esc_html_e('Click an element, change color, type, spacing, or hide it, then Save styles.', 'visual-site-studio'); ?></li>
                            <li><?php esc_html_e('Changes persist for every visitor — no theme file edits.', 'visual-site-studio'); ?></li>
                        </ol>
                    </article>
                    <article class="vss-card" id="vss-stats"></article>
                </div>
            </section>

            <section class="vss-panel" data-vss-panel="widgets">
                <article class="vss-card">
                    <div class="vss-card__row">
                        <h2><?php esc_html_e('White-label dashboard widgets', 'visual-site-studio'); ?></h2>
                        <button type="button" class="button" id="vss-add-widget"><?php esc_html_e('Add widget', 'visual-site-studio'); ?></button>
                    </div>
                    <p><?php esc_html_e('Replace the noisy default WordPress dashboard with shortcuts and notes your clients actually use.', 'visual-site-studio'); ?></p>
                    <div id="vss-widgets"></div>
                    <p><button type="button" class="button button-primary" data-vss-save="widgets"><?php esc_html_e('Save widgets', 'visual-site-studio'); ?></button></p>
                </article>
            </section>

            <section class="vss-panel" data-vss-panel="snippets">
                <article class="vss-card">
                    <div class="vss-card__row">
                        <h2><?php esc_html_e('Scheduled dynamic content', 'visual-site-studio'); ?></h2>
                        <button type="button" class="button" id="vss-add-snippet"><?php esc_html_e('Add snippet', 'visual-site-studio'); ?></button>
                    </div>
                    <p><?php echo wp_kses_post(__('Drop a snippet anywhere with <code>[vss_content slug="announcement"]</code> or the Gutenberg block. Control visibility and start/end dates.', 'visual-site-studio')); ?></p>
                    <div id="vss-snippets"></div>
                    <p><button type="button" class="button button-primary" data-vss-save="snippets"><?php esc_html_e('Save snippets', 'visual-site-studio'); ?></button></p>
                </article>
            </section>

            <section class="vss-panel" data-vss-panel="settings">
                <article class="vss-card">
                    <h2><?php esc_html_e('Agency white label', 'visual-site-studio'); ?></h2>
                    <form id="vss-settings-form" class="vss-form">
                        <label>
                            <span><?php esc_html_e('Product name in the admin bar', 'visual-site-studio'); ?></span>
                            <input type="text" name="brand_name" maxlength="80">
                        </label>
                        <label>
                            <span><?php esc_html_e('Who can use the visual editor', 'visual-site-studio'); ?></span>
                            <select name="min_role">
                                <option value="administrator"><?php esc_html_e('Administrators only', 'visual-site-studio'); ?></option>
                                <option value="editor"><?php esc_html_e('Editors and administrators', 'visual-site-studio'); ?></option>
                                <option value="author"><?php esc_html_e('Authors, editors, and administrators', 'visual-site-studio'); ?></option>
                            </select>
                        </label>
                        <label class="vss-check">
                            <input type="checkbox" name="hide_wp_logo">
                            <span><?php esc_html_e('Hide the WordPress logo in the admin bar', 'visual-site-studio'); ?></span>
                        </label>
                        <label>
                            <span><?php esc_html_e('Client welcome note (dashboard)', 'visual-site-studio'); ?></span>
                            <textarea name="welcome_text" rows="4"></textarea>
                        </label>
                        <p><button type="submit" class="button button-primary"><?php esc_html_e('Save white-label settings', 'visual-site-studio'); ?></button></p>
                    </form>
                </article>
            </section>

            <section class="vss-panel" data-vss-panel="backup">
                <article class="vss-card">
                    <h2><?php esc_html_e('Export / import', 'visual-site-studio'); ?></h2>
                    <p><?php esc_html_e('Move rules, widgets, and snippets between sites — useful for agency presets.', 'visual-site-studio'); ?></p>
                    <p>
                        <button type="button" class="button" id="vss-export"><?php esc_html_e('Download backup JSON', 'visual-site-studio'); ?></button>
                        <label class="button"><?php esc_html_e('Import backup', 'visual-site-studio'); ?>
                            <input type="file" id="vss-import" accept="application/json" hidden>
                        </label>
                    </p>
                    <div id="vss-rules-list"></div>
                </article>
            </section>

            <div id="vss-toast" class="vss-toast" hidden></div>
        </div>
        <?php
    }
}
