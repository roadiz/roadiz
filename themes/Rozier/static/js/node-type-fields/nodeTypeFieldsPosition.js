var NodeTypeFieldsPosition = function () {
    var _this = this;

    _this.$list = $(".node-type-fields > .uk-sortable");
    _this.currentRequest = null;
    _this.init();
};

NodeTypeFieldsPosition.prototype.init = function() {
    var _this = this;

    if (_this.$list.length &&
        _this.$list.children().length > 1) {
        var onChange = $.proxy(_this.onSortableChange, _this);
        _this.$list.off('change.uk.sortable', onChange);
        _this.$list.on('change.uk.sortable', onChange);
    }
};

NodeTypeFieldsPosition.prototype.onSortableChange = function(event, list, element) {
    var _this = this;

    if(_this.currentRequest && _this.currentRequest.readyState != 4){
        _this.currentRequest.abort();
    }

    var $element = $(element);
    var nodeTypeFieldId = parseInt($element.data('field-id'));
    var $sibling = $element.prev();
    var newPosition = 0.0;

    if ($sibling.length === 0) {
        $sibling = $element.next();
        newPosition = parseInt($sibling.data('position')) - 0.5;
    } else {
        newPosition = parseInt($sibling.data('position')) + 0.5;
    }

    console.log("nodeTypeFieldId="+nodeTypeFieldId+"; newPosition="+newPosition);


    var postData = {
        '_token':          Rozier.ajaxToken,
        '_action':         'updatePosition',
        'nodeTypeFieldId': nodeTypeFieldId,
        'newPosition':     newPosition
    };

    _this.currentRequest = $.ajax({
        url: Rozier.routes.nodeTypesFieldAjaxEdit.replace("%nodeTypeFieldId%", nodeTypeFieldId),
        type: 'POST',
        dataType: 'json',
        data: postData,
    })
    .done(function(data) {
        //console.log(data);
        $element.attr('data-position', newPosition);
        UIkit.notify({
            message : data.responseText,
            status  : data.status,
            timeout : 3000,
            pos     : 'top-center'
        });
    })
    .fail(function(data) {
        console.log(data);
    });

};
