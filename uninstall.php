<?php
if (!defined('WP_UNINSTALL_PLUGIN')) exit;

$posts = get_posts([
    'post_type' => 'qec_signup',
    'post_status' => 'any',
    'numberposts' => -1,
    'fields' => 'ids',
]);

foreach ($posts as $pid) {
    wp_delete_post($pid, true);
}

delete_option('qec_options');
wp_clear_scheduled_hook('qec_daily_purge');
