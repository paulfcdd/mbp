$( document ).ready( function () {

    $( window ).scroll( function(){

        let news = parseInt($( '#article_id' ).val());
        let page = parseInt($( '#page_number' ).val());
        let type = window.location.pathname.split( '/' )[2];

        if ( ( $( window ).scrollTop() === $( document ).height() - $( window ).height() ) ) {

            $.ajax({

                type: 'GET',
                url: '/ajax-news-teasers/' + news + '/' + type + '/' + page,
                beforeSend: function () {

                    $( '#loading' ).show();

                },
                success: function ( data ) {

                    const array_with_teasers_objects = JSON.parse(data);

                    for (let i = 0; i < array_with_teasers_objects.length; i++) {

                        const news_li_block = firstDesignParseObjectToHTML(array_with_teasers_objects[i]);
                        $( '#place_for_teasers' ).append(news_li_block);

                    }

                    page += 1;

                    $( '#page_number' ).val(page)

                    $( '#loading' ).hide();

                }

            });

        }

    });

});

function firstDesignParseObjectToHTML( item ) {

    const image_width = '240';
    const image_height = '180';

    const type = window.location.pathname.split( '/' )[2];
    const article_id = window.location.pathname.split( '/' )[3];

    const title = item['text'];
    const src = '/previews/' + item['file_path'].slice(16) + '/' + image_width + 'x' + image_height + '_' + item['fileName'];
    const href = `/counting/${ item['id'] }/${ article_id }?pageType=` + type;

    return `<div class="item-fourth__unit">
                <a href="${href}" class="item-fourth__link" target="_blank">
                <span class="item-fourth__img">
                    <img src="${src}" alt="${title}">
                </span>
                    <span class="item-fourth__title">${title}</span>
                </a>
            </div>`;

}