(function ($) {
    $(document).on('ajaxComplete', function (event, jqxhr) {
        if (jqxhr.getResponseHeader('X-Initialize-Dialog')) {
            jQuery('.ui-dialog-content textarea.add_toolbar').addToolbar();
        }
    });
    
}(jQuery));