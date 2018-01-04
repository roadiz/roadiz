import $ from 'jquery'

/**
 * Documents list
 */
export default class DocumentsList {
    constructor () {
        // Selectors
        this.$cont = $('.documents-list')

        if (this.$cont.length) this.$item = this.$cont.find('.document-item')

        this.contWidth = null
        this.itemWidth = 144 // (w : 128 + mr : 16)
        this.itemsPerLine = 4
        this.itemsWidth = 576
        this.contMarginLeft = 0
    }

    /**
     * Window resize callback
     */
    resize () {}
}
