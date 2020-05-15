import $ from 'jquery'

export default class AttributeValuePosition {
    /**
     * Constructor
     */
    constructor () {
        this.$list = $('.attribute-value-forms > .uk-sortable')
        this.currentRequest = null

        // Bind methods
        this.onSortableChange = this.onSortableChange.bind(this)

        this.init()
    }

    /**
     * Init
     */
    init () {
        if (this.$list.length && this.$list.children().length > 1) {
            this.$list.on('change.uk.sortable', this.onSortableChange)
        }
    }

    unbind () {
        if (this.$list.length && this.$list.children().length > 1) {
            this.$list.off('change.uk.sortable', this.onSortableChange)
        }
    }

    /**
     * @param event
     * @param list
     * @param element
     */
    onSortableChange (event, list, element) {
        if (this.currentRequest && this.currentRequest.readyState !== 4) {
            this.currentRequest.abort()
        }

        if (event.target instanceof HTMLInputElement) {
            return
        }

        let $element = $(element)
        let attributeValueId = parseInt($element.data('id'))
        let $sibling = $element.prev()
        let newPosition = 0.0

        if ($sibling.length === 0) {
            $sibling = $element.next()
            newPosition = parseInt($sibling.data('position')) - 0.5
        } else {
            newPosition = parseInt($sibling.data('position')) + 0.5
        }

        let postData = {
            '_token': window.Rozier.ajaxToken,
            '_action': 'updatePosition',
            'attributeValueId': attributeValueId,
            'newPosition': newPosition
        }
        // TODO: entry point
        if (window.Rozier.routes.attributeValueAjaxEdit) {
            this.currentRequest = $.ajax({
                url: window.Rozier.routes.attributeValueAjaxEdit.replace('%attributeValueId%', attributeValueId),
                type: 'POST',
                dataType: 'json',
                data: postData
            })
            .done(data => {
                $element.attr('data-position', newPosition)
                window.UIkit.notify({
                    message: data.responseText,
                    status: data.status,
                    timeout: 3000,
                    pos: 'top-center'
                })
            })
            .fail(data => {
                data = JSON.parse(data.responseText)
                window.UIkit.notify({
                    message: data.error_message,
                    status: 'danger',
                    timeout: 3000,
                    pos: 'top-center'
                })
            })
        }
    }
}
