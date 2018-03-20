import $ from 'jquery'
import {
    TweenLite,
    Expo
} from 'gsap'

/**
 * Custom form field edit
 */
export default class CustomFormFieldEdit {
    constructor () {
        // Selectors
        this.$btn = $('.custom-form-field-edit-button')

        this.btnClick = this.btnClick.bind(this)

        if (this.$btn.length) {
            this.$formFieldRow = $('.custom-form-field-row')
            this.$formFieldCol = $('.custom-form-field-col')
            this.indexOpen = null
            this.openFormDelay = 0
            this.$formCont = null
            this.$form = null
            this.$formIcon = null
            this.$formContHeight = null

            // Methods
            this.init()
        }
    }

    /**
     * Init
     * @return {[type]} [description]
     */
    init () {
        // Events
        this.$btn.on('click', this.btnClick)
    }

    unbind () {
        if (this.$btn.length) {
            this.$btn.off('click', this.btnClick)
        }
    }

    /**
     * Btn click
     */
    btnClick (e) {
        e.preventDefault()

        if (this.indexOpen !== null) {
            this.closeForm()
            this.openFormDelay = 500
        } else {
            this.openFormDelay = 0
        }

        if (this.indexOpen !== parseInt(e.currentTarget.getAttribute('data-index'))) {
            if (this.openTimeout) {
                window.clearTimeout(this.openTimeout)
            }
            this.openTimeout = window.setTimeout(() => {
                this.indexOpen = parseInt(e.currentTarget.getAttribute('data-index'))
                $.ajax({
                    url: e.currentTarget.href,
                    type: 'get',
                    cache: false,
                    dataType: 'html'
                })
                    .done(data => {
                        this.applyContent(e.currentTarget, data, e.currentTarget.href)
                    })
                    .fail(() => {
                        window.UIkit.notify({
                            message: window.Rozier.messages.forbiddenPage,
                            status: 'danger',
                            timeout: 3000,
                            pos: 'top-center'
                        })
                    })
            }, this.openFormDelay)
        }

        return false
    }

    /**
     * Apply content
     */
    applyContent (target, data, url) {
        let dataWrapped = [
            '<tr class="custom-form-field-edit-form-row">',
            '<td colspan="4">',
            '<div class="custom-form-field-edit-form-cont">',
            data,
            '</div>',
            '</td>',
            '</tr>'
        ].join('')

        $(target).parent().parent().after(dataWrapped)

        // Remove class to pause sortable actions
        this.$formFieldCol.removeClass('custom-form-field-col')

        // Switch checkboxes
        $('.rz-boolean-checkbox').bootstrapSwitch({
            size: 'small'
        })

        window.Rozier.lazyload.initMarkdownEditors()

        window.setTimeout(() => {
            this.$formCont = $('.custom-form-field-edit-form-cont')
            this.formContHeight = this.$formCont.actual('height')
            this.$formRow = $('.custom-form-field-edit-form-row')
            this.$form = $('#edit-custom-form-field-form')
            this.$formIcon = $(this.$formFieldRow[this.indexOpen]).find('.custom-form-field-col-1 i')

            this.$form.attr('action', url)
            this.$formIcon[0].className = 'uk-icon-chevron-down'

            this.$formCont[0].style.height = '0px'
            this.$formCont[0].style.display = 'block'

            TweenLite.to(this.$form, 0.6, {
                height: this.formContHeight,
                ease: Expo.easeOut
            })

            TweenLite.to(this.$formCont, 0.6, {
                height: this.formContHeight,
                ease: Expo.easeOut
            })
        }, 200)
    }

    /**
     * Close form
     */
    closeForm () {
        this.$formIcon[0].className = 'uk-icon-chevron-right'

        TweenLite.to(this.$formCont, 0.4, {
            height: 0,
            ease: Expo.easeOut,
            onComplete: () => {
                this.$formRow.remove()
                this.indexOpen = null
                this.$formFieldCol.addClass('custom-form-field-col')
            }
        })
    }

    /**
     * Window resize callback
     * @return {[type]} [description]
     */
    resize () {}
}
