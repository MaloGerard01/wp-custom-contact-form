<?php
/*
Plugin Name: Custom Contact Form
Description: A custom contact form plugin.
Version: 1.0
Author: Malo Gérard
*/

// Sécurité: Empêcher un accès direct au fichier
if (!defined('ABSPATH')) {
    exit;
}

include_once plugin_dir_path(__FILE__) . 'includes/install.php';
include_once plugin_dir_path(__FILE__) . 'includes/form-handler.php';
include_once plugin_dir_path(__FILE__) . 'includes/admin-page.php';

register_activation_hook(__FILE__, 'cc_create_database_table');
?>
