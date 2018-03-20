import $ from 'jquery'
import {
    TweenLite,
    Expo
} from 'gsap'

/**
 * NODE TYPE FIELD EDIT
 */
export default class NodeTypeFieldEdit {
    constructor () {
        // Selectors
        this.$btn = $('.node-type-field-edit-button')
        this.currentRequest = null
        if (this.$btn.length) {
            this.$formFieldRow = $('.node-type-field-row')
            this.$formFieldCol = $('.node-type-field-col')

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
     */
    init () {}

    unbind () {}

    /**
     * Btn click
     * @param {Event} e
     * @returns {boolean}
     */
    btnClick (e) {
        e.preventDefault()

        if (this.indexOpen !== null) {
            this.closeForm()
            this.openFormDelay = 400
        } else this.openFormDelay = 0

        if (this.indexOpen !== parseInt(e.currentTarget.getAttribute('data-index'))) {
            if (this.currentRequest && this.currentRequest.readyState !== 4) {
                this.currentRequest.abort()
            }
            window.Rozier.lazyload.canvasLoader.show()

            if (this.openTimeout) {
                clearTimeout(this.openTimeout)
            }

            this.openTimeout = setTimeout(() => {
                // Trigger event on window to notify open
                // widgets to close.
                let pageChangeEvent = new CustomEvent('pagechange')
                window.dispatchEvent(pageChangeEvent)

                this.indexOpen = parseInt(e.currentTarget.getAttribute('data-index'))

                this.currentRequest = $.ajax({
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
                    .always(() => {
                        window.Rozier.lazyload.canvasLoader.hide()
                    })
            }, this.openFormDelay)
        }

        return false
    }

    /**
     * Apply content
     * @param target
     * @param data
     * @param url
     */
    applyContent (target, data, url) {
        let dataWrapped = [
            '<tr class="node-type-field-edit-form-row">',
            '<td colspan="5">',
            '<div class="node-type-field-edit-form-cont">',
            data,
            '</div>',
            '</td>',
            '</tr>'
        ].join('')

        $(target).parent().parent().after(dataWrapped)

        // Remove class to pause sortable actions
        this.$formFieldCol.removeClass('node-type-field-col')

        // Switch checkboxes
        $('.rz-boolean-checkbox').bootstrapSwitch({
            size: 'small'
        })

        window.Rozier.lazyload.initMarkdownEditors()

        window.setTimeout(() => {
            this.$formCont = $('.node-type-field-edit-form-cont')
            this.formContHeight = this.$formCont.actual('height')
            this.$formRow = $('.node-type-field-edit-form-row')
            this.$form = $('#edit-node-type-field-form')
            this.$formIcon = $(this.$formFieldRow[this.indexOpen]).find('.node-type-field-col-1 i')

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

        TweenLite.to(this.$formCont, 0.4, {height: 0,
            ease: Expo.easeOut,
            onComplete: () => {
                this.$formRow.remove()
                this.indexOpen = null
                this.$formFieldCol.addClass('node-type-field-col')
            }})
    }

    /**
     * Window resize callback
     */
    resize () {}
}
