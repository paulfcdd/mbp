function calcPositioning(floatingBlock, newsBlock) {
    const newsPos = newsBlock.offset();

    if ($(window).scrollTop() >= (newsPos['top'] - 10)) {

        if ((newsBlock.height() + newsPos['top']) > ($(window).scrollTop() + floatingBlock.height())) {

            if (floatingBlock.css('position') !== 'fixed'){
                floatingBlock.css({
                    'position': 'fixed',
                    'top': '10px'
                });
            }

        } else {

            floatingBlock.css({
                'position': 'absolute',
                'top': parseInt(newsBlock.height() - floatingBlock.height()) + newsPos['top']
            });

        }

    } else if (floatingBlock.css('position') !== 'top'){

        floatingBlock.css({
            'position': '',
            'top': ''
        });

    }
}

function rightBlockPositioning() {
    const floatingBlock = $("#rightBlock");
    const newsBlock = $("#newsBlock");

    this.calcPositioning(floatingBlock, newsBlock);

    $(window).scroll(function() {
        this.calcPositioning(floatingBlock, newsBlock);
    });

    $(window).resize(function () {
        this.calcPositioning(floatingBlock, newsBlock);
    });
}