changeLavel2()
changeLavel3()

$(".multiple-selector").select2();

$('#report_settings_level1').on('change', function (e) {
    changeLavel2()
});

$('#report_settings_level2').on('change', function (e) {
    changeLavel3()
});

function changeLavel2() {
    $('#report_settings_level1').each(function (key, value) {
        if ($(value).find('option:selected').val().length === 0) {
            $('#report_settings_level2').prop( "disabled", true )
            $('#report_settings_level3').prop( "disabled", true )
        } else {
            $('#report_settings_level2').prop( "disabled", false )
        }
    })
}

function changeLavel3() {
    $('#report_settings_level2').each(function (key, value) {
        if ($(value).find('option:selected').val().length === 0) {
            $('#report_settings_level3').prop( "disabled", true )
        } else {
            $('#report_settings_level3').prop( "disabled", false )
        }
    })
}

$('.multiple-selector-help').append('<button class="multiple-selector-select-all">+</button>');
$('.multiple-selector-help').append('<button class="multiple-selector-un-select-all">-</button>');

$('#report_settings_sources_help').click(function (e) {
    changeSelected(e, $(this))
    e.preventDefault()
});

$('#report_settings_news_help').click(function (e) {
    changeSelected(e, $(this))
    e.preventDefault()
});

function changeSelected(e, select) {
    if($(e.target).attr('class') == 'multiple-selector-select-all'){
        $(select).closest('.form-group').find('.multiple-selector option').prop('selected', true);
        $(".multiple-selector").trigger("change");
    } else if($(e.target).attr('class') == 'multiple-selector-un-select-all'){
        $(select).closest('.form-group').find('.multiple-selector option').prop('selected', false);
        $(".multiple-selector").trigger("change");
    }
}
