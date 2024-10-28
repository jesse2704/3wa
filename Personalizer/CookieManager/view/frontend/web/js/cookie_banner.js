require(['jquery'], function($) {
    $(document).ready(function() {
        $('#cookie-accept').on('click', function() {
            let interests = $('.interests').val();
            if (interests === "") {
                $('.cookie-consent-banner__error').show();
            } else {
                $('#cookie-banner').hide();
                document.cookie = "personalize_accepted=" + interests + "; expires=Fri, 31 Dec 9999 23:59:59 GMT; path=/";
            }
        });
        $('#cookie-decline').on('click', function() {
            $('#cookie-banner').hide();
        });
    });
});
