<?php
/*
Plugin Name: Favoritos para Directorio Comercial
Plugin URI: https://happystory.com.mx/favoritos-plugin
Description: Permite a los usuarios agregar proveedores a su lista de favoritos.
Version: 1.0
Author: Enrique Gallegos
Author URI: https://happystory.com.mx
License: GPL2
*/

// Evitar acceso directo
if (!defined('ABSPATH')) exit;

// Agregar botón de favoritos con shortcode
function favopadi_agregar_favorito_button($atts) {
    if (!is_user_logged_in()) return '';
    $user_id = get_current_user_id();
    $provider_id = get_the_ID();
    $favoritos = get_user_meta($user_id, 'favopadi_favoritos', true);
    $favoritos = is_array($favoritos) ? $favoritos : [];
    $is_favorito = in_array($provider_id, $favoritos);

    $nonce = wp_create_nonce('favopadi_favoritos_nonce');
    $button_text = $is_favorito ? 'Quitar de Favoritos' : 'Agregar a Favoritos';
    
    return "<button class='add-to-favorites' data-id='{$provider_id}' data-nonce='{$nonce}'>{$button_text}</button>";
}
add_shortcode('favopadi_boton_favoritos', 'favopadi_agregar_favorito_button');

// Guardar/Quitar favoritos vía AJAX
add_action('wp_ajax_favopadi_toggle_favorite', 'favopadi_toggle_favorite');
function favopadi_toggle_favorite() {
    if (!is_user_logged_in()) {
        wp_send_json_error('Debes iniciar sesión');
    }

    // Sanitizar y verificar nonce
    if (!isset($_POST['_wpnonce'])) {
        wp_send_json_error('Nonce no presente');
    }
    
    $nonce = sanitize_text_field(wp_unslash($_POST['_wpnonce']));
    if (!wp_verify_nonce($nonce, 'favopadi_favoritos_nonce')) {
        wp_send_json_error('Nonce inválido');
    }

    // Validar y sanitizar provider_id
    if (!isset($_POST['provider_id']) || !is_numeric($_POST['provider_id'])) {
        wp_send_json_error('ID de proveedor inválido');
    }

    $user_id = get_current_user_id();
    $provider_id = intval($_POST['provider_id']);
    $favoritos = get_user_meta($user_id, 'favopadi_favoritos', true);
    $favoritos = is_array($favoritos) ? $favoritos : [];

    if (in_array($provider_id, $favoritos)) {
        $favoritos = array_diff($favoritos, [$provider_id]);
        $message = 'Eliminado';
    } else {
        $favoritos[] = $provider_id;
        $message = 'Agregado';
    }
    update_user_meta($user_id, 'favopadi_favoritos', $favoritos);
    wp_send_json_success($message);
}

// Resto del código permanece igual...

// Mostrar lista de favoritos con shortcode
function favopadi_mostrar_favoritos() {
    if (!is_user_logged_in()) return '<p>Inicia sesión para ver tus favoritos.</p>';

    $user_id = get_current_user_id();
    $favoritos = get_user_meta($user_id, 'favopadi_favoritos', true);
    if (!$favoritos) return '<p>No tienes favoritos aún.</p>';

    $html = '<ul>';
    foreach ($favoritos as $id) {
        $html .= "<li><a href='" . esc_url(get_permalink($id)) . "'>" . esc_html(get_the_title($id)) . "</a></li>";
    }
    $html .= '</ul>';
    return $html;
}
add_shortcode('favopadi_mis_favoritos', 'favopadi_mostrar_favoritos');

// Cargar script para AJAX
function favopadi_cargar_script_favoritos() {
    if (is_user_logged_in()) {
        wp_enqueue_script(
            'favopadi-favoritos-script',
            plugin_dir_url(__FILE__) . 'favoritos.js',
            ['jquery'],
            filemtime(plugin_dir_path(__FILE__) . 'favoritos.js'),
            true
        );
        wp_localize_script('favopadi-favoritos-script', 'favoritosAjax', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'security' => wp_create_nonce('favopadi_favoritos_nonce')
        ]);
    }
}
add_action('wp_enqueue_scripts', 'favopadi_cargar_script_favoritos');

// Endpoint para obtener un nuevo nonce
add_action('wp_ajax_get_new_nonce', 'favopadi_get_new_nonce');
function favopadi_get_new_nonce() {
    if (!is_user_logged_in()) {
        wp_send_json_error('Usuario no autenticado');
    }
    wp_send_json_success(wp_create_nonce('favopadi_favoritos_nonce'));
}