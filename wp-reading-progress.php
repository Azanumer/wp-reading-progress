<?php
/**
 * Plugin Name: Reading Progress
 * Description: A slim reading-progress bar plus an estimated reading time for posts — no JavaScript libraries, no tracking.
 * Version: 1.0.0
 * Author: Azan Umer
 * License: MIT
 */

if (!defined('ABSPATH')) {
    exit;
}

define('WRP_OPTION', 'wrp_settings');

function wrp_defaults() {
    return array(
        'bar_color'    => '#2563eb',
        'bar_height'   => 4,
        'position'     => 'top', // top | bottom
        'show_time'    => 1,
        'words_per_min' => 200,
        'post_types'   => array('post'),
    );
}

function wrp_get_settings() {
    return wp_parse_args(get_option(WRP_OPTION, array()), wrp_defaults());
}

/** Estimate reading minutes from plain text. Filterable WPM via wrp_words_per_min. */
function wrp_reading_minutes($content) {
    $settings = wrp_get_settings();
    $wpm = max(50, (int) apply_filters('wrp_words_per_min', $settings['words_per_min']));
    $words = str_word_count(wp_strip_all_tags($content));
    return max(1, (int) ceil($words / $wpm));
}

/** [reading_time] shortcode — "5 min read". */
function wrp_reading_time_shortcode() {
    if (!is_singular()) {
        return '';
    }
    $mins = wrp_reading_minutes(get_the_content());
    return esc_html(sprintf(_n('%d min read', '%d min read', $mins, 'wrp'), $mins));
}
add_shortcode('reading_time', 'wrp_reading_time_shortcode');

/** Prepend "X min read" above single-post content when enabled. */
function wrp_prepend_reading_time($content) {
    if (!is_singular() || !in_main_loop() || !is_main_query()) {
        return $content;
    }
    $settings = wrp_get_settings();
    if (empty($settings['show_time']) || !in_array(get_post_type(), (array) $settings['post_types'], true)) {
        return $content;
    }
    $mins = wrp_reading_minutes($content);
    $label = esc_html(sprintf(_n('%d min read', '%d min read', $mins, 'wrp'), $mins));
    return '<p class="wrp-reading-time">' . $label . '</p>' . $content;
}
add_filter('the_content', 'wrp_prepend_reading_time');

/** Print the progress bar + tiny inline script on enabled single posts. */
function wrp_print_bar() {
    if (!is_singular()) {
        return;
    }
    $settings = wrp_get_settings();
    if (!in_array(get_post_type(), (array) $settings['post_types'], true)) {
        return;
    }
    $color  = sanitize_hex_color($settings['bar_color']) ?: '#2563eb';
    $height = max(2, min(12, (int) $settings['bar_height']));
    $pos    = $settings['position'] === 'bottom' ? 'bottom:0;' : 'top:0;';
    ?>
    <div id="wrp-bar" style="position:fixed;left:0;<?php echo esc_attr($pos); ?>width:0;height:<?php echo esc_attr($height); ?>px;background:<?php echo esc_attr($color); ?>;z-index:99999;"></div>
    <script>
    (function () {
        var bar = document.getElementById('wrp-bar');
        function update() {
            var doc = document.documentElement;
            var max = doc.scrollHeight - doc.clientHeight;
            bar.style.width = (max > 0 ? (doc.scrollTop / max * 100) : 0) + '%';
        }
        window.addEventListener('scroll', update, { passive: true });
        window.addEventListener('resize', update);
        update();
    })();
    </script>
    <?php
}
add_action('wp_footer', 'wrp_print_bar');

/** Settings page. */
function wrp_register_settings() {
    register_setting('wrp_group', WRP_OPTION, array(
        'sanitize_callback' => function ($input) {
            $out = wrp_defaults();
            $out['bar_color'] = sanitize_hex_color($input['bar_color']) ?: '#2563eb';
            $out['bar_height'] = max(2, min(12, (int) ($input['bar_height'] ?? 4)));
            $out['position'] = in_array($input['position'] ?? 'top', array('top', 'bottom'), true) ? $input['position'] : 'top';
            $out['show_time'] = !empty($input['show_time']) ? 1 : 0;
            $out['words_per_min'] = max(50, (int) ($input['words_per_min'] ?? 200));
            $out['post_types'] = array_values(array_intersect((array) ($input['post_types'] ?? array('post')), get_post_types(array('public' => true))));
            return $out;
        },
    ));
    add_options_page('Reading Progress', 'Reading Progress', 'manage_options', 'wrp-settings', 'wrp_render_settings');
}
add_action('admin_menu', 'wrp_register_settings');

function wrp_render_settings() {
    $s = wrp_get_settings();
    ?>
    <div class="wrap">
        <h1>Reading Progress</h1>
        <form method="post" action="options.php">
            <?php settings_fields('wrp_group'); ?>
            <table class="form-table">
                <tr><th>Bar colour</th><td><input type="color" name="<?php echo esc_attr(WRP_OPTION); ?>[bar_color]" value="<?php echo esc_attr($s['bar_color']); ?>"></td></tr>
                <tr><th>Bar height (px)</th><td><input type="number" min="2" max="12" name="<?php echo esc_attr(WRP_OPTION); ?>[bar_height]" value="<?php echo esc_attr($s['bar_height']); ?>"></td></tr>
                <tr><th>Position</th><td><select name="<?php echo esc_attr(WRP_OPTION); ?>[position]"><option value="top" <?php selected($s['position'], 'top'); ?>>Top</option><option value="bottom" <?php selected($s['position'], 'bottom'); ?>>Bottom</option></select></td></tr>
                <tr><th>Show reading time</th><td><input type="checkbox" name="<?php echo esc_attr(WRP_OPTION); ?>[show_time]" value="1" <?php checked($s['show_time'], 1); ?>></td></tr>
                <tr><th>Words per minute</th><td><input type="number" min="50" name="<?php echo esc_attr(WRP_OPTION); ?>[words_per_min]" value="<?php echo esc_attr($s['words_per_min']); ?>"></td></tr>
            </table>
            <?php submit_button(); ?>
        </form>
        <p>Shortcode: <code>[reading_time]</code> anywhere in a post.</p>
    </div>
    <?php
}

function wrp_uninstall() {
    delete_option(WRP_OPTION);
}
