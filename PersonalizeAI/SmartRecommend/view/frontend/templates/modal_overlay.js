define([
    'jquery',
    'Magento_Ui/js/modal/modal'
], function ($, modal) {
    'use strict';
    return function (config, element) {
        var options = {
            type: 'popup',
            responsive: true,
            innerScroll: true,
            title: 'Promotional Popup',
            buttons: []
        };

        var popup = modal(options, $(element));
        $(element).modal('openModal');
    };
});