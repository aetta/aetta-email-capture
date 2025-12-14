<?php
if (!defined('ABSPATH')) exit;

class QEC_Form
{
    const COOKIE_THANKS = 'qec_thanks';
    const COOKIE_ERR = 'qec_err';

    public static function init()
    {
        add_shortcode('quick_email_capture', [__CLASS__, 'shortcode']);
        add_action('admin_post_qec_submit', [__CLASS__, 'handle_post']);
        add_action('admin_post_nopriv_qec_submit', [__CLASS__, 'handle_post']);
    }

    public static function handle_post()
    {
        $opts = QEC_Plugin::opts();

        $redirect_to = wp_get_referer();
        if (!$redirect_to) $redirect_to = home_url('/');

        $nonce = isset($_POST['qec_nonce']) ? sanitize_text_field(wp_unslash($_POST['qec_nonce'])) : '';
        if (!wp_verify_nonce($nonce, 'qec_submit')) {
            self::set_cookie(self::COOKIE_ERR, $opts['error_invalid']);
            wp_safe_redirect(remove_query_arg([self::COOKIE_THANKS, self::COOKIE_ERR], $redirect_to));
            exit;
        }

        $err = self::handle_submit($opts);

        if ($err === '') {
            self::set_cookie(self::COOKIE_THANKS, '1');
        } else {
            self::set_cookie(self::COOKIE_ERR, $err);
        }

        wp_safe_redirect(remove_query_arg([self::COOKIE_THANKS, self::COOKIE_ERR], $redirect_to));
        exit;
    }

    public static function shortcode($atts = [])
    {
        $opts = QEC_Plugin::opts();

        if ((int)$opts['use_css'] === 1) {
            wp_enqueue_style('qec-form', QEC_PLUGIN_URL . 'assets/css/form.css', [], QEC_VERSION);
        }

        $thanks = (self::get_cookie(self::COOKIE_THANKS) === '1');
        $err = self::get_cookie(self::COOKIE_ERR);

        if ($thanks) self::clear_cookie(self::COOKIE_THANKS);
        if ($err !== '') self::clear_cookie(self::COOKIE_ERR);

        $wrapper_extra = trim((string)$opts['wrapper_class']);
        $input_extra = trim((string)$opts['input_class']);
        $button_extra = trim((string)$opts['button_class']);
        $msg_extra = trim((string)$opts['message_class']);

        $style_vars = [];
        $style_vars[] = '--qec-border-color:' . (string)$opts['ui_border_color'];
        $style_vars[] = '--qec-border-width:' . (int)$opts['ui_border_width'] . 'px';
        $style_vars[] = '--qec-radius:' . (int)$opts['ui_radius'] . 'px';
        $style_vars[] = '--qec-input-height:' . (int)$opts['ui_input_height'] . 'px';
        $style_vars[] = '--qec-button-bg:' . (string)$opts['ui_button_bg'];
        $style_vars[] = '--qec-button-text:' . (string)$opts['ui_button_text'];
        $style_vars[] = '--qec-success-border:' . (string)$opts['ui_success_border'];
        $style_vars[] = '--qec-error-border:' . (string)$opts['ui_error_border'];
        $style_attr = implode(';', $style_vars);

        $html = '';

        if ($thanks) {
            $html .= '<div class="qec-msg qec-success' . ($msg_extra ? ' ' . esc_attr($msg_extra) : '') . '" style="' . esc_attr($style_attr) . '"><strong>' . esc_html__('Success', 'quick-email-capture') . '</strong> — ' . esc_html($opts['success_message']) . '</div>';
        }

        if ($err !== '') {
            $html .= '<div class="qec-msg qec-error' . ($msg_extra ? ' ' . esc_attr($msg_extra) : '') . '" style="' . esc_attr($style_attr) . '"><strong>' . esc_html__('Error', 'quick-email-capture') . ':</strong> ' . esc_html($err) . '</div>';
        }

        $action = esc_url(admin_url('admin-post.php'));

        $html .= '<form class="qec-form' . ($wrapper_extra ? ' ' . esc_attr($wrapper_extra) : '') . '" method="post" action="' . $action . '" novalidate style="' . esc_attr($style_attr) . '">';
        $html .= wp_nonce_field('qec_submit', 'qec_nonce', true, false);

        $html .= '<input type="hidden" name="action" value="qec_submit">';

        $html .= '<label for="qec_name">' . esc_html($opts['name_label']) . '</label>';
        $html .= '<input class="' . esc_attr($input_extra) . '" id="qec_name" name="qec_name" type="text" autocomplete="name" maxlength="150" required aria-required="true" placeholder="' . esc_attr($opts['name_placeholder']) . '">';

        $html .= '<label for="qec_email">' . esc_html($opts['email_label']) . '</label>';
        $html .= '<input class="' . esc_attr($input_extra) . '" id="qec_email" name="qec_email" type="email" autocomplete="email" maxlength="191" required aria-required="true" placeholder="' . esc_attr($opts['email_placeholder']) . '">';

        if ((int)$opts['consent_required'] === 1) {
            $html .= '<div class="qec-consent">';
            $html .= '<input id="qec_consent" name="qec_consent" type="checkbox" value="1" required aria-required="true">';
            $html .= '<label for="qec_consent">' . esc_html($opts['consent_label']) . '</label>';
            $html .= '</div>';
        } else {
            $html .= '<input type="hidden" name="qec_consent" value="1">';
        }

        $html .= '<div class="qec-hp" aria-hidden="true">';
        $html .= '<label for="qec_hp">' . esc_html__('Leave this field empty', 'quick-email-capture') . '</label>';
        $html .= '<input id="qec_hp" name="qec_hp" type="text" autocomplete="off">';
        $html .= '</div>';

        $html .= '<input type="hidden" name="qec_ts" value="' . esc_attr(time()) . '">';
        $html .= '<button class="' . esc_attr($button_extra) . '" type="submit">' . esc_html($opts['button_label']) . '</button>';
        $html .= '</form>';

        return $html;
    }

    private static function handle_submit($opts)
    {
        $nonce = isset($_POST['qec_nonce']) ? sanitize_text_field(wp_unslash($_POST['qec_nonce'])) : '';
        if (!wp_verify_nonce($nonce, 'qec_submit')) return $opts['error_invalid'];

        $hp = isset($_POST['qec_hp']) ? sanitize_text_field(wp_unslash($_POST['qec_hp'])) : '';
        if ($hp !== '') return $opts['error_invalid'];

        $ts = isset($_POST['qec_ts']) ? absint(wp_unslash($_POST['qec_ts'])) : 0;
        $min = max(0, (int)$opts['min_submit_seconds']);
        if ($min > 0 && (time() - $ts) < $min) return $opts['error_wait'];

        $name = isset($_POST['qec_name']) ? sanitize_text_field(wp_unslash($_POST['qec_name'])) : '';
        $email = isset($_POST['qec_email']) ? sanitize_email(wp_unslash($_POST['qec_email'])) : '';
        $consent_raw = isset($_POST['qec_consent']) ? sanitize_text_field(wp_unslash($_POST['qec_consent'])) : '';
        $consent = ($consent_raw === '1');

        if ($name === '' || !is_email($email)) return $opts['error_required'];
        if ((int)$opts['consent_required'] === 1 && !$consent) return $opts['error_consent'];

        $ip = self::ip();
        $rate_seconds = max(0, (int)$opts['rate_limit_seconds']);
        if ($rate_seconds > 0) {
            $rate_key = 'qec_rate_' . md5(($ip !== '' ? $ip : '') . '|' . $email);
            if (get_transient($rate_key)) return $opts['error_wait'];
            set_transient($rate_key, 1, $rate_seconds);
        }

        if (self::email_exists($email)) return '';

        $now_gmt = current_time('mysql', true);

        $pid = wp_insert_post([
            'post_type' => QEC_CPT::POST_TYPE,
            'post_status' => 'private',
            'post_title' => $email,
            'post_date_gmt' => $now_gmt,
            'post_date' => get_date_from_gmt($now_gmt),
        ], true);

        if (is_wp_error($pid)) return $opts['error_invalid'];

        update_post_meta($pid, '_qec_name', $name);
        update_post_meta($pid, '_qec_email', $email);
        update_post_meta($pid, '_qec_consent', $consent ? 1 : 0);
        update_post_meta($pid, '_qec_created_gmt', $now_gmt);
        update_post_meta($pid, '_qec_source_url', self::current_url());

        $ref = isset($_SERVER['HTTP_REFERER']) ? esc_url_raw(wp_unslash($_SERVER['HTTP_REFERER'])) : '';
        update_post_meta($pid, '_qec_source_ref', $ref);

        if ((int)$opts['store_ip'] === 1) update_post_meta($pid, '_qec_ip', $ip);

        if ((int)$opts['store_user_agent'] === 1) {
            $ua = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])) : '';
            if ($ua !== '') update_post_meta($pid, '_qec_ua', substr($ua, 0, 255));
        }

        return '';
    }

    private static function email_exists($email)
    {
        global $wpdb;

        $cache_key = 'qec_email_exists_' . md5((string)$email);
        $cached = wp_cache_get($cache_key, 'qec');
        if ($cached !== false) return (bool)$cached;

        $found = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT 1
                 FROM {$wpdb->posts} p
                 INNER JOIN {$wpdb->postmeta} pm
                    ON pm.post_id = p.ID
                    AND pm.meta_key = %s
                    AND pm.meta_value = %s
                 WHERE p.post_type = %s
                 LIMIT 1",
                '_qec_email',
                $email,
                QEC_CPT::POST_TYPE
            )
        );

        $exists = !empty($found);
        wp_cache_set($cache_key, $exists ? 1 : 0, 'qec', 300);
        return $exists;
    }

    private static function current_url()
    {
        $uri = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])) : '/';
        $uri = preg_replace('/[\r\n]/', '', $uri);
        $uri = '/' . ltrim($uri, '/');
        return esc_url_raw(home_url($uri));
    }

    private static function ip()
    {
        $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';
        return $ip;
    }

    private static function set_cookie($name, $value)
    {
        $value = sanitize_text_field((string)$value);
        $secure = is_ssl();
        $path = defined('COOKIEPATH') ? COOKIEPATH : '/';
        setcookie($name, $value, [
            'expires' => time() + 120,
            'path' => $path,
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        $_COOKIE[$name] = $value;
    }

    private static function get_cookie($name)
    {
        if (!isset($_COOKIE[$name])) return '';
        return sanitize_text_field(wp_unslash($_COOKIE[$name]));
    }

    private static function clear_cookie($name)
    {
        $secure = is_ssl();
        $path = defined('COOKIEPATH') ? COOKIEPATH : '/';
        setcookie($name, '', [
            'expires' => time() - 3600,
            'path' => $path,
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        unset($_COOKIE[$name]);
    }
}
