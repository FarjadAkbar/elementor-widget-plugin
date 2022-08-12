<?php
/*
Plugin Name: Fa Widgets Spot For Elementor
Description: FA Spots for Elementor plugin includes widgets and addons like Blog Post Grid, Gallery, Post Carousel, Advanced Slider, Modal Popup, Google Maps, Pricing Tables, Lottie Animations, Countdown, Testimonials.
Plugin URI: https://www.fiverr.com/farjadakbar?public_mode=true
Version: 4.9.23
Elementor tested up to: 3.6.8
Elementor Pro tested up to: 3.7.3
Author: Farjad
Author URI: https://www.fiverr.com/farjadakbar?public_mode=true
Text Domain: fa-spot-for-elementor
Domain Path: /languages
License: GNU General Public License v3.0
*/

if (!defined('ABSPATH')) {
    exit; // No access of directly access.
}

// Define Constants.
define('FA_SPOT_VERSION', '4.9.23');
define('FA_SPOT_URL', plugins_url('/', __FILE__));
define('FA_SPOT_PATH', plugin_dir_path(__FILE__));
// define('FA_SPOT_PATH', set_url_scheme(wp_upload_dir()['basedir'] . '/premium-addons-elementor'));
// define('FA_SPOT_URL', set_url_scheme(wp_upload_dir()['baseurl'] . '/premium-addons-elementor'));
define('FA_SPOT_FILE', __FILE__);
define('FA_SPOT_BASENAME', plugin_basename(FA_SPOT_FILE));
define('FA_SPOT_STABLE_VERSION', '4.9.22');



function elementor_page_speed_context_menu_scripts()
{
    wp_enqueue_script(
        'elementor-storage',
        plugins_url('assets/js/xdlocalstorage.js', FA_SPOT_FILE),
        array('jquery'),
        '1.0.0',
        false
    );

    wp_enqueue_script(
        'elementor-page-speed-context-menu',
        plugins_url('assets/js/context-menu.js', FA_SPOT_FILE),
        array('jquery'),
        '1.0.0',
        false
    );
    wp_localize_script('elementor-page-speed-context-menu', 'fa_cross_cp', array('ajax_url' => admin_url('admin-ajax.php')));
}

add_action('elementor/editor/after_enqueue_scripts', 'elementor_page_speed_context_menu_scripts');


add_action('wp_ajax_fa_cross_cp_import', 'cross_cp_fetch_content_data');

function cross_cp_fetch_content_data()
{
    check_ajax_referer('fa_cross_cp_import', 'nonce');
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(
            __('Not a valid user', 'premium-addons-for-elementor'),
            403
        );
    }

    $media_import = isset($_POST['copy_content']) ? wp_unslash($_POST['copy_content']) : '';

    if (empty($media_import)) {
        wp_send_json_error(__('Empty Content.', 'premium-addons-for-elementor'));
    }

    $media_import = array(json_decode($media_import, true));
    $media_import = cross_cp_import_elements_ids($media_import);
    $media_import = cross_cp_import_copy_content($media_import);

    wp_send_json_success($media_import);
    // wp_send_json_success("Result");
}


function cross_cp_import_elements_ids($media_import)
{
    return \Elementor\Plugin::instance()->db->iterate_data(
        $media_import,
        function ($element) {
            $element['id'] = \Elementor\Utils::generate_random_string();
            return $element;
        }
    );
}

function cross_cp_import_copy_content($media_import)
{
    return \Elementor\Plugin::instance()->db->iterate_data(
        $media_import,
        function ($element_data) {
            $element = \Elementor\Plugin::instance()->elements_manager->create_element_instance($element_data);

            // If the widget/element isn't exist, like a plugin that creates a widget but deactivated.
            if (!$element) {
                return null;
            }

            return cross_cp_import_element($element);
        }
    );
}

function cross_cp_import_element(\Elementor\Controls_Stack $element)
{

    $element_data = $element->get_data();
    $method       = 'on_import';

    if (method_exists($element, $method)) {
        // TODO: Use the internal element data without parameters.
        $element_data = $element->{$method}($element_data);
    }

    foreach ($element->get_controls() as $control) {
        $control_class = \Elementor\Plugin::instance()->controls_manager->get_control($control['type']);

        // If the control isn't exist, like a plugin that creates the control but deactivated.
        if (!$control_class) {
            return $element_data;
        }

        if (method_exists($control_class, $method)) {

            if ('media' !== $control['type'] && 'hedia' !== $control['type'] && 'repeater' !== $control['type']) {
                $element_data['settings'][$control['name']] = $control_class->{$method}($element->get_settings($control['name']), $control);
            } elseif ('repeater' === $control['type']) {
                $element_data['settings'][$control['name']] = on_import_repeater($element->get_settings($control['name']), $control);
            } else {
                if (!empty($element_data['settings'][$control['name']]['url'])) {
                    $element_data['settings'][$control['name']] = on_import_media($element->get_settings($control['name']));
                }
            }
        }
    }

    return $element_data;
}

function on_import_media($settings)
{

    if (empty($settings['url']) || false != strpos($settings['url'], 'placeholder')) {
        return $settings;
    }

    $settings = \Elementor\Plugin::$instance->templates_manager->get_import_images_instance()->import($settings);

    return $settings;
}


function on_import_repeater($settings, $control_data = array())
{
    if (empty($settings) || empty($control_data['fields'])) {
        return $settings;
    }

    $method = 'on_import';

    foreach ($settings as &$item) {
        foreach ($control_data['fields'] as $field) {
            if (empty($field['name']) || empty($item[$field['name']])) {
                continue;
            }

            $control_obj = \Elementor\Plugin::$instance->controls_manager->get_control($field['type']);

            if (!$control_obj) {
                continue;
            }

            if (method_exists($control_obj, $method)) {
                if ('media' !== $field['type'] && 'hedia' !== $field['type']) {
                    $item[$field['name']] = $control_obj->{$method}($item[$field['name']], $field);
                } else {
                    if (!empty($item[$field['name']]['url'])) {
                        $item[$field['name']] = on_import_media($item[$field['name']]);
                    }
                }
            }
        }
    }

    return $settings;
}