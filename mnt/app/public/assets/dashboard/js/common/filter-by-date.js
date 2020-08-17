$(document).ready(function () {
    $("#date-filter").on("click", function (e) {
        e.preventDefault()
        $('#period').val(null)
        tableReload()
    });

     $("#period-link a").on("click", function (e) {
        e.preventDefault()
         let period = $(this).data('period')
         addFromToDates(period)
         $('#period').val(period)
        tableReload()
    });

     $("#reload").on("change", function (e) {
        e.preventDefault()
         let period = $(this).find(":selected").data('period')
         console.log(period);
         addFromToDates(period)
         $('#period').val(period)
        tableReload()
    });

    function addFromToDates(period) {

        let from, to;
        let format = "DD.MM.YYYY";

        switch (period) {
            case 'today':
                from = moment().format(format);
                to = moment().format(format);
                break;
            case 'yesterday':
                from = moment().add(-1, 'day').format(format);
                to = moment().format(format);
                break;
            case 'week':
                from = moment().add(-1, 'week').format(format);
                to = moment().format(format);
                break;
            case 'two-week':
                from = moment().add(-2, 'week').format(format);
                to = moment().format(format);
                break;
            case 'current-month':
                from = moment().startOf('month').format(format);
                to = moment().endOf('month').format(format);
                break;
            case 'last-month':
                from = moment().add(-1, 'month').startOf('month').format(format);
                to = moment().add(-1, 'month').endOf('month').format(format);
                break;
            case 'current-year':
                from = moment().startOf('year').format(format);
                to = moment().endOf('year').format(format);
                break;
            case 'last-year':
                from = moment().add(-1, 'year').startOf('year').format(format);
                to = moment().add(-1, 'year').endOf('year').format(format);
                break;

            case 'day-before-yesterday':
                from = moment().add(-2, 'day').format(format);
                to = moment().format(format);
                break;

            case 'current-week':
                from = moment().startOf('isoWeek').format(format);
                to = moment().endOf('isoWeek').format(format);
                break;

            case 'last-week':
                from = moment().add(-1, 'week').startOf('isoWeek').format(format);
                to = moment().add(-1, 'week').endOf('isoWeek').format(format);
                break;

            case 'current-month':
                from = moment().startOf('month').format(format);
                to = moment().endOf('month').format(format);
                break;
            case 'last-month':
                from = moment().add(-1, 'month').startOf('month').format(format);
                to = moment().add(-1, 'month').endOf('month').format(format);
                break;
            case 'month':
                from = moment().add(-30, 'day').format(format);
                to = moment().format(format);
                break;
            case 'two-month':
                from = moment().add(-60, 'day').format(format);
                to = moment().format(format);
                break;
            case 'three-month':
                from = moment().add(-90, 'day').format(format);
                to = moment().format(format);
                break;

            
        }
        $('#from').val(from);
        $('#to').val(to);
    }


    function tableReload() {
        let table = $('#data-table').DataTable();
        table.ajax.reload();
    }
});