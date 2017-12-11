import $ from 'jquery'

/*
 * You can add automatically form button to actions-menus
 * Just add them to the .rz-action-save class and use the data-action-save
 * attribute to point form ID to submit.
 */
export default function SaveButtons () {
    var _this = this

    _this.$button = $($('.rz-action-save').get(0))
    _this.$actionMenu = $($('.actions-menu').get(0))
    _this.bindKeyboard()

    if (_this.$button.length &&
        _this.$actionMenu.length) {
        _this.init()
    }
}

SaveButtons.prototype.init = function () {
    var _this = this

    var formToSave = $(_this.$button.attr('data-action-save'))

    if (formToSave.length) {
        _this.$button.prependTo(_this.$actionMenu)
        _this.$button.on('click', function (event) {
            formToSave.submit()
        })
        window.Mousetrap.bind(['mod+s'], function (e) {
            console.log('Save requested')
            formToSave.submit()

            return false
        })
    }
}

SaveButtons.prototype.bindKeyboard = function () {
    window.Mousetrap.stopCallback = function (e, element, combo) {
        // if the element has the class "mousetrap" then no need to stop
        if ((' ' + element.className + ' ').indexOf(' mousetrap ') > -1) {
            return false
        }

        // stop for input, select, and textarea
        return element.tagName === 'SELECT'
    }
}
