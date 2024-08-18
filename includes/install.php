<?php
// Sécurité: Empêcher un accès direct au fichier
if (!defined('ABSPATH')) {
    exit;
}

function cc_create_database_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'custom_contact_form';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name tinytext NOT NULL,
        email varchar(100) NOT NULL,
        phone VARCHAR(20) NOT NULL,
        message text NOT NULL,
        consent tinyint(1) NOT NULL DEFAULT 1,
        created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        error_log('La table n\'a pas été créée.');
    }
}

register_activation_hook(__FILE__, 'cc_create_database_table');
?>
