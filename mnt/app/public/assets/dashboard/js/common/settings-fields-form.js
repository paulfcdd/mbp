$("#fields_settings_save").on('click', function (e) {
    e.preventDefault()
    let data = {}
    data.traffic = $('#fields_settings_traffic :input').serializeArray();
    data.leads = $('#fields_settings_leads :input').serializeArray();
    data.finances = $('#fields_settings_finances :input').serializeArray();
    $.ajax({
        data: {'settings-fields': data},
        method: 'post',
        url: '/mediabuyer/statistic/traffic-analysis/settings-fields/update',
        success: function (data) {
            window.location.reload();
        },
        error: function (data) {
            window.location.reload();
        }
    })
})