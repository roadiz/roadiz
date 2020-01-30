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

        if ($form.hasClass('uk-has-errors')) {
            $form.find('.uk-alert').remove()
            $form.removeClass('uk-has-errors')
        }

        window.Rozier.lazyload.canvasLoader.show()
        let formData = new window.FormData($form[0])
        let sendData = {
            url: window.location.href,
            type: 'post',
            data: formData,
            processData: false,
            cache: false,
            contentType: false,
            headers: {'Accept': 'application/json'}
        }

        this.currentRequest = $.ajax(sendData)
            .fail((data) => {
                if (data.responseJSON && data.responseJSON.errors && data.responseJSON.errors.value) {
                    for (let key in data.responseJSON.errors.value) {
                        $form.addClass('uk-has-errors')
                        $form.append('<span class="uk-alert uk-alert-danger">' + data.responseJSON.errors.value[key] + '</span>')
                    }
                } else if (data.responseJSON && data.responseJSON.errors) {
                    for (let key in data.responseJSON.errors) {
                        $form.addClass('uk-has-errors')
                        $form.append('<span class="uk-alert uk-alert-danger">' + data.responseJSON.errors[key] + '</span>')
                        break
                    }
                }
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
