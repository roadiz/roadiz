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
        this.bindKeyboard()

        if (this.$button.length &&
            this.$actionMenu.length) {
            this.init()
        }
    }

    init () {
        let formToSave = $(this.$button.attr('data-action-save'))

        if (formToSave.length) {
            this.$button.prependTo(this.$actionMenu)
            this.$button.on('click', () => {
                formToSave.submit()
            })

            window.Mousetrap.bind(['mod+s'], () => {
                console.log('Save requested')
                formToSave.submit()

                return false
            })
        }
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
