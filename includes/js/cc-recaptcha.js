document.addEventListener('DOMContentLoaded', function () {
    grecaptcha.ready(function () {
        var siteKey = cc_recaptcha.siteKey;
        console.log(siteKey);
        
        // Exécuter reCAPTCHA avec le site key passé par PHP
        grecaptcha.execute(siteKey, { action: 'submit' }).then(function (token) {
            document.getElementById('g-recaptcha-response').value = token;
        });
    });
});
