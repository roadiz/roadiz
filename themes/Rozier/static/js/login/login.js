(function () {

    var onLoad = function (event) {
        $("#splash-container").css({
            'background-image':'url('+splash.url+')'
        });
        $("#splash-container").addClass('visible');
    };

    if(typeof splash != 'undefined'){

        var myImage = new Image(window.width, window.height);
        myImage.src = splash.url;
        console.log(myImage);
        myImage.onload = onLoad;
    }
})();