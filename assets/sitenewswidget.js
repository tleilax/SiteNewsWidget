/*jslint browser: true, unparam: true */
/*global jQuery, STUDIP */
(function ($, STUDIP) {
    'use strict';

    function xprintf(str, params) {
        return str.replace(/#\{([\w-_]+)\}/g, function (chunk, key) {
            return params.hasOwnProperty(key) ? params[key] : chunk;
        });
    }

    $(document).on('submit', '.sitenews-editor', function (event) {
        if ($('.multi-checkbox-required :checkbox', this).length > 0 && $('.multi-checkbox-required :checkbox:checked', this).length === 0) {
            window.alert("Bitte wählen Sie mindestens eine Sichtbarkeit aus.".toLocaleString());
            event.preventDefault();
        }
    });

    $(document).on('click', '.sitenews-widget .widget-tabs a', function (event) {
        var source_url = $(this).closest('.widget-tabs').data().source;
        var group      = $(this).data().group;
        var url        = xprintf(source_url, {group: group});

        var timeout = setTimeout(function () {
            STUDIP.Overlay.show(true);
        }, 200);

        $(this).closest('.sitenews-widget').parent().load(url, function () {
            clearTimeout(timeout);
            STUDIP.Overlay.hide();
        });

        event.preventDefault();
    });

    $(document).on('click', 'a.sitenews-active-toggle', function (event) {
        const link = $(this).attr('href');

        $.post(link).done(shown => {
            const widget = $(this).closest('.studip-widget').find('.sitenews-widget');
            const role = shown ? 'checkbox-unchecked' : 'checkbox-checked';

            $(this).data('show-inactive', shown)
                .find('img')
                .attr('src', STUDIP.ASSETS_URL + 'images/icons/blue/' + role + '.svg');


            widget.find('[data-active="false"]').toggle(shown);

            const visibleEntries = widget.find('article.studip:visible').length;
            $('.no-entries', widget).toggle(visibleEntries === 0);
        });

        return false;
    });

    var new_counter = 1;

    $(document).on('click', '.group-administration button[name="new-group"]', function () {
        var table = $(this).closest('table.group-administration');
        var position = table.find('input[type="hidden"]').last().val();
        var template = xprintf($('script[type="text/x-template"]#new-group-row').text(), {
            'new-id': -(new_counter++),
            'position': parseInt(position, 10) + 1
        });

        table.find('tbody').append(template);
        $(document).trigger('dialog-update', {dialog: table});
        table.sortable('refresh');

        return false;
    }).on('click', '.group-administration .actions input[type="image"]', function () {
        var question = $(this).data().confirm;
        STUDIP.Dialog.confirm(question).then(function () {
            return $.Deferred(function (dfd) {
                if ($(this).is('.new-row')) {
                    dfd.resolve();
                } else {
                    var url = $(this).attr('formaction');
                    $.post(url).done(dfd.resolve).fail(dfd.reject);
                }
            }.bind(this));
        }.bind(this)).done(function () {
            $(this).closest('tr').remove();
        }.bind(this));

        return false;
    });

    STUDIP.ready(function () {
        $('table.group-administration:not(.ui-sortable)').sortable({
            axis: 'y',
            containment: 'parent',
            cursor: 'ns-resize',
            forcePlaceholderSize: true,
            helper: function (event, element) {
                var helper = $(element).clone();
                $('td', helper).each(function (index) {
                    var width = $('td:eq(' + index + ')', element).width();
                    $(this).width(width);
                });
                return helper;
            },
            handle: 'td:first-child',
            items: '> tbody > tr',
            placeholder: 'placeholder',
            tolerance: 'pointer',
            update: function (event, ui) {
                ui.item.closest('tbody').find('tr').each(function (index) {
                    $('input[type=hidden]', this).val(index + 1);
                });
            }
        });
    });

}(jQuery, STUDIP));
