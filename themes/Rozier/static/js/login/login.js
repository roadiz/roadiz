(function () {

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