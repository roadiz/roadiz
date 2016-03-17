(function () {

    //center login/purge div
    var $inners = $('#purge-caches, #login');
    for (var i = $inners.length - 1; i >= 0; i--) {
        var inner = $($inners[i]);
        inner.css({
            'margin-top':inner.outerHeight()/-2,
            'margin-left':inner.outerWidth()/-2
        });
    }

    var onLoad = function (data, event) {
        $("#splash-container").css({
            'background-image':'url('+data.url+')'
        });
        $("#splash-container").addClass('visible');
    };

    var requestImage = function () {
        $.ajax({
            url: splashRequest,
            type: 'GET',
            cache: false,
            dataType: 'json'
        })
        .done(function(data) {
            if (false === data) {
                requestImage();
            } else if(typeof data.url != 'undefined'){

                var myImage = new Image(window.width, window.height);
                myImage.src = data.url;
                myImage.onload = $.proxy(onLoad, this, data);
            }
        });
    };

    if(typeof splashRequest !== 'undefined'){
        requestImage();
    }

})();
