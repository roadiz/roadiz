var DocumentsBulk = function () {
    var _this = this;

    _this.$documentsCheckboxes = $('input.document-checkbox');
    _this.$actionsMenu = $('.documents-bulk-actions');

    if (_this.$documentsCheckboxes.length) {
        _this.init();
    }
};
DocumentsBulk.prototype.$documentsCheckboxes = null;
DocumentsBulk.prototype.$actionsMenu = null;
DocumentsBulk.prototype.documentsIds = null;
DocumentsBulk.prototype.init = function() {
    var _this = this;

    var proxy = $.proxy(_this.onCheckboxChange, _this);
    _this.$documentsCheckboxes.off('change', proxy);
    _this.$documentsCheckboxes.on('change', proxy);

    var $bulkDeleteButton = _this.$actionsMenu.find('.document-bulk-delete');
    var deleteProxy = $.proxy(_this.onBulkDelete, _this);
    $bulkDeleteButton.off('click', deleteProxy);
    $bulkDeleteButton.on('click', deleteProxy);
};

DocumentsBulk.prototype.onCheckboxChange = function(event) {
    var _this = this;

    _this.documentsIds = [];
    $("input.document-checkbox:checked").each(function(index,domElement) {
        _this.documentsIds.push($(domElement).val());
    });

    console.log(_this.documentsIds);

    if(_this.documentsIds.length > 0){
        _this.showActions();
    } else {
        _this.hideActions();
    }
};

DocumentsBulk.prototype.onBulkDelete = function(event) {
    var _this = this;

    if(_this.documentsIds.length > 0){

        history.pushState({
            'headerData' : {
                'documents': _this.documentsIds
            }
        }, null, Rozier.routes.documentsBulkDeletePage);

        Rozier.lazyload.onPopState(null);
    }

    return false;
};

DocumentsBulk.prototype.showActions = function () {
    var _this = this;

    _this.$actionsMenu.slideDown();
    //_this.$actionsMenu.addClass('visible');
};

DocumentsBulk.prototype.hideActions = function () {
    var _this = this;

    _this.$actionsMenu.slideUp();
    //_this.$actionsMenu.removeClass('visible');
};