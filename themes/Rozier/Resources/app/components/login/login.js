import $ from 'jquery'
import request from 'axios'

(function () {
    const onLoad = function (data) {
        const $splashContainer = $('#splash-container')
        $splashContainer.css({
            'background-image': 'url(' + data.url + ')'
        })
        $splashContainer.addClass('visible')
    }

    const requestImage = function () {
        request({
            method: 'GET',
            url: window.RozierRoot.routes.splashRequest,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            withCredentials: false,
            responseType: 'json'
        })
        .then((response) => {
            if (typeof response.data !== 'undefined' &&
                typeof response.data.url !== 'undefined') {
                let myImage = new Image(window.width, window.height)
                myImage.src = response.data.url
                myImage.onload = $.proxy(onLoad, this, response.data)
            }
        })
        .catch((error) => {
            console.error(error.response.data.humanMessage)
        })
    }

    if (typeof window.RozierRoot.routes.splashRequest !== 'undefined') {
        requestImage()
    }
})()
