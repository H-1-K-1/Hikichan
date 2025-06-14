/*
 * Adds 4chan-like [Start a New Thread] and [Post a Reply] buttons to pages,
 * with an option in the Options > General tab to hide the post form by default.
 *
 * Usage:
 * $config['additional_javascript'][] = 'js/jquery.min.js';
 * $config['additional_javascript'][] = 'js/hide-form.js';
 *
 */

$(document).ready(() => {
    if (typeof active_page === 'undefined' || (active_page !== 'index' && active_page !== 'thread'))
        return;

    let form_el = $('form[name="post"]');
    let form_msg = active_page === 'index' ? 'Start a New Thread' : 'Post a Reply';

    // Add option to Options > General tab
    if (window.Options && Options.get_tab('general')) {
        Options.extend_tab('general',
            '<span id="hide-form-toggle">' +
            '<label><input type="checkbox" id="hide-post-form"> Hide post form by default</label>' +
            '</span>'
        );

        let $checkbox = $('#hide-post-form');
        // Default: unchecked (form visible)
        let hideForm = localStorage.hidePostForm === 'true';
        $checkbox.prop('checked', hideForm);

        // Show/hide form based on setting
        setFormVisibility(!hideForm);

        $checkbox.on('change', function () {
            let checked = $(this).is(':checked');
            localStorage.hidePostForm = checked ? 'true' : 'false';
            setFormVisibility(!checked);
        });
    } else {
        // If no options panel, show form by default
        setFormVisibility(true);
    }

    function setFormVisibility(show) {
        let $showBtn = $('#show-post-form');
        if (show) {
            form_el.show();
            $showBtn.hide();
        } else {
            form_el.hide();
            if ($showBtn.length === 0) {
                form_el.after(`<div id="show-post-form" style="font-size:175%;text-align:center;font-weight:bold">[<a href="#" style="text-decoration:none">${_(form_msg)}</a>]</div>`);
                $showBtn = $('#show-post-form');
                $showBtn.on('click', function (e) {
                    e.preventDefault();
                    $showBtn.hide();
                    form_el.show();
                });
            } else {
                $showBtn.show();
            }
        }
    }
});