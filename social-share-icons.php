<?php

/**
 * Plugin Name: Social Share Icons
 * Description: A plugin to add social share icons to posts and provide a shortcode for adding icons.
 * Version: 1.0
 * Author: Daniel Correa Placeres
 * License: GPL2+
 */

// Register activation hook
register_activation_hook( __FILE__, 'ssi_plugin_activate' );

// Register deactivation hook
register_deactivation_hook( __FILE__, 'ssi_plugin_deactivate' );

// Enqueue Font Awesome stylesheet
function ssi_enqueue_styles() {
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css');
    wp_enqueue_style('ssi-style', plugins_url('css/style.css', __FILE__));
}
add_action('wp_enqueue_scripts', 'ssi_enqueue_styles');

// Add settings page to admin menu
function ssi_add_settings_page() {
    add_options_page(
        'Social Share Icons Settings',    // Página del título
        'Social Share Icons',            // Menú del título
        'manage_options',                // Capacidad requerida
        'ssi-settings',                  // ID de página
        'ssi_render_settings_page'       // Función para renderizar la página
    );
}
add_action('admin_menu', 'ssi_add_settings_page');

// Render settings page
function ssi_render_settings_page() {
    ?>
    <div class="wrap">
        <h1>Social Share Icons Settings</h1>
        <form method="post" action="options.php">
            <?php settings_fields('ssi_settings_group'); ?>
            <?php do_settings_sections('ssi-settings'); ?>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// Register settings and fields
function ssi_register_settings() {
    register_setting(
        'ssi_settings_group',           // Opciones de grupo
        'ssi_settings',                 // Opciones de nombre
        'ssi_sanitize_settings'         // Función de sanitización
    );

    add_settings_section(
        'ssi_general_section',          // ID de sección
        'General Settings',             // Título de sección
        'ssi_general_section_callback', // Callback para la sección
        'ssi-settings'                  // Página en la que aparecerá la sección
    );

    add_settings_field(
        'ssi_display_location',         // ID del campo
        'Display Location',             // Título del campo
        'ssi_display_location_callback',// Callback para el campo
        'ssi-settings',                 // Página en la que aparecerá el campo
        'ssi_general_section'           // Sección a la que pertenece el campo
    );

    add_settings_field(
        'ssi_custom_text',              // ID del campo
        'Custom Text',                  // Título del campo
        'ssi_custom_text_callback',     // Callback para el campo
        'ssi-settings',                 // Página en la que aparecerá el campo
        'ssi_general_section'           // Sección a la que pertenece el campo
    );

    add_settings_field(
        'ssi_icon_colors',              // ID del campo
        'Icon Colors',                  // Título del campo
        'ssi_icon_colors_callback',     // Callback para el campo
        'ssi-settings',                 // Página en la que aparecerá el campo
        'ssi_general_section'           // Sección a la que pertenece el campo
    );
}
add_action('admin_init', 'ssi_register_settings');

// Callback para la sección general
function ssi_general_section_callback() {
    echo 'Configure general settings for Social Share Icons:';
}

// Callback para el campo de ubicación de visualización
function ssi_display_location_callback() {
    $options = get_option('ssi_settings');
    $display_location = isset($options['display_location']) ? $options['display_location'] : 'below_content';

    $locations = array(
        'below_content' => 'Below Content',
        'above_content' => 'Above Content',
        'shortcode' => 'Shortcode Only',
    );

    echo '<select id="ssi_display_location" name="ssi_settings[display_location]">';
    foreach ($locations as $key => $label) {
        echo '<option value="' . esc_attr($key) . '" ' . selected($display_location, $key, false) . '>' . esc_html($label) . '</option>';
    }
    echo '</select>';
}

// Callback para el campo de texto personalizado
function ssi_custom_text_callback() {
    $options = get_option('ssi_settings');
    $custom_text = isset($options['custom_text']) ? $options['custom_text'] : '';

    echo '<input type="text" id="ssi_custom_text" name="ssi_settings[custom_text]" value="' . esc_attr($custom_text) . '" />';
}

// Callback para el campo de colores de iconos
function ssi_icon_colors_callback() {
    $options = get_option('ssi_settings');
    $icon_color = isset($options['icon_color']) ? $options['icon_color'] : '#000000';
    $hover_color = isset($options['hover_color']) ? $options['hover_color'] : '#ffffff';

    echo '<p>Icon Color: <input type="color" id="ssi_icon_color" name="ssi_settings[icon_color]" value="' . esc_attr($icon_color) . '" /></p>';
    echo '<p>Hover Color: <input type="color" id="ssi_hover_color" name="ssi_settings[hover_color]" value="' . esc_attr($hover_color) . '" /></p>';
}

// Sanitización de opciones
function ssi_sanitize_settings($input) {
    $sanitized_input = array();

    if (isset($input['display_location'])) {
        $sanitized_input['display_location'] = sanitize_text_field($input['display_location']);
    }

    if (isset($input['custom_text'])) {
        $sanitized_input['custom_text'] = sanitize_text_field($input['custom_text']);
    }

    if (isset($input['icon_color'])) {
        $sanitized_input['icon_color'] = sanitize_hex_color($input['icon_color']);
    }

    if (isset($input['hover_color'])) {
        $sanitized_input['hover_color'] = sanitize_hex_color($input['hover_color']);
    }

    return $sanitized_input;
}

// Add social share icons based on settings
function ssi_add_social_icons($content) {
    if (is_singular('post')) {
        $options = get_option('ssi_settings');
        $display_location = isset($options['display_location']) ? $options['display_location'] : 'below_content';

        if ($display_location === 'above_content') {
            $social_icons = ssi_generate_social_icons();
            $content = $social_icons . $content;
        } elseif ($display_location === 'below_content') {
            $content .= ssi_generate_social_icons();
        }
    }

    return $content;
}
add_filter('the_content', 'ssi_add_social_icons');

// Function to generate social icons based on settings
function ssi_generate_social_icons() {
    $options = get_option('ssi_settings');
    $custom_text = isset($options['custom_text']) ? $options['custom_text'] : 'Share:';
    $icon_color = isset($options['icon_color']) ? $options['icon_color'] : '#000000';
    $hover_color = isset($options['hover_color']) ? $options['hover_color'] : '#ffffff';

    $post_title = get_the_title();
    $post_permalink = get_permalink();

    $social_icons = '<div class="ssi-social-icons">';
    $social_icons .= '<span class="ssi-custom-text">' . esc_html($custom_text) . ' </span>';

    // Facebook
    $social_icons .= '<a class="ssi-icon ssi-facebook" style="color: ' . esc_attr($icon_color) . ';" onmouseover="this.style.color=\''. esc_attr($hover_color) .'\'" onmouseout="this.style.color=\''. esc_attr($icon_color) .'\'" href="https://www.facebook.com/sharer/sharer.php?u=' . esc_url($post_permalink) . '" target="_blank"><i class="fab fa-facebook-f" ></i></a>';

    // Twitter
    $social_icons .= '<a class="ssi-icon ssi-twitter" style="color: ' . esc_attr($icon_color) . ';" onmouseover="this.style.color=\''. esc_attr($hover_color) .'\'" onmouseout="this.style.color=\''. esc_attr($icon_color) .'\'" href="https://twitter.com/intent/tweet?url=' . esc_url($post_permalink) . '&text=' . rawurlencode($post_title) . '" target="_blank"><i class="fab fa-twitter" ></i></a>';

    // LinkedIn
    $social_icons .= '<a class="ssi-icon ssi-linkedin" style="color: ' . esc_attr($icon_color) . ';" onmouseover="this.style.color=\''. esc_attr($hover_color) .'\'" onmouseout="this.style.color=\''. esc_attr($icon_color) .'\'" href="https://www.linkedin.com/sharing/share-offsite/?url=' . esc_url($post_permalink) . '" target="_blank"><i class="fab fa-linkedin-in" ></i></a>';

    $social_icons .= '</div>';

    return $social_icons;
}

// Activation function
function ssi_plugin_activate() {
    // Flush rewrite rules to ensure the shortcode is registered correctly
    flush_rewrite_rules();
}

// Deactivation function
function ssi_plugin_deactivate() {
    // Flush rewrite rules to clean up after deactivation
    flush_rewrite_rules();
}