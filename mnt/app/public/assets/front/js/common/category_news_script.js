$( document ).ready( function () {

    $( window ).scroll( function(){

        let page = parseInt($( '#page_number' ).val());
        let slug = $( '#slug' ).val();

        if ( ( $( window ).scrollTop() === $( document ).height() - $( window ).height() ) ) {

            $.ajax({

                type: 'GET',
                url: '/ajax-news-categories/' + slug + '/' + page,
                beforeSend: function() {

                    $( '#loading' ).show();

                },
                success: function( data ) {

                    const array_with_news_objects = JSON.parse(data);

                    for (let i = 0; i < array_with_news_objects.length; i++) {

                        const news_li_block = firstDesignParseObjectToHTML(array_with_news_objects[i]);
                        $('#load_main').append(news_li_block);

                    }

                    page += 1;

                    $( '#page_number' ).val(page)

                    $( '#loading' ).hide();

                }

            });

        }

    });

})

function firstDesignParseObjectToHTML( item ) {

    const image_width = '240';
    const image_height = '180';

    const title = item['title'];
    const src = '/previews/' + item['filePath'].slice(16) + '/' + image_width + 'x' + image_height + '_' + item['fileName'];
    const href = `/counting_news/${ item['id'] }?pageType=category`;

    return `<li><a href="${href}" target="_blank">
                             <span class="visual">
                                <img src="${src}" alt="${title}">
                             </span>
                             <h2>${title}</h2>
                         </a>
                     </li>`;

}