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
        add_action('admin_enqueue_scripts', array($this, 'loadAssets'));
        $this->loadAndCheckTimeZone();
        add_action('wp', array($this, 'setDayAndNightDesignActions'));
        register_activation_hook(__FILE__, array($this, 'saveDefaultHomePage'));
        add_action('admin_menu', array($this, 'addAdminSubMenuSettings'));
        add_action('add_meta_boxes', array($this, 'addCustomMetaBoxForEachPages'));
        add_action('save_post', array($this, 'saveMetaboxData'));
        add_filter('manage_pages_columns', array($this, 'custom_pages_columns'));
        add_action('manage_pages_custom_column', array($this, 'custom_pages_columns_content'), 10, 2);
        add_filter('body_class', array($this, 'addClass'));
    }

    function resetAllSettings()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'postmeta';
        $wpdb->delete($table_name, array('meta_key' => 'pageNight'), array('%s'));
        $wpdb->delete($table_name, array('meta_key' => 'pageDay'), array('%s'));
        $wpdb->delete($table_name, array('meta_key' => 'show_on_front'), array('%s'));
        $wpdb->delete($table_name, array('meta_key' => 'page_on_front'), array('%s'));
        delete_option('show_on_front');
        delete_option('page_on_front');
        delete_option('daytime_homepage_title');
        delete_option('nighttime_homepage_title');
        delete_option('timeFrom');
        delete_option('timeTo');

        add_settings_error(
            'reset',
            'reset_success',
            'Settings successfully reset.',
            'success'
        );
    }

    public function custom_pages_columns($columns)
    {
        $columns['custom_field'] = 'Design Mode';
        return $columns;
    }

    public function custom_pages_columns_content($column_name, $post_id)
    {
        if ($column_name == 'custom_field') {
            $custom_field_value = get_post_meta($post_id, '_custom_field', true);
            if (!empty($custom_field_value)) {
                if ($custom_field_value == 'night') {
                    echo '<small style="background: #1d2327; color: #ffffff; font-weight: 500; border-radius: 30px; padding: 1px; text-align: center; display: block; width: 50px !important;">' . strtoupper($custom_field_value) . '</small>';
                } else {
                    echo '<small style="background: #fdaf00; color: #ffffff; font-weight: 500; border-radius: 30px; padding: 1px; text-align: center; display: block; width: 50px !important;">' . strtoupper($custom_field_value) . '</small>';
                }
            } else {
                echo 'Choose Design mode';
            }
        }
    }

    public function loadAssets() // load asset files (css and javascript)
    {
        wp_enqueue_style('day-and-night-design-styles', plugin_dir_url(__FILE__) . 'assets/css/day-night-style.css');
        wp_enqueue_script('day-and-night-design-styles', plugin_dir_url(__FILE__) . 'assets/js/day-night-scripts.js');
    }

    public function loadAndCheckTimeZone() // get the current wordpress timezone
    {
        $timezone = get_option('timezone_string');
        if ($timezone) {
            date_default_timezone_set($timezone);
        }
    }

    public function addAdminSubMenuSettings() // add admin dashboard sub menu settings
    {
        add_submenu_page(
            'options-general.php',
            'Day and Night Design',
            'Day and Night',
            'manage_options',
            'day-and-night-settings',
            array($this, 'dayAndNightSettingsPage')
        );
    }

    function addCustomMetaBoxForEachPages() // adding meta box to all pages
    {
        add_meta_box(
            'custom_field_meta_box',
            'Choose Design Mode',
            array($this, 'metaBoxCallBack'),
            'page',
            'side',
            'high'
        );
    }

    function metaBoxCallBack($post) // Callback function to display the custom field meta box
    {
        $value = get_post_meta($post->ID, '_custom_field', true);
        echo '<select id="custom_field" name="custom_field">';
        echo '<option value="">Select Mode</option>';
        echo '<option value="day"' . selected($value, 'day', false) . '>Day</option>';
        echo '<option value="night"' . selected($value, 'night', false) . '>Night</option>';
        echo '</select>';
    }

    function saveMetaboxData($post_id) // save meta box field to database
    {
        if (isset($_POST['custom_field'])) {
            update_post_meta($post_id, '_custom_field', sanitize_text_field($_POST['custom_field']));
        }
    }

    public function saveDefaultHomePage() // save the default home page
    {
        $default_front_page = get_option('page_on_front', 0);
        update_option('default_front_page', $default_front_page);
    }

    function isTimeBetween($start_time, $end_time) // check time is in between start and end time parameters
    {
        $current_time = date('H:i');
        if ($end_time < $start_time) { // Check if the end time is before the start time (indicating an overnight time range)
            return ($current_time >= $start_time || $current_time <= $end_time);
        } else {
            return ($current_time >= $start_time && $current_time <= $end_time);
        }
    }

    public function isValidTimeFormat($f, $t) // check time if valid format
    {
        $from = sanitize_text_field($f);
        $to = sanitize_text_field($t);

        if (preg_match('/^([01][0-9]|2[0-3]):[0-5][0-9]$/', $from) && preg_match('/^([01][0-9]|2[0-3]):[0-5][0-9]$/', $to)) {
            return true;
        } else {
            return false;
        }
    }

    public function setDayAndNightDesignActions()
    {
        $isDayAndNightEnabled = get_option('set_homepage_enabled', true);
        $timeFrom = get_option('timeFrom');
        $timeTo = get_option('timeTo');

        if (!$isDayAndNightEnabled) {
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

        if ($isDayAndNightEnabled) {
            $page_id = get_the_ID();
            if ($this->isTimeBetween($timeFrom, $timeTo)) {
                $nighttime_page = get_page_by_title(get_option('nighttime_homepage_title', 'Nighttime Page'));
                if ($nighttime_page) {
                    update_option('show_on_front', 'page');
                    update_option('page_on_front', $nighttime_page->ID);

                    $night_page_id = get_post_meta($page_id, 'pageNight', true);
                    if ($night_page_id) {
                        wp_redirect(get_permalink($night_page_id), 301);
                        exit;
                    }
                    $this->addClass('v-mode-night');
                }
            } else {
                $daytime_page = get_page_by_title(get_option('daytime_homepage_title', 'Daytime Page'));
                if ($daytime_page) {
                    update_option('show_on_front', 'page');
                    update_option('page_on_front', $daytime_page->ID);

                    $day_page_id = get_post_meta($page_id, 'pageDay', true);
                    if ($day_page_id) {
                        wp_redirect(get_permalink($day_page_id), 301);
                        exit;
                    }
                    $this->addClass('v-mode-day');
                }
            }
        }
    }

    public function addClass($mode)
    {
        $classes[] = $mode;
        return $classes;
    }

    public function dayAndNightSettingsPage()
    {
        if (isset($_POST['set_homepage_enabled'])) { // toggle enable and disable switch
            $isDayAndNightEnabled = $_POST['set_homepage_enabled'];
            update_option('set_homepage_enabled', $isDayAndNightEnabled);
            if ($isDayAndNightEnabled) {
                add_settings_error(
                    'set_homepage_enabled',
                    'set_homepage_enabled_success',
                    'Day and Night Design enabled.',
                    'updated'
                );
            } else {
                add_settings_error(
                    'set_homepage_enabled',
                    'set_homepage_enabled_success',
                    'Day and Night Design disabled.',
                    'warning'
                );
            }
        }

        $dayArgs = array(
            'post_type' => 'page',
            'meta_query' => array(
                array(
                    'key' => '_custom_field',
                    'value' => 'day'
                )
            )
        );
        $pagesDay = get_posts($dayArgs);

        $nightArgs = array(
            'post_type' => 'page',
            'meta_query' => array(
                array(
                    'key' => '_custom_field',
                    'value' => 'night'
                )
            )
        );
        $pagesNight = get_posts($nightArgs);

        if (isset($_POST['save_settings'])) {

            if (empty($_POST['daytime_homepage_title']) || empty($_POST['nighttime_homepage_title'])) {
                add_settings_error('save_settings', 'save_settings_error', 'Day Time and Night Time field for homepage should not be empty.', 'error');
            }

            update_option('daytime_homepage_title', sanitize_text_field($_POST['daytime_homepage_title']));
            update_option('nighttime_homepage_title', sanitize_text_field($_POST['nighttime_homepage_title']));
            if (empty($_POST['timeFrom']) || empty($_POST['timeTo'])) {
                add_settings_error('save_settings', 'save_settings_error', 'Field From and To should not be empty.', 'error');
            }

            if ($this->isValidTimeFormat($_POST['timeFrom'], $_POST['timeTo'])) {
                update_option('timeFrom', $_POST['timeFrom']);
                update_option('timeTo', $_POST['timeTo']);
            } else {
                add_settings_error('save_settings', 'save_settings_error', 'Invalid time format.', 'error');
            }
            foreach ($pagesDay as $pageDay) {
                if (isset($_POST['pageNight'][$pageDay->ID])) {
                    update_post_meta($pageDay->ID, 'pageNight', sanitize_text_field($_POST['pageNight'][$pageDay->ID]));
                }
            }
            foreach ($pagesNight as $pageNight) {
                if (isset($_POST['pageDay'][$pageNight->ID])) {
                    update_post_meta($pageNight->ID, 'pageDay', sanitize_text_field($_POST['pageDay'][$pageNight->ID]));
                }
            }
            add_settings_error(
                'save_settings_enabled',
                'save_settings_enabled_success',
                'All Changes are saved successfully',
                'updated'
            );
        }

        if (isset($_POST['reset'])) {
            $this->resetAllSettings();
        }

        $daytime_homepage_title = get_option('daytime_homepage_title');
        $nighttime_homepage_title = get_option('nighttime_homepage_title');
        $isDayAndNightEnabled = get_option('set_homepage_enabled', true);
        $timeFrom = get_option('timeFrom');
        $timeTo = get_option('timeTo');

?>
        <style>
            .settings_page_day-and-night-settings #wpwrap {
                background: url(<?php echo plugins_url('/assets/images/day-and-night.jpg', __FILE__) ?>) no-repeat;
                background-position: right;
                background-size: cover;
            }
        </style>
        <!-- Head -->
        <div id="v-head">
            <div class="v-head-logo">
                <img src="<?php echo plugins_url('/assets/images/logo.png', __FILE__); ?>" alt="logo">
            </div>
            <div class="v-head-toggle">
                <form id="switch" method="POST">
                    <p><?php echo $isDayAndNightEnabled ? 'Enabled' : 'Disabled' ?> </p>
                    <label class="switch">
                        <input type="hidden" name="set_homepage_enabled" value="false" <?php checked($isDayAndNightEnabled, false); ?>>
                        <input type="checkbox" name="set_homepage_enabled" id="witch_input" value="true" <?php checked($isDayAndNightEnabled, true); ?>>
                        <span class="slider round"></span>
                    </label>
                </form>
                <button class="save_settings">Save Settings</button>
                <form method="POST">
                    <input type="submit" role="button" value="Reset Settings" name="reset" class="reset_settings">
                </form>
            </div>
        </div>
        <!-- End Head -->

        <!-- Container -->
        <div class="v-container">
            <?= settings_errors() ?>
            <h3 style="color:#ffffff;">Introduction</h3>
            <p class="v-description">
                The Day and Night Design allows website owners to create a dynamic website design that changes with the time of day. It automatically switches between a bright, bold color scheme for daytime and a darker, muted palette for nighttime. This is ideal for businesses with different audiences at different times of day, such as restaurants or nightclubs, and creates a unique and engaging website design.
            </p>
            <h4 style="color:#ffffff;font-size:20px;">Timezone: 
            <?php 
                $timezone = get_option('timezone_string');
                if ($timezone) {
                    echo "<small style='color:yellow;'>".$timezone."</small>";
                } else {
                    echo "<small style='color:yellow;'>WordPress timezone is not set. To set your Timezone click <a style='color:#ffffff;' href='".admin_url()."/options-general.php#timezone_string'>here</a></small>";
                }
            ?>
            </h4>
            <p style='color:#ffffff;'>Change Timezone <a style='color:#ffffff;' href='<?= admin_url() ?>/options-general.php#timezone_string'>here</a></small></p>
            <form method="POST" id="save_settings">
                <input type="hidden" name="save_settings">
                <div class="v-form">
                    <div class="v-form-flex">
                        <div class="v-form-group">
                            <h4>Set Night Time</h4>
                            <div class="v-form-wrapper">
                                <div class="v-form-fields">
                                    <label for="setTimeFrom">From</label>
                                    <select id="setTimeFrom" name="timeFrom">
                                        <option value="">-- Select time --</option>
                                        <?php
                                        $hourFrom = 1;
                                        $minFrom = 0;
                                        while ($hourFrom <= 24) {
                                            $timeF = date('h:i A', strtotime("$hourFrom:$minFrom"));
                                            $selectedFrom = $timeFrom == date('H:i', strtotime($timeF)) ? 'selected' : '';
                                            echo "<option value='" . date('H:i', strtotime($timeF)) . "' $selectedFrom>$timeF</option>";
                                            $hourFrom++;
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="v-form-fields">
                                    <label for="setTimeTo">To</label>
                                    <select id="setTimeTo" name="timeTo">
                                        <option value="">-- Select time --</option>
                                        <?php
                                        $hourTo = 1;
                                        $minTo = 0;
                                        while ($hourTo <= 24) {
                                            $timeT = date('h:i A', strtotime("$hourTo:$minTo"));
                                            $selectedTo = $timeTo == date('H:i', strtotime($timeT)) ? 'selected' : '';
                                            echo "<option value='" . date('H:i', strtotime($timeT)) . "' $selectedTo>$timeT</option>";
                                            $hourTo++;
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="v-form-group">
                            <h4>Set Home page</h4>
                            <div class="v-form-wrapper">
                                <div class="v-form-fields">
                                    <label for="dayTime">Day Time</label>
                                    <select name="daytime_homepage_title" id="dayTime">
                                        <option value="">-- Select page --</option>
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
                                        <option value="">-- Select page --</option>
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
                        <!-- <div class="v-form-group">
                            <h4>Add additional class</h3>
                            <input type="text" class="additional-class" placeholder="Add Class Name">
                        </div> -->
                    </div>
                    <?php if (count($pagesDay) > 0) { ?>
                        <!-- <hr> -->
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
                                                <input type="hidden" id="hiddenPageDay<?php echo $pageDay->ID ?>" value="<?php echo (empty(get_post_meta($pageDay->ID, 'pageNight', true)) ? '' : $pageDay->ID) ?>" name="pageDay[<?php echo (empty(get_post_meta($pageDay->ID, 'pageNight', true)) ? '' : get_post_meta($pageDay->ID, 'pageNight', true)) ?>]">
                                            </td>
                                            <td>
                                                <select name="pageNight[<?php echo $pageDay->ID; ?>]" class="selectPageNight">
                                                    <option value="">-- Select page --</option>
                                                    <?php foreach ($pagesNight as $pageNight) { ?>
                                                        <option data-day="<?php echo $pageDay->ID ?>" value="<?php echo $pageNight->ID; ?>" <?php selected(get_post_meta($pageDay->ID, 'pageNight', true), $pageNight->ID); ?>><?php echo $pageNight->post_title; ?></option>
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
                <!-- <?php submit_button(); ?> -->
            </form>
        </div>
        <!-- End Container -->

<?php

    }
}

new DayAndNightDesign;
