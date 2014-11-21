/**
 * Documents bulk
 */

var DocumentsBulk = function () {
    var _this = this;

    _this.$documentsCheckboxes = $('input.document-checkbox');
    _this.$documentsIdBulkFolders = $('input.document-id-bulk-folder');
    _this.$actionsMenu = $('.documents-bulk-actions');
    _this.$documentsFolderButton = $('.uk-button-bulk-folder-documents');
    _this.$documentsFolderCont = $('.documents-bulk-folder-cont');

    if (_this.$documentsCheckboxes.length) {
        _this.init();
    }
};


DocumentsBulk.prototype.$documentsCheckboxes = null;
DocumentsBulk.prototype.$documentsIdBulkFolders = null;
DocumentsBulk.prototype.$actionsMenu = null;
DocumentsBulk.prototype.$documentsFolderButton = null;
DocumentsBulk.prototype.$documentsFolderCont = null;
DocumentsBulk.prototype.documentsFolderOpen = false;
DocumentsBulk.prototype.documentsIds = null;

/**
 * Init
 * @return {[type]} [description]
 */
DocumentsBulk.prototype.init = function() {
    var _this = this;

    var proxy = $.proxy(_this.onCheckboxChange, _this);
    _this.$documentsCheckboxes.off('change', proxy);
    _this.$documentsCheckboxes.on('change', proxy);

    var $bulkDeleteButton = _this.$actionsMenu.find('.uk-button-bulk-delete-documents');
    var deleteProxy = $.proxy(_this.onBulkDelete, _this);
    $bulkDeleteButton.off('click', deleteProxy);
    $bulkDeleteButton.on('click', deleteProxy);

    _this.$documentsFolderButton.on('click', $.proxy(_this.documentsFolderButtonClick, _this));
};


/**
 * On checkbox change
 * @param  {[type]} event [description]
 * @return {[type]}       [description]
 */
DocumentsBulk.prototype.onCheckboxChange = function(event) {
    var _this = this;

    _this.documentsIds = [];
    $("input.document-checkbox:checked").each(function(index,domElement) {
        _this.documentsIds.push($(domElement).val());
    });

    if(_this.$documentsIdBulkFolders.length){
        _this.$documentsIdBulkFolders.val(_this.documentsIds.join(','));
    }

    // console.log(_this.documentsIds);

    if(_this.documentsIds.length > 0){
        _this.showActions();
    } else {
        _this.hideActions();
    }
};


/**
 * On bulk delete
 * @param  {[type]} event [description]
 * @return {[type]}       [description]
 */
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


/**
 * Show actions
 * @return {[type]} [description]
 */
DocumentsBulk.prototype.showActions = function () {
    var _this = this;

    _this.$actionsMenu.slideDown();
    //_this.$actionsMenu.addClass('visible');
};


/**
 * Hide actions
 * @return {[type]} [description]
 */
DocumentsBulk.prototype.hideActions = function () {
    var _this = this;

    _this.$actionsMenu.slideUp();
    //_this.$actionsMenu.removeClass('visible');
};


/**
 * Documents folder button click
 * @return {[type]} [description]
 */
DocumentsBulk.prototype.documentsFolderButtonClick = function(e){
    var _this = this;

    if(!_this.documentsFolderOpen){
        _this.$documentsFolderCont.slideDown();
        _this.documentsFolderOpen = true;
    }
    else{        
        _this.$documentsFolderCont.slideUp();
        _this.documentsFolderOpen = false;
    }

};
