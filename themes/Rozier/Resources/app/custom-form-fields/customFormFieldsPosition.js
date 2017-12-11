import $ from 'jquery'

export default function CustomFormFieldsPosition () {
    var _this = this

    _this.$list = $('.custom-form-fields > .uk-sortable')

    _this.init()
};

CustomFormFieldsPosition.prototype.init = function () {
    var _this = this

    if (_this.$list.length &&
        _this.$list.children().length > 1) {
        var onChange = $.proxy(_this.onSortableChange, _this)
        _this.$list.off('change.uk.sortable', onChange)
        _this.$list.on('change.uk.sortable', onChange)
    }
}

CustomFormFieldsPosition.prototype.onSortableChange = function (event, list, element) {
    var $element = $(element)
    var customFormFieldId = parseInt($element.data('field-id'))
    var $sibling = $element.prev()
    var newPosition = 0.0

    if ($sibling.length === 0) {
        $sibling = $element.next()
        newPosition = parseInt($sibling.data('position')) - 0.5
    } else {
        newPosition = parseInt($sibling.data('position')) + 0.5
    }

    console.log('customFormFieldId=' + customFormFieldId + '; newPosition=' + newPosition)

    var postData = {
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
    .done(function (data) {
        // console.log(data);
        $element.attr('data-position', newPosition)
        window.UIkit.notify({
            message: data.responseText,
            status: data.status,
            timeout: 3000,
            pos: 'top-center'
        })
    })
    .fail(function (data) {
        console.log(data)
    })
}
