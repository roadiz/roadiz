import $ from 'jquery'
import {
    addClass
} from '../utils/plugins'

/**
 * Node Tree
 */
export default class NodeTree {
    constructor () {
        // Selectors
        this.$content = $('.content-node-tree')
        this.$elements = null
        this.$dropdown = null

        // Methods
        if (this.$content.length) {
            this.$dropdown = this.$content.find('.uk-dropdown-small')
            this.init()
        }
    }

    /**
     * Init
     */
    init () {
        this.contentHeight = this.$content.actual('outerHeight')

        if (this.contentHeight >= (window.Rozier.windowHeight - 400)) this.dropdownFlip()
    }

    unbind () {}

    /**
     * Flip dropdown
     */
    dropdownFlip () {
        for (let i = this.$dropdown.length - 1; i >= this.$dropdown.length - 3; i--) {
            addClass(this.$dropdown[i], 'uk-dropdown-up')
        }
    }
}
