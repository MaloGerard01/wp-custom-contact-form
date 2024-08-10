<?php
// Sécurité: Empêcher un accès direct au fichier
if (!defined('ABSPATH')) {
    exit;
}

function cc_register_admin_page() {
    // Menu principal
    add_menu_page('Formulaire de contact', 'Formulaire de contact', 'manage_options', 'custom-contact-form', 'cc_display_entries_page', 'dashicons-email', 26);

    // Sous-menu pour les entrées du formulaire
    add_submenu_page('custom-contact-form', 'Entrées du formulaire', 'Entrées', 'manage_options', 'custom-contact-form', 'cc_display_entries_page');

    // Sous-menu pour les paramètres reCAPTCHA
    add_submenu_page('custom-contact-form', 'Paramètres', 'Paramètres', 'manage_options', 'cc-settings', 'cc_display_settings_page');
}
add_action('admin_menu', 'cc_register_admin_page');

// Enregistrer les paramètres reCAPTCHA
function cc_register_settings() {
    register_setting('cc_contact_form_settings', 'cc_recaptcha_site_key');
    register_setting('cc_contact_form_settings', 'cc_recaptcha_secret_key');
}
add_action('admin_init', 'cc_register_settings');

// Fonction pour afficher les entrées du formulaire
function cc_display_entries_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'custom_contact_form';
    $results = $wpdb->get_results("SELECT * FROM $table_name");
    ?>
    <div class="wrap">
        <h1>Entrées du formulaire de contact</h1>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nom</th>
                    <th>Email</th>
                    <th>Message</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $row) { ?>
                    <tr>
                        <td><?php echo $row->id; ?></td>
                        <td><?php echo $row->name; ?></td>
                        <td><?php echo $row->email; ?></td>
                        <td><?php echo $row->message; ?></td>
                        <td><?php echo $row->created_at; ?></td>
                        <td>
                            <a href="<?php echo admin_url('admin-post.php?action=cc_delete_entry&id=' . $row->id); ?>" 
                               onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette entrée?');">
                                Supprimer
                            </a>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
    <?php
}

// Fonction pour afficher la page de paramètres reCAPTCHA
function cc_display_settings_page() {
    ?>
    <div class="wrap">
        <h1>Paramètres du formulaire</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('cc_contact_form_settings');
            do_settings_sections('cc_contact_form_settings');
            ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">reCAPTCHA Site Key</th>
                    <td><input type="text" name="cc_recaptcha_site_key" value="<?php echo esc_attr(get_option('cc_recaptcha_site_key')); ?>" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">reCAPTCHA Secret Key</th>
                    <td><input type="text" name="cc_recaptcha_secret_key" value="<?php echo esc_attr(get_option('cc_recaptcha_secret_key')); ?>" /></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}
?>