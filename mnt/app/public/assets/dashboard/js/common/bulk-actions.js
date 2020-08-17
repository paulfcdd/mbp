$(document).ready(function (e) {
    let bulkCheckbox = $('.bulk-actions');
    let bulkActionsSubmit = $('#bulk-actions-submit');
    let bulkActionsFormSubmit = $('#bulk-actions-form-submit');
    let bulkActionsSelector = $('#bulk-actions-selector');

    $(bulkCheckbox).on('click', function () {

        if ($(bulkCheckbox).is(':checked')) {
            $(document).find('.bulk-action-item').each(function (key, val) {
                $(val).parent().parent().addClass('checked-row');
                $(val).prop('checked', true)
            })
        } else {
            $(document).find('.bulk-action-item').each(function (key, val) {
                $(val).parent().parent().removeClass('checked-row');
                $(val).prop('checked', false)
            })
        }
    })

    $(bulkActionsSubmit).on('click', function () {
        let selectedOption = $(bulkActionsSelector).children("option:selected");
        let selectedOptionValue = $(selectedOption).val();
        let selectedOptionUrl = $(selectedOption).data('url');
        let confirmMessage = confirm('Подтверждаете массовую операцию?')
        let checkedItems

        if (selectedOptionValue === 'change_teaser_subgroup') {
            checkedItems = {}
            checkedItems['checked_items'] = []
            $(document).find('.bulk-action-item').each(function (key, value) {
                if ($(value).is(':checked')) {
                    let itemId = $(value).data('item-id');
                    checkedItems['checked_items'].push(itemId);
                }
            });
            checkedItems['sub_group'] = $('#bulk-change-teasers-group').val()
        } else {
            checkedItems = [];
            $(document).find('.bulk-action-item').each(function (key, value) {
                if ($(value).is(':checked')) {
                    let itemId = $(value).data('item-id');
                    checkedItems.push(itemId);
                }
            });
        }

        if (checkedItems.length === 0) {
            return;
        }

        if (typeof checkedItems.checked_items !== 'undefined' && checkedItems.checked_items.length === 0) {
            return;
        }

        if (confirmMessage) {
            sendAjaxRequest(checkedItems, selectedOptionUrl);
        }
    })

    $(bulkActionsFormSubmit).on('click', function (e) {
        e.preventDefault()
        let confirmMessage = confirm('Подтверждаете массовую операцию?')
        let selectedOption = $(bulkActionsSelector).children("option:selected");
        let selectedOptionUrl = $(selectedOption).data('url');
        let form = $('#bulk-actions-form')
        form.attr('action', selectedOptionUrl);
        if (confirmMessage) {
            form.submit()
        }
    })

    function sendAjaxRequest(checkedItems, selectedOptionUrl) {
        $.ajax({
            data: {'checkedItems': checkedItems},
            method: 'post',
            url: selectedOptionUrl,
            success: function (data) {
                window.location.replace(data.route_to_redirect);
            },
            error: function (data) {
                window.location.replace(data.route_to_redirect);
            }
        })
    }
    
    $('[name="data-table_length"], #filter-teasers-group-subgroup, #filter-news').on('change', function() {
        releaseBulkActionsCheckbox();
    });  

    $('.dataTables_filter>label>input').on('input', function() {
        releaseBulkActionsCheckbox();
    });  

    $('body').on('click', '.paginate_button', function() {
        if (!$(this).hasClass('disabled')) {
            releaseBulkActionsCheckbox();
        }
    });

    $('.sorting, #clear-filter-teasers-group-subgroup, #clear-filter-news').on('click', function() {
        releaseBulkActionsCheckbox();
    });

    $(document).on('change', '.bulk-action-item', function(e) {
        let input = e.target
        if ($('.bulk-action-item:checked').length == 0) {
            releaseBulkActionsCheckbox();
        }
        if($(input).is(':checked')) {
            $(input).parent().parent().addClass('checked-row')
        } else {
            $(input).parent().parent().removeClass('checked-row')
        }
    });

    function releaseBulkActionsCheckbox() {
        if ($('.bulk-actions').is(':checked')) {
            $('.bulk-actions').trigger('click');
        }
    }

})