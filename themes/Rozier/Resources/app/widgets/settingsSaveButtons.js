import $ from 'jquery'

/**
 * Settings save buttons
 */
export default class SettingsSaveButtons {
    constructor () {
        // Selectors
        this.$button = $('.uk-button-settings-save')
        this.currentRequest = null

        // Binded methods
        this.buttonClick = this.buttonClick.bind(this)

        // Methods
        if (this.$button.length) this.init()
    }

    /**
     * Init
     */
    init () {
        // Events
        this.$button.on('click', this.buttonClick)
    }

    unbind () {
        this.$button.off('click', this.buttonClick)
    }

    /**
     * Button click
     * @param {Event} e
     * @returns {boolean}
     */
    buttonClick (e) {
        if (this.currentRequest && this.currentRequest.readyState !== 4) {
            this.currentRequest.abort()
        }

        let $form = $(e.currentTarget).parent().parent().find('.uk-form').eq(0)

        if ($form.find('input[type=file]').length) {
            $form.submit()
            return false
        }

        window.Rozier.lazyload.canvasLoader.show()
        let formData = new FormData($form[0])
        let sendData = {
            url: window.location.href,
            type: 'post',
            data: formData,
            processData: false,
            cache: false,
            contentType: false
        }

        this.currentRequest = $.ajax(sendData)
            .done(() => {
                console.debug('Saved setting with success.')
            })
            .always(() => {
                window.Rozier.lazyload.canvasLoader.hide()
                window.Rozier.getMessages()
            })

        return false
    }

    /**
     * Window resize callback
     */
    resize () {}
}
