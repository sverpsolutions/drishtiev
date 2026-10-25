/* Ezee Needs ERP — Main JS */
$(function() {
    // Auto-dismiss alerts after 4 seconds
    setTimeout(function() {
        $('.alert-dismissible').fadeOut('slow');
    }, 4000);

    // Confirm delete links
    $(document).on('click', 'a[data-confirm]', function(e) {
        if (!confirm($(this).data('confirm') || 'Are you sure?')) {
            e.preventDefault();
        }
    });

    // Activate current nav link
    var baseUrl = window.BASE_URL || '';
    var currentUrl = window.location.href.split('?')[0].replace(/\/$/, "");
    var relativeCurrent = window.location.href.replace(baseUrl, '');
    var currentController = relativeCurrent.split('/')[0] || '';

    // First pass: exact match for dropdown items and single nav links
    var exactMatchFound = false;
    $('.navbar-nav .dropdown-item, .navbar-nav .nav-link:not(.dropdown-toggle)').each(function() {
        var href = this.href ? this.href.split('?')[0].replace(/\/$/, "") : '';
        if (href === currentUrl && href !== '') {
            $(this).addClass('active');
            exactMatchFound = true;
            
            // Highlight parent dropdown if it is a dropdown-item
            if ($(this).hasClass('dropdown-item')) {
                $(this).closest('.dropdown').find('.dropdown-toggle').addClass('active');
            }
        }
    });

    // Second pass: if no exact match, highlight parent dropdown based on controller path
    if (!exactMatchFound && currentController !== '') {
        $('.navbar-nav .dropdown').each(function() {
            var dropdown = $(this);
            var parentToggle = dropdown.find('.dropdown-toggle');
            var controllerMatched = false;

            dropdown.find('.dropdown-item').each(function() {
                var href = this.href || '';
                var relativeHref = href.replace(baseUrl, '');
                var linkController = relativeHref.split('/')[0] || '';
                if (linkController === currentController) {
                    controllerMatched = true;
                }
            });

            if (controllerMatched) {
                parentToggle.addClass('active');
            }
        });
    }
});
