/*jslint browser: true, unparam: true */
/*global jQuery, STUDIP */
(function ($, STUDIP) {
    'use strict';

    function xprintf(str, params) {
        return str.replace(/#\{(\w+)\}/g, function (chunk, key) {
            return params.hasOwnProperty(key) ? params[key] : chunk;
        });
    }

    $(document).on('ajaxComplete', function (event, jqxhr) {
        if (jqxhr.getResponseHeader('X-Initialize-Dialog')) {
            $('.ui-dialog-content textarea.add_toolbar').addToolbar();
            $('.ui-dialog-content .has-datepicker').datepicker();
        }
    });

    $(document).on('submit', '.sitenews-editor', function (event) {
        if ($('.multi-checkbox-required :checkbox', this).length > 0 && $('.multi-checkbox-required :checkbox:checked', this).length === 0) {
            window.alert("Bitte wählen Sie mindestens eine Sichtbarkeit aus.".toLocaleString());
            event.preventDefault();
        }
    });

    $(document).on('click', '.sitenews-widget .widget-tabs a', function (event) {
        var source_url = $(this).closest('.widget-tabs').data().source,
            perm       = $(this).data().perm,
            url        = xprintf(source_url, {perm: perm}),
            timeout;

        timeout = setTimeout(function () {
            STUDIP.Overlay.show(true, '.sitenews-widget');
        }, 200);

        $(this).closest('.sitenews-widget').parent().load(url, function () {
            clearTimeout(timeout);
            STUDIP.Overlay.hide();
        });

        event.preventDefault();
    });

    $(document).on('click', 'a.sitenews-active-toggle', function (event) {
        var shown = !$(this).data().showInactive;
        $(this).data('show-inactive', shown).attr('data-show-inactive', JSON.stringify(shown));

        $(this).closest('.studip-widget').find('[data-active="false"]').toggle(shown);

        event.stopPropagation();
        event.preventDefault();
    });

    $(document).on('change', '.sitenews-editor :checkbox[name="visibility[]"][value="autor"]', function (event) {
        if (this.checked) {
            $(':checkbox[name="visibility[]"][value="tutor"]').attr('checked', true);
        }
    });
}(jQuery, STUDIP));
