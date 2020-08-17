$(document).ready(function () {
    $('#filter-news').on('change', function (e) {
        let table = $('#data-table').DataTable();
        table.ajax.reload();
    });

    $('#clear-filter-news').on('click', function (e) {
        $('#filter-news option').prop('selected', false);
        let table = $('#data-table').DataTable();
        table.draw();
    });

});