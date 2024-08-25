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

// Enregistrer les paramètres
function cc_register_settings() {
    register_setting('cc_contact_form_settings', 'cc_recaptcha_site_key');
    register_setting('cc_contact_form_settings', 'cc_recaptcha_secret_key');
    register_setting('cc_contact_form_settings', 'cc_privacy_policy_url');
    register_setting('cc_contact_form_settings', 'cc_send_to_admin');
    register_setting('cc_contact_form_settings', 'cc_admin_email');
    register_setting('cc_contact_form_settings', 'cc_send_to_user');
}
add_action('admin_init', 'cc_register_settings');

// Fonction pour afficher les entrées du formulaire
function cc_display_entries_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'custom_contact_form';
    
    // Récupérer le terme de recherche s'il y en a un
    $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

    // Récupérer le numéro de page actuel
    $paged = isset($_GET['paged']) && is_numeric($_GET['paged']) ? intval($_GET['paged']) : 1;
    $limit = 25; // Nombre d'entrées par page
    $offset = ($paged - 1) * $limit;

    // Modifier la requête SQL pour filtrer par nom, email ou message
    $query = "SELECT * FROM $table_name";
    if (!empty($search)) {
        $query .= $wpdb->prepare(" WHERE name LIKE %s OR email LIKE %s OR message LIKE %s", '%'.$search.'%', '%'.$search.'%', '%'.$search.'%');
    }
    $query .= " LIMIT $offset, $limit";
    $results = $wpdb->get_results($query);

    // Obtenir le nombre total d'entrées pour la pagination
    $total_query = "SELECT COUNT(*) FROM $table_name";
    if (!empty($search)) {
        $total_query .= $wpdb->prepare(" WHERE name LIKE %s OR email LIKE %s OR message LIKE %s", '%'.$search.'%', '%'.$search.'%', '%'.$search.'%');
    }
    $total_items = $wpdb->get_var($total_query);
    $total_pages = ceil($total_items / $limit);
    
    ?>
    <div class="wrap">
        <h1>Entrées du formulaire de contact</h1>
        
        <!-- Formulaire de recherche -->
        <form method="get" action="">
            <input type="hidden" name="page" value="custom-contact-form" />
            <input type="text" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Rechercher par nom, email ou message" />
            <input type="submit" value="Rechercher" class="button" />
            <?php if (!empty($search)) { ?>
                <a href="<?php echo admin_url('admin.php?page=custom-contact-form'); ?>" class="button">Réinitialiser</a>
            <?php } ?>
        </form>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nom</th>
                    <th>Email</th>
                    <th>Numéro de téléphone</th>
                    <th>Message</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($results) : ?>
                    <?php foreach ($results as $row) : ?>
                        <tr>
                            <td><?php echo esc_html($row->id); ?></td>
                            <td><?php echo esc_html($row->name); ?></td>
                            <td><?php echo esc_html($row->email); ?></td>
                            <td><?php echo esc_html($row->phone); ?></td>
                            <td><?php echo esc_html($row->message); ?></td>
                            <td><?php echo esc_html($row->created_at); ?></td>
                            <td>
                                <a href="<?php echo esc_url(admin_url('admin-post.php?action=cc_delete_entry&id=' . $row->id)); ?>" 
                                   onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette entrée?');">
                                    Supprimer
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="7">Aucune entrée trouvée.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1) : ?>
            <div class="tablenav">
                <div class="tablenav-pages">
                    <?php
                    $pagination_args = array(
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'total' => $total_pages,
                        'current' => $paged,
                        'prev_text' => __('&laquo; Précédent'),
                        'next_text' => __('Suivant &raquo;'),
                    );

                    echo paginate_links($pagination_args);
                    ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <?php
}

// Fonction pour afficher la page de paramètres
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
                <tr valign="top">
                    <th scope="row">URL de la politique de confidentialité</th>
                    <td><input type="text" name="cc_privacy_policy_url" value="<?php echo esc_attr(get_option('cc_privacy_policy_url')); ?>" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Envoyer un email à l'administrateur</th>
                    <td><input type="checkbox" name="cc_send_to_admin" value="1" <?php checked(get_option('cc_send_to_admin'), 1); ?> /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Email de l'administrateur</th>
                    <td><input type="email" name="cc_admin_email" value="<?php echo esc_attr(get_option('cc_admin_email')); ?>" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Envoyer un email de confirmation à l'utilisateur</th>
                    <td><input type="checkbox" name="cc_send_to_user" value="1" <?php checked(get_option('cc_send_to_user'), 1); ?> /></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}
?>
