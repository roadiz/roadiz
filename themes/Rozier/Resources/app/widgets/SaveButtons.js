import $ from 'jquery'

/*
 * You can add automatically form button to actions-menus
 * Just add them to the .rz-action-save class and use the data-action-save
 * attribute to point form ID to submit.
 */
export default class SaveButtons {
    constructor () {
        this.$button = $($('.rz-action-save').get(0))
        this.$actionMenu = $($('.actions-menu').get(0))
        this.formToSave = null

        // Bind method
        this.onClick = this.onClick.bind(this)

        this.bindKeyboard()

        if (this.$button.length &&
            this.$actionMenu.length) {
            this.init()
        }
    }

    init () {
        this.formToSave = $(this.$button.attr('data-action-save'))

        if (this.formToSave.length) {
            this.$button.prependTo(this.$actionMenu)
            this.$button.on('click', this.onClick)

            window.Mousetrap.bind(['mod+s'], () => {
                console.debug('Save requested')
                this.formToSave.submit()

                return false
            })
        }
    }

    unbind () {
        if (this.formToSave && this.formToSave.length) {
            this.$button.off('click', this.onClick)
        }
    }

    onClick () {
        this.formToSave.submit()
    }

    bindKeyboard () {
        window.Mousetrap.stopCallback = (e, element) => {
            // if the element has the class "mousetrap" then no need to stop
            if ((' ' + element.className + ' ').indexOf(' mousetrap ') > -1) {
                return false
            }

            // stop for input, select, and textarea
            return element.tagName === 'SELECT'
        }
    }
}
