(function ($) {
    $(document).on('ajaxComplete', function (event, jqxhr) {
        var button_set = STUDIP.Toolbar.buttonSet;
        
        button_set.left.today = {
            label: "Heute".toLocaleString(),
            evaluate: function (string) {
                var week_days = ['Sonntag', 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag'],
                    months    = ['Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'],
                    now       = new Date;
                return '!!! ' + week_days[now.getDay()] + ', den ' + now.getDate() + '. ' + months[now.getMonth()] + ' ' + now.getFullYear() + "\n";
            }
        };
        
        if (jqxhr.getResponseHeader('X-Initialize-Dialog')) {
            jQuery('.ui-dialog-content textarea.add_toolbar').addToolbar(button_set);
        }
    });
    
}(jQuery));