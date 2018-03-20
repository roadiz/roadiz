import $ from 'jquery'

/**
 * Custom form fields position
 */
export default class CustomFormFieldsPosition {
    constructor () {
        this.$list = $('.custom-form-fields > .uk-sortable')
        this.onSortableChange = this.onSortableChange.bind(this)
        this.init()
    }

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

    onSortableChange (event, list, element) {
        let $element = $(element)
        let customFormFieldId = parseInt($element.data('field-id'))
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
            'customFormFieldId': customFormFieldId,
            'newPosition': newPosition
        }

        $.ajax({
            url: window.Rozier.routes.customFormsFieldAjaxEdit.replace('%customFormFieldId%', customFormFieldId),
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
