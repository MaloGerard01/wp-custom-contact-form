<?php
// Sécurité: Empêcher un accès direct au fichier
if (!defined('ABSPATH')) {
    exit;
}

function cc_enqueue_recaptcha_script() {
    // Enregistrement du script Google reCAPTCHA
    wp_enqueue_script('google-recaptcha', "https://www.google.com/recaptcha/api.js?render=".get_option('cc_recaptcha_site_key'), array(), null, true);
    
    // Enregistrement du script personnalisé pour exécuter reCAPTCHA
    wp_enqueue_script('cc-recaptcha', plugin_dir_url(__FILE__) . 'js/cc-recaptcha.js', array('google-recaptcha'), null, true);

    // Passer les clés reCAPTCHA à JavaScript
    wp_localize_script('cc-recaptcha', 'cc_recaptcha', array(
        'siteKey' => get_option('cc_recaptcha_site_key'),
    ));
}
add_action('wp_enqueue_scripts', 'cc_enqueue_recaptcha_script');

// Shortcode pour afficher le formulaire de contact
function cc_form_shortcode() {
    $privacy_policy_relative_url = get_option('cc_privacy_policy_url', '/cgu');
    $privacy_policy_url = esc_url(home_url($privacy_policy_relative_url));

    ob_start();
    ?>
    <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
        <p>
            <label for="cc_name">Nom</label>
            <input type="text" name="cc_name" required>
        </p>
        <p>
            <label for="cc_email">Email</label>
            <input type="email" name="cc_email" required>
        </p>
        <p>
            <label for="cc_phone">Numéro de téléphone</label>
            <input type="tel" name="cc_phone" maxlength="12" required>
        </p>
        <p>
            <label for="cc_message">Message</label>
            <textarea name="cc_message" required></textarea>
        </p>
        <p style="display:none;">
            <label for="cc_honeypot">Laisser ce champ vide</label>
            <input type="text" name="cc_honeypot">
        </p>
        <p>
            <input type="checkbox" name="cc_consent" required> 
            <label for="cc_consent">Je consens à ce que mes données soient collectées et utilisées conformément à la <a href="<?php echo $privacy_policy_url; ?>" target="_blank">politique de confidentialité</a>.</label>
        </p>
        <input type="hidden" id="g-recaptcha-response" name="g-recaptcha-response">
        <p>
            <input type="hidden" name="action" value="cc_form_submission">
            <input type="submit" value="Envoyer">
        </p>
    </form>
    <div id="recaptcha-script"></div>
    <?php
    return ob_get_clean();
}
add_shortcode('custom_contact_form', 'cc_form_shortcode');

// Enregistrer les données dans la base de données
function cc_handle_form_submission() {
    if (!isset($_POST['cc_consent'])) {
        wp_die('Vous devez donner votre consentement pour soumettre ce formulaire.');
    }

    // Vérification reCAPTCHA v3
    $recaptcha_secret = esc_attr(get_option('cc_recaptcha_secret_key'));
    $response = wp_remote_post("https://www.google.com/recaptcha/api/siteverify", array(
        'body' => array(
            'secret' => $recaptcha_secret,
            'response' => $_POST['g-recaptcha-response'],
            'remoteip' => $_SERVER['REMOTE_ADDR']
        )
    ));
    

    $response_body = wp_remote_retrieve_body($response);
    $result = json_decode($response_body);

    if (!$result->success || $result->score < 0.5) { // Le seuil peut être ajusté
        wp_die('reCAPTCHA vérification échouée. Votre score: ' . $result->score);
    }

    // Vérification honeypot
    if (!empty($_POST['cc_honeypot'])) {
        wp_die('Spam détecté.');
     }
    

    global $wpdb;

    $name = sanitize_text_field($_POST['cc_name']);
    $email = sanitize_email($_POST['cc_email']);
    $phone = sanitize_text_field($_POST['cc_phone']);
    $message = sanitize_textarea_field($_POST['cc_message']);
    $consent = isset($_POST['cc_consent']) ? 1 : 0;

    $table_name = $wpdb->prefix . 'custom_contact_form';
    $wpdb->insert($table_name, [
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'message' => $message,
        'consent' => $consent,
        'created_at' => current_time('mysql', 1),
    ]);

    // Envoi d'un email à l'administrateur si activé
    if (get_option('cc_send_to_admin') == 1) {
        $admin_email = get_option('cc_admin_email');
        if (!empty($admin_email)) {
            $admin_subject = 'Nouvelle soumission de formulaire';
            $admin_message = "Vous avez reçu une nouvelle soumission de formulaire de la part de $name.\n\n";
            $admin_message .= "Nom: $name\n";
            $admin_message .= "Email: $email\n";
            $admin_message .= "Numéro de téléphone: $phone\n";
            $admin_message .= "Message: $message\n";
    
            wp_mail($admin_email, $admin_subject, $admin_message);
        }
    }

    // Envoi d'un email de confirmation à l'utilisateur si activé
    if (get_option('cc_send_to_user') == 1) {
        $user_subject = 'Confirmation de votre soumission';
        $user_message = "Merci, $name, de nous avoir contactés. Nous avons bien reçu votre message et reviendrons vers vous dans les plus brefs délais.\n\n";

        wp_mail($email, $user_subject, $user_message);
    }

    // Rediriger après soumission
    wp_redirect(home_url('/'));
    exit;
}
add_action('admin_post_nopriv_cc_form_submission', 'cc_handle_form_submission');
add_action('admin_post_cc_form_submission', 'cc_handle_form_submission');

// Supprimer une entrée
function cc_delete_entry() {
    global $wpdb;

    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        wp_die('ID invalide.');
    }

    $id = intval($_GET['id']);
    $table_name = $wpdb->prefix . 'custom_contact_form';

    $wpdb->delete($table_name, ['id' => $id]);

    wp_redirect(admin_url('admin.php?page=custom-contact-form'));
    exit;
}
add_action('admin_post_cc_delete_entry', 'cc_delete_entry');
?>