import $ from 'jquery'
import { isMobile } from '../../utils/plugins'
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
            withCredentials: true,
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
            throw new Error(error.response.data.humanMessage)
        })
    }

    if (typeof window.RozierRoot.routes.splashRequest !== 'undefined' && !isMobile.any()) {
        requestImage()
    }
})()
