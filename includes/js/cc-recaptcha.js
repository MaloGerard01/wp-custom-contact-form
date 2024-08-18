document.addEventListener('DOMContentLoaded', function () {
    grecaptcha.ready(function () {
        var siteKey = cc_recaptcha.siteKey;
        
        grecaptcha.execute(siteKey, { action: 'submit' }).then(function (token) {
            document.getElementById('g-recaptcha-response').value = token;
        });
    });
});
