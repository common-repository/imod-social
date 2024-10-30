<?php
/**
 * @package WordPress Social
 */
/**
 * Plugin Name: WordPress Social
 * Plugin URI: http://imoddigital.com/wordpress-plugins/
 * Description: Adds information to your 'Posts > All Posts' section to see how well your blog posts are performing.
 * Version: 1.0
 * Author: iMod Digital
 * Author URI: http://imoddigital.com/
 * License: Proprietary
 */

function imod_social_add_signal_columns($columns)
{
    $columns['signal_score'] = 'Social';
    return $columns;
}

add_filter('manage_posts_columns', 'imod_social_add_signal_columns');

function imod_social_show_signal_columns($name)
{
    if ($name != 'signal_score') {
        return;
    }
    global $post;
    $signal_score = get_post_meta($post->ID, 'isig:score', true);
    echo '<div aria-hidden="true" class="wpsocial-icon ' . imod_social_compute_colour($signal_score) . '"></div>';
}

add_action('manage_posts_custom_column', 'imod_social_show_signal_columns');

add_filter('manage_edit-post_sortable_columns', 'imod_social_sort_signal_columns');
function imod_social_sort_signal_columns($columns)
{
    $columns['signal_score'] = 'signal';
    return $columns;
}

add_action('pre_get_posts', 'imod_social_signal_orderby');
function imod_social_signal_orderby($query)
{
    if (!is_admin())
        return;

    $orderby = $query->get('orderby');

    if ($orderby == 'signal') {
        $query->set('meta_key', 'isig:metrics');
        $query->set('orderby', 'meta_value_num');
    }
}

function imod_social_compute_colour($score)
{
    if ($score == '') {
        return 'na';
    }
    $option_values = get_option('isig:scores');

    $ok_score = $option_values ? $option_values['ok_score'] : 10;
    $bad_score = $option_values ? $option_values['bad_score'] : 5;
    $good_score = $option_values ? $option_values['good_score'] : 15;


    if ($score >= $good_score) {
        return 'good';
    }
    if ($score >= $ok_score) {
        return 'ok';
    }
    return 'bad';
}

function imod_social_signals_activate()
{
    if (!wp_next_scheduled('signals_update_hook')) {
        wp_schedule_event(time(), 'daily', 'signals_update_hook');
    }

    wp_schedule_single_event(time() - 60, 'signals_update_hook'); // try to run immediately - 1 minute
}

register_activation_hook(__FILE__, 'imod_social_signals_activate');

function imod_social_signals_update($debug = false)
{
    $option_name = 'isig:update';
    $option_values = get_option($option_name);
    $amount_to_fetch = $option_values ? (int)$option_values['amount'] : 5;

    set_time_limit($amount_to_fetch * 7);
    global $wpdb;
    // prioritize unfetched posts
    $q = "SELECT id FROM $wpdb->posts 
WHERE $wpdb->posts.post_type = 'post' 
AND $wpdb->posts.post_status = 'publish'
AND $wpdb->posts.id NOT IN (select post_id from $wpdb->postmeta where $wpdb->postmeta.meta_key = 'isig:score')
ORDER BY post_date DESC 
LIMIT $amount_to_fetch";
    if ($debug) echo $q;
    $postids = $wpdb->get_col($q);
    if (count($postids) == 0) {  // all posts have their metrics - so now we update oldest retrieved metrics instead
        $minimum_period = time() - 172800; // 48 hours at least, before we update a social metric
        $q = "SELECT id FROM $wpdb->posts 
INNER JOIN $wpdb->postmeta ON $wpdb->posts.ID = $wpdb->postmeta.post_id
WHERE $wpdb->posts.post_type = 'post' 
AND $wpdb->posts.post_status = 'publish'
AND $wpdb->postmeta.meta_key = 'isig:time'
AND $wpdb->postmeta.meta_value <= $minimum_period
ORDER BY $wpdb->postmeta.meta_value 
LIMIT $amount_to_fetch";
        if ($debug) echo $q;
        $postids = $wpdb->get_col($q);
    }
    if ($debug) {
        echo 'Writing for postids:' . print_r($postids, true);
    }
    foreach ($postids as $id) {
        $permalink = get_the_permalink($id);
        $metrics = imod_social_get_social_metrics($permalink);
        $res = array_merge($metrics, array('slug' => md5($permalink),
        ));
        update_post_meta($id, 'isig:metrics', $res);
        $score = $res['g'] + $res['f'] + $res['p'] + $res['l'];

        update_post_meta($id, 'isig:score', $score);
        update_post_meta($id, 'isig:time', time());
    }
}

add_action('signals_update_hook', 'imod_social_signals_update');

function imod_social_get_social_metrics($url)
{
    require_once(__DIR__ . '/includes/Imod_SocialFetcher.php');

    return array(
        'f' => Imod_SocialFetcher::facebook($url),
        'g' => Imod_SocialFetcher::google($url),
        'l' => Imod_SocialFetcher::linkedIn($url),
        'p' => Imod_SocialFetcher::pinterest($url),
    );
}

function imod_social_signals_deactivate()
{
    wp_clear_scheduled_hook('signals_update_hook');
}

register_deactivation_hook(__FILE__, 'imod_social_signals_deactivate');

add_action('admin_menu', 'imod_social_post_performance_menu');

function imod_social_post_performance_menu()
{
    require_once __DIR__ . '/views/settings.php';
    add_menu_page('WordPress Social', 'Social', 'publish_posts',
        'wp-social', 'imod_social_post_performance_view',
        'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0idXRmLTgiPz48IURPQ1RZUEUgc3ZnIFBVQkxJQyAiLS8vVzNDLy9EVEQgU1ZHIDEuMS8vRU4iICJodHRwOi8vd3d3LnczLm9yZy9HcmFwaGljcy9TVkcvMS4xL0RURC9zdmcxMS5kdGQiPjxzdmcgdmVyc2lvbj0iMS4xIiBpZD0iTGF5ZXJfMSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB4bWxuczp4bGluaz0iaHR0cDovL3d3dy53My5vcmcvMTk5OS94bGluayIgeD0iMHB4IiB5PSIwcHgiIHdpZHRoPSI2NHB4IiBoZWlnaHQ9IjY0cHgiIHZpZXdCb3g9IjAgMCA2NCA2NCIgZW5hYmxlLWJhY2tncm91bmQ9Im5ldyAwIDAgNjQgNjQiIHhtbDpzcGFjZT0icHJlc2VydmUiPjxnIGlkPSJTRU9fQmVuY2htYXJrXzFfIj48cGF0aCBmaWxsLXJ1bGU9ImV2ZW5vZGQiIGNsaXAtcnVsZT0iZXZlbm9kZCIgZmlsbD0iIzYyNjM2NiIgZD0iTTQuOTQsNDcuMzAzaDMuMTk5djE0LjI3Mkg0Ljk0VjQ3LjMwM0w0Ljk0LDQ3LjMwM3ogTTMxLjk2NiwyMC43OTJsLTIuMDk0LTAuODY3YzAuMDEtMC4xNDIsMC4wMTUtMC4yODMsMC4wMTUtMC40MjdjMC0zLjUzMy0yLjg2NC02LjM5Ny02LjM5Ni02LjM5N2MtMy41MzIsMC02LjM5NywyLjg2NC02LjM5Nyw2LjM5N2MwLDAuODk2LDAuMTg1LDEuNzUsMC41MTksMi41MjJsLTguMjM1LDguMjM2Yy0wLjg1NC0wLjQyNC0xLjgxOS0wLjY2NC0yLjgzOC0wLjY2NGMtMy41MzIsMC02LjM5NiwyLjg2My02LjM5Niw2LjM5NmMwLDMuNTMyLDIuODY0LDYuMzk3LDYuMzk2LDYuMzk3czYuMzk2LTIuODY1LDYuMzk2LTYuMzk3YzAtMS4wMjEtMC4yMzctMS45ODItMC42NjMtMi44MzhsOC4wOC04LjA3OWMwLjkyNywwLjUyMywxLjk5OSwwLjgyMywzLjEzOCwwLjgyM2MxLjkyLDAsMy42NDMtMC44NDgsNC44MTUtMi4xODdsMy42NTksMS41MTZWMjAuNzkyTDMxLjk2NiwyMC43OTJ6IE0yMS44OTEsMzEuMDExaDMuMTk5djMwLjU2NGgtMy4xOTlWMzEuMDExTDIxLjg5MSwzMS4wMTF6IE02LjU0LDMyLjc5MmMxLjc2NywwLDMuMTk4LDEuNDMxLDMuMTk4LDMuMTk3cy0xLjQzMiwzLjE5OS0zLjE5OCwzLjE5OWMtMS43NjYsMC0zLjE5OC0xLjQzMy0zLjE5OC0zLjE5OVM0Ljc3MywzMi43OTIsNi41NCwzMi43OTJMNi41NCwzMi43OTJ6IE0yMy40OTEsMTYuM2MxLjc2NywwLDMuMTk4LDEuNDMxLDMuMTk4LDMuMTk4YzAsMS43NjYtMS40MzEsMy4xOTgtMy4xOTgsMy4xOThjLTEuNzY2LDAtMy4xOTgtMS40MzItMy4xOTgtMy4xOThDMjAuMjkzLDE3LjczMSwyMS43MjUsMTYuMywyMy40OTEsMTYuM3oiLz48cGF0aCBmaWxsLXJ1bGU9ImV2ZW5vZGQiIGNsaXAtcnVsZT0iZXZlbm9kZCIgZmlsbD0iIzQwNDA0MSIgZD0iTTMxLjk2NiwyNS4yMjR2LTQuNDMybDMuNjU3LDEuNTE2YzAuNjI3LTAuNzE5LDEuNDI3LTEuMzEsMi4zNjktMS43YzEuNzc1LTAuNzM0LDMuNjg5LTAuNjExLDUuMjg2LDAuMTc4bDguMjM1LTguMjM0Yy0wLjMzMy0wLjc3NC0wLjUxNy0xLjYyOC0wLjUxNy0yLjUyNGMwLTMuNTMxLDIuODYzLTYuMzk2LDYuMzk1LTYuMzk2YzMuNTMzLDAsNi4zOTYsMi44NjUsNi4zOTYsNi4zOTZjMCwzLjUzMi0yLjg2Myw2LjM5Ni02LjM5Niw2LjM5NmMtMS4xNCwwLTIuMjEtMC4zLTMuMTM5LTAuODIzbC04LjA4LDguMDgxYzAuMDYyLDAuMTI1LDAuMTIxLDAuMjU1LDAuMTc3LDAuMzg3YzAuMTYsMC4zOSwwLjI4MSwwLjc4NSwwLjM2LDEuMTgzYzAuMDgsMC4zODYsMC4xMiwwLjc3MiwwLjEyNiwxLjE1NXYwLjExMmMwLDIuNTA4LTEuNDg3LDQuODktMy45NSw1LjkwOGMtMy4yNjIsMS4zNTMtNy4wMDQtMC4xOTgtOC4zNTUtMy40NjFjLTAuMzkxLTAuOTQxLTAuNTQtMS45MjMtMC40NzItMi44NzZMMzEuOTY2LDI1LjIyNEwzMS45NjYsMjUuMjI0eiBNNTUuNzkxLDIxLjU0djQwLjAzNWgzLjE5OFYyMS41NEg1NS43OTFMNTUuNzkxLDIxLjU0eiBNMzguODQxLDM4LjAzMmgzLjE5N3YyMy41NDNoLTMuMTk3VjM4LjAzMkwzOC44NDEsMzguMDMyeiBNNDAuNDM5LDIzLjMyMWMtMS43NjYsMC0zLjE5OCwxLjQzMS0zLjE5OCwzLjE5OGMwLDEuNzY2LDEuNDMzLDMuMTk5LDMuMTk4LDMuMTk5YzEuNzY4LDAsMy4xOTgtMS40MzMsMy4xOTgtMy4xOTlDNDMuNjM4LDI0Ljc1Miw0Mi4yMDcsMjMuMzIxLDQwLjQzOSwyMy4zMjFMNDAuNDM5LDIzLjMyMXogTTU3LjM5Miw2LjgyOWMtMS43NjgsMC0zLjE5OCwxLjQzMS0zLjE5OCwzLjE5OWMwLDEuNzY3LDEuNDMxLDMuMTk4LDMuMTk4LDMuMTk4czMuMTk4LTEuNDMxLDMuMTk4LTMuMTk4QzYwLjU5LDguMjYsNTkuMTU5LDYuODI5LDU3LjM5Miw2LjgyOXoiLz48L2c+PC9zdmc+'
    );
    require_once __DIR__ . '/views/about.php';
    add_submenu_page('wp-social', 'About', 'About', 'publish_posts', 'about', 'imod_social_about_view');
    require_once __DIR__ . '/views/export.php';
    add_submenu_page('wp-social', 'Export', 'Export', 'publish_posts', 'export', 'imod_social_export_view');
    global $submenu;
    if (isset($submenu['wp-social'])) {
        $submenu['wp-social'][0][0] = 'Settings';
    }
}

add_action('admin_init', 'imod_social_signals_settings_init');
function imod_social_signals_settings_init()
{
    imod_social__score_settings();
    imod_social__update_settings();

    // export handler - we handle this here because we need to modify headers
    global $pagenow;
    if ($pagenow == 'admin.php' && $_GET['page'] == 'export' && !empty($_POST)) {
        wp_verify_nonce('isig_nounce', 'isig');
        require_once(__DIR__ . '/includes/Imod_SocialExporter.php');

        $mode = isset($_POST['status']) ? (int)$_POST['status'] : 0;
        Imod_SocialExporter::export($mode);
        die;
    }
}

function imod_social__score_settings()
{
    $option_name = 'isig:scores';
    $option_values = get_option($option_name);

    $default_values = array(
        'ok_score' => 10,
        'bad_score' => 5,
        'good_score' => 15,
    );

    $data = shortcode_atts($default_values, $option_values);

    register_setting(
        'isig:scores_group', // group, used for settings_fields()
        $option_name//,  // option name, used as key in database
    // 't5_sae_validate_option'      // validation callback
    );

    add_settings_section(
        'signals_scores_section',
        null,
        null,
        'score_tab'
    );

    add_settings_field(
        'good_score',
        "Minimum Score for 'Good'",
        'imod_social_signals_numeric_render',
        'score_tab',
        'signals_scores_section',
        array(
            'name' => 'good_score',
            'value' => esc_attr($data['good_score']),
            'option_name' => $option_name
        )
    );

    add_settings_field(
        'ok_score',
        "Minimum Score for 'Fair'",
        'imod_social_signals_numeric_render',
        'score_tab',
        'signals_scores_section',
        array(
            'name' => 'ok_score',
            'value' => esc_attr($data['ok_score']),
            'option_name' => $option_name
        )
    );

    add_settings_field(
        'bad_score',
        "Minimum Score for 'Bad'",
        'imod_social_signals_numeric_render',
        'score_tab',
        'signals_scores_section',
        array(
            'name' => 'bad_score',
            'value' => esc_attr($data['bad_score']),
            'option_name' => $option_name
        )
    );
}

const updates_group = 'isig:updates_group';

function imod_social__update_settings()
{
    $option_name = 'isig:update';
    $option_values = get_option($option_name);

    $data = shortcode_atts(array(
        'frequency' => 'daily',
        'amount' => 10,
    ), $option_values);

    register_setting(
        updates_group, // group, used for settings_fields()
        $option_name//,  // option name, used as key in database
    // 'validate_update_settings'      // validation callback
    );

    add_settings_section(
        'signals_updates_section',
        null,
        null,
        'updates_tab'
    );

    add_settings_field(
        'frequency',
        "Update Frequency",
        'imod_social_signals_select_render',
        'updates_tab',
        'signals_updates_section',
        array(
            'label_for' => 'imod_updated_freq',
            'name' => 'frequency',
            'value' => esc_attr($data['frequency']),
            'option_name' => $option_name,
            'options' => array(
                'daily' => 'Daily',
                'twicedaily' => 'Twice Daily',
                'hourly' => 'Hourly',
            ),
        )
    );

    add_settings_field(
        'amount',
        "Number of Posts",
        'imod_social_signals_select_render',
        'updates_tab',
        'signals_updates_section',
        array(
            'label_for' => 'imod_posts_number',
            'name' => 'amount',
            'value' => esc_attr($data['amount']),
            'option_name' => $option_name,
            'options' => array(
                5 => '5',
                10 => '10',
                15 => '15',
                20 => '20',
            ),
        )
    );
}

function imod_social_validate_update_settings($values)
{
    $defaults = array(
        'frequency' => 'daily',
        'amount' => 10,
    );
    if (!is_array($values))
        return $defaults;

    $out = array();

    foreach ($defaults as $key => $value) {

        if ($key == 'frequency') {
            if (!in_array($value, array('daily', 'twicedaily', 'hourly'))) {
                add_settings_error(
                    updates_group,
                    'invalid-frequency',
                    'Invalid frequency'
                );
                continue;
            }
        }
        $out[$key] = $value;
    }
    return $out;
}

add_filter('update_option_isig:update', function ($old, $new) {
    wp_clear_scheduled_hook('signals_update_hook');
    wp_schedule_event(time(), $new['frequency'], 'signals_update_hook');
}, 10, 2);

function imod_social_signals_numeric_render($args)
{
    printf(
        '<input name="%1$s[%2$s]" id="%3$s" value="%4$s" class="regular-text" type="number">',
        $args['option_name'],
        $args['name'],
        $args['label_for'],
        $args['value']
    );
}

function imod_social_signals_select_render($args)
{
    printf(
        '<select name="%1$s[%2$s]" id="%3$s">',
        $args['option_name'],
        $args['name'],
        $args['label_for']
    );
    foreach ($args['options'] as $val => $title)
        printf(
            '<option value="%1$s" %2$s>%3$s</option>',
            $val,
            selected($val, $args['value'], FALSE),
            $title
        );
    print '</select>';
}

function imod_social_add_settings_link($actions, $file)
{
    if (strpos($file, 'wordpress-social') !== false) {
        $actions['settings'] = '<a href="options-general.php?page=wp-social">Settings</a>';
    }
    return $actions;
}

add_filter('plugin_action_links', 'imod_social_add_settings_link', 2, 2);

function imod_social_add_wpsocial_assets($hook)
{
    if ($hook != 'edit.php' /*posts list*/ && $hook != 'toplevel_page_wp-social' && (strpos($hook, 'social_page') === false)) {
        return;
    }

    wp_enqueue_style('wpsocial_style', plugins_url('assets/style.css', __FILE__));
}

add_action('admin_enqueue_scripts', 'imod_social_add_wpsocial_assets');