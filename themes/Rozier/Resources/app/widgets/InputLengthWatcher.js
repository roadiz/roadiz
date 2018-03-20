import $ from 'jquery'

export default class InputLengthWatcher {
    constructor () {
        this.$maxLengthed = $('input[data-max-length]')
        this.$minLengthed = $('input[data-min-length]')

        this.onMaxKeyUp = this.onMaxKeyUp.bind(this)
        this.onMinKeyUp = this.onMinKeyUp.bind(this)

        this.init()
    }

    init () {
        if (this.$maxLengthed.length) {
            this.$maxLengthed.on('keyup', this.onMaxKeyUp)
        }

        if (this.$minLengthed.length) {
            this.$minLengthed.on('keyup', this.onMinKeyUp)
        }
    }

    unbind () {
        if (this.$maxLengthed.length) {
            this.$maxLengthed.off('keyup', this.onMaxKeyUp)
        }

        if (this.$minLengthed.length) {
            this.$minLengthed.off('keyup', this.onMinKeyUp)
        }
    }

    /**
     * @param {Event} event
     */
    onMaxKeyUp (event) {
        let input = $(event.currentTarget)
        let maxLength = Math.round(event.currentTarget.getAttribute('data-max-length'))
        let currentLength = event.currentTarget.value.length

        if (currentLength > maxLength) {
            input.addClass('uk-form-danger')
        } else {
            input.removeClass('uk-form-danger')
        }
    }

    /**
     * @param {Event} event
     */
    onMinKeyUp (event) {
        let input = $(event.currentTarget)
        let maxLength = Math.round(event.currentTarget.getAttribute('data-min-length'))
        let currentLength = event.currentTarget.value.length

        if (currentLength <= maxLength) {
            input.addClass('uk-form-danger')
        } else {
            input.removeClass('uk-form-danger')
        }
    }
}
