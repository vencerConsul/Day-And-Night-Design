<?php
/**
 * Plugin Name: Day and Night Design
 * Plugin URI: https://github.com/vencerConsul/Day-and-night-plugin
 * Description: Switches the bullshit page base on shit time
 * Version: 1.1
 * Author: Vencer Olermo
 * Author URI: https://github.com/vencerConsul
 * License: GPL2
 */
// Set the timezone based on the user's location
$url = "http://ip-api.com/json/";
$json = file_get_contents($url);
$data = json_decode($json, true);
$timezone = $data['timezone'];
date_default_timezone_set($timezone);

function set_homepage_based_on_time() {
    $is_enabled = get_option('set_homepage_enabled', true);
    
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
    
    $hour = date('H');
    if ($hour >= 6 && $hour < 18) {
        $daytime_page = get_page_by_title(get_option('daytime_homepage_title', 'Daytime Page'));
        if ($daytime_page) {
            update_option('show_on_front', 'page');
            update_option('page_on_front', $daytime_page->ID);
        }
    } else {
        $nighttime_page = get_page_by_title(get_option('nighttime_homepage_title', 'Nighttime Page'));
        if ($nighttime_page) {
            update_option('show_on_front', 'page');
            update_option('page_on_front', $nighttime_page->ID);
        }
    }
}

add_action('wp', 'set_homepage_based_on_time');

function save_default_front_page() {
    $default_front_page = get_option('page_on_front', 0);
    update_option('default_front_page', $default_front_page);
}

register_activation_hook(__FILE__, 'save_default_front_page');


function day_and_night_settings_page() {
    if (isset($_POST['daytime_homepage_title'])) {
        update_option('daytime_homepage_title', $_POST['daytime_homepage_title']);
    }
    if (isset($_POST['nighttime_homepage_title'])) {
        update_option('nighttime_homepage_title', $_POST['nighttime_homepage_title']);
    }
    if (isset($_POST['set_homepage_enabled'])) {
        $is_enabled = ($_POST['set_homepage_enabled'] == 'true');
        update_option('set_homepage_enabled', $is_enabled);
    }
    $daytime_homepage_title = get_option('daytime_homepage_title');
    $nighttime_homepage_title = get_option('nighttime_homepage_title');
    $is_enabled = get_option('set_homepage_enabled', true);
    ?>
    <style>
        .__venz_plugin_banner{
            width: 100%;
            border-radius: 20px;
        }
        .wrap{
            max-width: 100%;
            background: #ffffff;
            padding: 50px;
            border-radius: 20px;
        }
        .__venz_plugin_title{
            margin-bottom: 30px !important;
        }
    </style>
    <div class="wrap">
        <h1 class="__venz_plugin_title">Setup Homepage Settings</h1>
        <div class="__venz_plugin_image">
            <img class="__venz_plugin_banner" src="<?php echo plugins_url( 'day-and-night.png', __FILE__ ); ?>" alt="the bullshit">
        </div>
        <p class="__venz_plugin_description">
        The Day and Night Design plugin is a useful tool for WordPress users who want to create a dynamic and engaging website design that changes with the time of day. With this plugin, users can create a distinct design for both daytime and nighttime, which will be automatically switched based on the time of the day. During the day, the website can be displayed with bright, bold colors, while at night, a more muted and darker color palette can be used to enhance the user's experience. This plugin is perfect for businesses that have different audiences at different times of the day, such as restaurants or nightclubs. Overall, the Day and Night Design plugin provides an easy and effective way to create a unique and engaging website design that stands out from the crowd.
        </p>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="daytime_homepage_title">Daytime Page</label></th>
                    <td>
                        <select name="daytime_homepage_title" id="daytime_homepage_title">
                            <?php
                            $pages = get_pages();
                            foreach ($pages as $page) {
                                $selected = ($daytime_homepage_title == $page->post_title) ? 'selected' : '';
                                echo '<option value="' . $page->post_title . '" ' . $selected . '>' . $page->post_title . '</option>';
                            }
                            ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="nighttime_homepage_title">Nighttime Page</label></th>
                    <td>
                        <select name="nighttime_homepage_title" id="nighttime_homepage_title">
                            <?php
                            foreach ($pages as $page) {
                                $selected = ($nighttime_homepage_title == $page->post_title) ? 'selected' : '';
                                echo '<option value="' . $page->post_title . '" ' . $selected . '>' . $page->post_title . '</option>';
                            }
                            ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="set_homepage_enabled">Enable Day and Night Design</label></th>
                    <td>
                        <input type="hidden" name="set_homepage_enabled" value="false" <?php checked($is_enabled, false); ?>>
                        <input type="checkbox" name="set_homepage_enabled" value="true" <?php checked($is_enabled, true); ?>>
                </td>
            </tr>
        </table>
        <p>If the Day and Night Design feature is disabled, the default front page will be displayed</p>
        <?php submit_button(); ?>
    </form>
</div>
<?php

}

function add_homepage_settings_submenu_page() {
add_submenu_page(
    'options-general.php',
    'Day and Night Design',
    'Day and Night',
    'manage_options',
    'day-and-night-settings',
    'day_and_night_settings_page'
    );
}

add_action('admin_menu', 'add_homepage_settings_submenu_page');