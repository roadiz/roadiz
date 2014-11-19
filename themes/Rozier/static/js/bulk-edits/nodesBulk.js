/**
 * Nodes bulk
 */

var NodesBulk = function () {
    var _this = this;

    _this.$nodesCheckboxes = $('input.node-checkbox');
    _this.$nodesIdBulkTags = $('input.nodes-id-bulk-tags');
    _this.$actionsMenu = $('.nodes-bulk-actions');

    if (_this.$nodesCheckboxes.length) {
        _this.init();
    }
};


NodesBulk.prototype.$nodesCheckboxes = null;
NodesBulk.prototype.$nodesIdBulkTags = null;
NodesBulk.prototype.$actionsMenu = null;
NodesBulk.prototype.nodesIds = null;

/**
 * Init
 * @return {[type]} [description]
 */
NodesBulk.prototype.init = function() {
    var _this = this;

    var proxy = $.proxy(_this.onCheckboxChange, _this);
    _this.$nodesCheckboxes.off('change', proxy);
    _this.$nodesCheckboxes.on('change', proxy);

    //var $bulkDeleteButton = _this.$actionsMenu.find('.node-bulk-delete');
    //var deleteProxy = $.proxy(_this.onBulkDelete, _this);
    //$bulkDeleteButton.off('click', deleteProxy);
    //$bulkDeleteButton.on('click', deleteProxy);
};


/**
 * On checkbox change
 * @param  {[type]} event [description]
 * @return {[type]}       [description]
 */
NodesBulk.prototype.onCheckboxChange = function(event) {
    var _this = this;

    _this.nodesIds = [];
    $("input.node-checkbox:checked").each(function(index,domElement) {
        _this.nodesIds.push($(domElement).val());
    });

    if(_this.$nodesIdBulkTags.length){
        _this.$nodesIdBulkTags.val(_this.nodesIds.join(','));
    }

    console.log(_this.nodesIds);

    if(_this.nodesIds.length > 0){
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
NodesBulk.prototype.onBulkDelete = function(event) {
    var _this = this;

    if(_this.nodesIds.length > 0){

        history.pushState({
            'headerData' : {
                'nodes': _this.nodesIds
            }
        }, null, Rozier.routes.nodesBulkDeletePage);

        Rozier.lazyload.onPopState(null);
    }

    return false;
};


/**
 * Show actions
 * @return {[type]} [description]
 */
NodesBulk.prototype.showActions = function () {
    var _this = this;

    _this.$actionsMenu.slideDown();
    //_this.$actionsMenu.addClass('visible');
};


/**
 * Hide actions
 * @return {[type]} [description]
 */
NodesBulk.prototype.hideActions = function () {
    var _this = this;

    _this.$actionsMenu.slideUp();
    //_this.$actionsMenu.removeClass('visible');
};