import $ from 'jquery'
import {
    toType
} from '../../utils/plugins'

/**
 * Node edit source
 */
export default class TagEdit {
    constructor () {
        // Selectors
        this.$content = $('.content-tag-edit').eq(0)
        this.$form = $('#edit-tag-form')
        this.$formRow = null
        this.$dropdown = null

        // Binded methods
        this.onFormSubmit = this.onFormSubmit.bind(this)

        // Methods
        if (this.$content.length) {
            this.$formRow = this.$content.find('.uk-form-row')
            this.initEvents()
        }
    }

    initEvents () {
        this.$form.on('submit', this.onFormSubmit)
    }

    unbind () {
        if (this.$content.length) {
            this.$form.off('submit', this.onFormSubmit)
        }
    }

    onFormSubmit () {
        window.Rozier.lazyload.canvasLoader.show()

        if (this.currentTimeout) {
            clearTimeout(this.currentTimeout)
        }

        this.currentTimeout = setTimeout(() => {
            /*
             * Trigger event on window to notify open
             * widgets to close.
             */
            let pageChangeEvent = new CustomEvent('pagechange')
            window.dispatchEvent(pageChangeEvent)

            let formData = new FormData(this.$form.get(0))

            $.ajax({
                url: window.location.href,
                type: 'post',
                data: formData,
                processData: false,
                cache: false,
                contentType: false
            })
                .done(data => {
                    this.cleanErrors()
                })
                .fail(data => {
                    if (data.responseJSON) {
                        this.displayErrors(data.responseJSON.errors)
                        window.UIkit.notify({
                            message: data.responseJSON.message,
                            status: 'danger',
                            timeout: 2000,
                            pos: 'top-center'
                        })
                    }
                })
                .always(() => {
                    window.Rozier.lazyload.canvasLoader.hide()
                    window.Rozier.refreshMainTagTree()
                    window.Rozier.getMessages()
                })
        }, 300)

        return false
    }

    cleanErrors () {
        const $previousErrors = $('.form-errored')
        $previousErrors.each((index) => {
            $previousErrors.eq(index).removeClass('form-errored')
            $previousErrors.eq(index).find('.error-message').remove()
        })
    }

    /*
     * @param {Array} errors
     * @param {Boolean} keepExisting Keep existing errors.
     */
    displayErrors (errors, keepExisting) {
        // First clean fields
        if (!keepExisting || keepExisting === false) {
            this.cleanErrors()
        }

        for (let key in errors) {
            let classKey = null
            let errorMessage = null
            if (toType(errors[key]) === 'object') {
                this.displayErrors(errors[key], true)
            } else {
                classKey = key.replace('_', '-')
                if (errors[key] instanceof Array) {
                    errorMessage = errors[key][0]
                } else {
                    errorMessage = errors[key]
                }
                let $field = $('.form-col-' + classKey)
                if ($field.length) {
                    $field.addClass('form-errored')
                    $field.append('<p class="error-message uk-alert uk-alert-danger"><i class="uk-icon uk-icon-warning"></i> ' + errorMessage + '</p>')
                }
            }
        }
    }

    /**
     * Window resize callback
     */
    resize () {}
}
