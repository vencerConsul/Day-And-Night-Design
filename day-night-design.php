<?php

/**
 * Plugin Name: Day and Night Design
 * Plugin URI: https://github.com/vencerConsul/Day-and-night-plugin
 * Description: Day and Night Design is a WordPress plugin that allows website owners to easily create a Night Design for their website.
 * Version: 1.0.0
 * Author: Venz
 * Author URI: https://github.com/vencerConsul
 * License: GPL2
 */
if (!defined('ABSPATH')) {
    echo 'Dont';
    exit;
}


class DayAndNightDesign
{

    public function __construct()
    {
        add_action('admin_enqueue_scripts', array($this, 'load_assets'));
        $this->loadAndCheckTimeZone();
        add_action('wp', array($this, 'set_homepage_based_on_time'));
        register_activation_hook(__FILE__, array($this, 'save_default_front_page'));
        add_action('admin_menu', array($this, 'add_homepage_settings_submenu_page'));
        add_action('add_meta_boxes', array($this, 'custom_meta_box'));
        add_action('save_post', array($this, 'save_custom_field'));
    }


    public function loadAndCheckTimeZone()
    {
        $url = "http://ip-api.com/json/";
        $json = file_get_contents($url);
        $data = json_decode($json, true);
        $timezone = $data['timezone'];
        date_default_timezone_set($timezone);
    }

    public function load_assets()
    {
        wp_enqueue_style('day-and-night-design-styles', plugin_dir_url(__FILE__) . 'assets/css/day-night-style.css');
        wp_enqueue_script('day-and-night-design-styles', plugin_dir_url(__FILE__) . 'assets/js/day-night-scripts.js');
    }

    public function add_homepage_settings_submenu_page()
    {
        add_submenu_page(
            'options-general.php',
            'Day and Night Design',
            'Day and Night',
            'manage_options',
            'day-and-night-settings',
            array($this, 'day_and_night_settings_page')
        );
    }

    // Add meta box to page editor screen
    function custom_meta_box()
    {
        add_meta_box(
            'custom_field_meta_box',
            'Choose Design Mode',
            array($this, 'custom_field_callback'),
            'page',
            'side',
            'high'
        );
    }

    // Callback function to display the custom field meta box
    function custom_field_callback($post)
    {
        $value = get_post_meta($post->ID, '_custom_field', true);
        echo '<select id="custom_field" name="custom_field">';
        echo '<option value="">Select Mode</option>';
        echo '<option value="day"' . selected($value, 'day', false) . '>Day</option>';
        echo '<option value="night"' . selected($value, 'night', false) . '>Night</option>';
        echo '</select>';
    }

    // Save the value of the custom field
    function save_custom_field($post_id)
    {
        if (isset($_POST['custom_field'])) {
            update_post_meta($post_id, '_custom_field', sanitize_text_field($_POST['custom_field']));
        }
    }

    public function set_homepage_based_on_time()
    {
        $is_enabled = get_option('set_homepage_enabled', true);
        $timeFrom = get_option('timeFrom');
        $timeTo = get_option('timeTo');

        if (!$is_enabled) {
            $default_front_page = get_option('default_front_page', 0);
            if ($default_front_page) {
                update_option('show_on_front', 'page');
                update_option('page_on_front', $default_front_page);
            } else {
                update_option('show_on_front', 'posts');
                update_option('page_on_front', 0);
            }
            return;
        }

        $hour = date('H:i');
        $daytime_start = $timeFrom;
        $daytime_end = $timeTo;
        $currentTime = DateTime::createFromFormat('H:i', $hour);
        $start = DateTime::createFromFormat('H:i', $daytime_start);
        $end = DateTime::createFromFormat('H:i', $daytime_end);

        if ($currentTime > $start && $currentTime < $end) {
            $nighttime_page = get_page_by_title(get_option('nighttime_homepage_title', 'Nighttime Page'));
            if ($nighttime_page) {
                update_option('show_on_front', 'page');
                update_option('page_on_front', $nighttime_page->ID);
                add_action('template_redirect', array($this, 'redirect_to_night_page'), 10, 1);
                $this->redirect_to_night_page('night');
            }
        } else {
            $daytime_page = get_page_by_title(get_option('daytime_homepage_title', 'Daytime Page'));
            if ($daytime_page) {
                update_option('show_on_front', 'page');
                update_option('page_on_front', $daytime_page->ID);
                add_action('template_redirect', array($this, 'redirect_to_night_page'), 10, 1);
                $this->redirect_to_night_page('day');
            }
        }
    }



    public function save_default_front_page()
    {
        $default_front_page = get_option('page_on_front', 0);
        update_option('default_front_page', $default_front_page);
    }


    function redirect_to_night_page($mode)
    {
        $is_enabled = get_option('set_homepage_enabled', true);
        if ($is_enabled) {
            $page_id = get_the_ID();
            // var_dump(get_post_meta($page_id, 'pageDay', true));
            // var_dump(get_post_meta($page_id, 'pageNight', true));
            // Check if the current page has a custom field of 'day'
            if ($mode == 'night') {
                // Get the night page ID for the current day page
                $night_page_id = get_post_meta($page_id, 'pageNight', true);
                if ($night_page_id) {
                    // Redirect to the night page
                    wp_redirect(get_permalink($night_page_id), 301);
                    exit;
                }
            }
        }
    }


    function day_and_night_settings_page()
    {
        if (isset($_POST['daytime_homepage_title'])) {
            update_option('daytime_homepage_title', sanitize_text_field($_POST['daytime_homepage_title']));
        }
        if (isset($_POST['nighttime_homepage_title'])) {
            update_option('nighttime_homepage_title', sanitize_text_field($_POST['nighttime_homepage_title']));
        }
        if (isset($_POST['timeFrom']) && isset($_POST['timeTo'])) {
            $from = sanitize_text_field($_POST['timeFrom']);
            $to = sanitize_text_field($_POST['timeTo']);

            if (empty($from)) {
                add_settings_error('timeFrom', 'timeFrom_error', 'From field is required.', 'error');
            } elseif (empty($to)) {
                add_settings_error('timeTo', 'timeFrom_error', 'To field is required.', 'error');
            } elseif (!empty($from) && !empty($to)) {
                update_option('timeFrom', $from);
                update_option('timeTo', $to);
            }
        }
        if (isset($_POST['set_homepage_enabled'])) {
            $is_enabled = ($_POST['set_homepage_enabled'] == 'true');
            update_option('set_homepage_enabled', $is_enabled);
            if ($is_enabled) {
                add_settings_error(
                    'set_homepage_enabled',
                    'set_homepage_enabled_success',
                    'Day and Night Design is Fucking on.',
                    'updated'
                );
            } else {
                add_settings_error(
                    'set_homepage_enabled',
                    'set_homepage_enabled_success',
                    'Day and Night Design is Fucking off.',
                    'warning'
                );
            }
        }

        $daytime_homepage_title = get_option('daytime_homepage_title');
        $nighttime_homepage_title = get_option('nighttime_homepage_title');
        $is_enabled = get_option('set_homepage_enabled', true);
        $timeFrom = get_option('timeFrom');
        $timeTo = get_option('timeTo');

        $args = array(
            'post_type' => 'page',
            'meta_query' => array(
                array(
                    'key' => '_custom_field',
                    'value' => 'day'
                )
            )
        );
        $pagesDay = get_posts($args);

        $args2 = array(
            'post_type' => 'page',
            'meta_query' => array(
                array(
                    'key' => '_custom_field',
                    'value' => 'night'
                )
            )
        );
        $pagesNight = get_posts($args2);

        if (isset($_POST['pageNight'])) {
            foreach ($pagesDay as $pageDay) {
                if (isset($_POST['pageNight'][$pageDay->ID])) {
                    update_post_meta($pageDay->ID, 'pageNight', $_POST['pageNight'][$pageDay->ID]);
                    update_post_meta($_POST['pageNight'][$pageDay->ID], 'pageDay', $pageDay->ID);
                }
            }
        }

?>
        <!-- Head -->
        <div id="v-head">
            <div class="v-head-logo">
                <img src="<?php echo plugins_url('/assets/images/logo.png', __FILE__); ?>" alt="logo">
            </div>
            <div class="v-head-toggle">
                <form id="switch" method="POST">
                    <label class="switch">
                        <input type="hidden" name="set_homepage_enabled" value="false" <?php checked($is_enabled, false); ?>>
                        <input type="checkbox" name="set_homepage_enabled" id="witch_input" value="true" <?php checked($is_enabled, true); ?>>
                        <span class="slider round"></span>
                    </label>
                </form>
            </div>
        </div>
        <!-- End Head -->

        <!-- Container -->
        <div class="v-container">
            <?= settings_errors() ?>
            <h3>Introduction</h3>
            <p class="v-description">
                The Day and Night Design allows website owners to create a dynamic website design that changes with the time of day. It automatically switches between a bright, bold color scheme for daytime and a darker, muted palette for nighttime. This is ideal for businesses with different audiences at different times of day, such as restaurants or nightclubs, and creates a unique and engaging website design.
            </p>
            <form method="POST">
                <div class="v-form">
                    <div class="v-form-group">
                        <h4>Set Night Time</h4>
                        <div class="v-form-wrapper">
                            <div class="v-form-fields">
                                <label for="setTimeFrom">From</label>
                                <input type="time" id="setTimeFrom" value="<?php echo (empty($timeFrom) ? '' : $timeFrom) ?>" name="timeFrom">
                            </div>
                            <div class="v-form-fields">
                                <label for="setTimeTo">To</label>
                                <input type="time" id="setTimeTo" value="<?php echo (empty($timeTo) ? '' : $timeTo) ?>" name="timeTo">
                            </div>
                        </div>
                    </div>
                    <div class="v-form-group">
                        <h4>Set Home page</h4>
                        <div class="v-form-wrapper">
                            <div class="v-form-fields">
                                <label for="dayTime">Day Time</label>
                                <select name="daytime_homepage_title" id="dayTime">
                                    <?php
                                    $pages = get_pages();
                                    foreach ($pages as $page) {
                                        $selected = ($daytime_homepage_title == $page->post_title) ? 'selected' : '';
                                        echo '<option value="' . $page->post_title . '" ' . $selected . '>' . $page->post_title . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="v-form-fields">
                                <label for="nightTime">Night Time</label>
                                <select name="nighttime_homepage_title" id="nightTime">
                                    <?php
                                    foreach ($pages as $page) {
                                        $selected = ($nighttime_homepage_title == $page->post_title) ? 'selected' : '';
                                        echo '<option value="' . $page->post_title . '" ' . $selected . '>' . $page->post_title . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <?php if (count($pagesDay) > 0) { ?>
                        <h4>Set Night Design for Inner Pages</h4>
                        <div class="v-form-pages">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Page with Day Design</th>
                                        <th>Select Page for Night Design</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pagesDay as $pageDay) { ?>
                                        <tr>
                                            <td>
                                                <p><?php echo $pageDay->post_title; ?></p>
                                            </td>
                                            <td>
                                                <select name="pageNight[<?php echo $pageDay->ID; ?>]">
                                                    <option value="">-- Select page --</option>
                                                    <?php foreach ($pagesNight as $pageNight) { ?>
                                                        <option value="<?php echo $pageNight->ID; ?>" <?php selected(get_post_meta($pageDay->ID, 'pageNight', true), $pageNight->ID); ?>><?php echo $pageNight->post_title; ?></option>
                                                    <?php } ?>
                                                </select>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    <?php } else { ?>
                        <h4>No current page with Design mode. Choose a Design mode for your pages</h4>
                        <p>Note: If you have a existing page, just navigate to the page you want to add a night design and choose a "Design mode" on the right side of your page editor</p>
                    <?php } ?>
                </div>
                <?php submit_button(); ?>
            </form>
        </div>


        <!-- End Container -->
<?php

    }
}

new DayAndNightDesign;
