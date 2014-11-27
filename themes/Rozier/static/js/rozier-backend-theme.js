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

    var $bulkDownloadButton = _this.$actionsMenu.find('.uk-button-bulk-download-documents');
    var downloadProxy = $.proxy(_this.onBulkDownload, _this);
    $bulkDownloadButton.off('click', downloadProxy);
    $bulkDownloadButton.on('click', downloadProxy);
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
 * On bulk Download
 * @param  {[type]} event [description]
 * @return {[type]}       [description]
 */
DocumentsBulk.prototype.onBulkDownload = function(event) {
    var _this = this;

    if(_this.documentsIds.length > 0){

        history.pushState({
            'headerData' : {
                'documents': _this.documentsIds
            }
        }, null, Rozier.routes.documentsBulkDownloadPage);

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
;/**
 * Nodes bulk
 */

var NodesBulk = function () {
    var _this = this;

    _this.$nodesCheckboxes = $('input.node-checkbox');
    _this.$nodesIdBulkTags = $('input.nodes-id-bulk-tags');
    _this.$actionsMenu = $('.nodes-bulk-actions');
    _this.$nodesFolderButton = $('.uk-button-bulk-folder-nodes');
    _this.$nodesFolderCont = $('.nodes-bulk-folder-cont');

    if (_this.$nodesCheckboxes.length) {
        _this.init();
    }
};


NodesBulk.prototype.$nodesCheckboxes = null;
NodesBulk.prototype.$nodesIdBulkTags = null;
NodesBulk.prototype.$actionsMenu = null;
NodesBulk.prototype.$nodesFolderButton = null;
NodesBulk.prototype.$nodesFolderCont = null;
NodesBulk.prototype.nodesFolderOpen = false;
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

    _this.$nodesFolderButton.on('click', $.proxy(_this.nodesFolderButtonClick, _this));

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


/**
 * Nodes folder button click
 * @return {[type]} [description]
 */
NodesBulk.prototype.nodesFolderButtonClick = function(e){
    var _this = this;

    if(!_this.nodesFolderOpen){
        _this.$nodesFolderCont.slideDown();
        _this.nodesFolderOpen = true;
    }
    else{        
        _this.$nodesFolderCont.slideUp();
        _this.nodesFolderOpen = false;
    }

};
;/**
 * Documents list
 */

DocumentsList = function(){
    var _this = this;

    // Selectors
    _this.$cont = $('.documents-list');
    if(_this.$cont.length) _this.$item = _this.$cont.find('.document-item');

    _this.resize();
};


DocumentsList.prototype.$cont = null;
DocumentsList.prototype.contWidth = null;
DocumentsList.prototype.$item = null;
DocumentsList.prototype.itemWidth = 144; // (w : 128 + mr : 16)
DocumentsList.prototype.itemsPerLine = 4;
DocumentsList.prototype.itemsWidth = 576;
DocumentsList.prototype.contMarginLeft = 0;


/**
 * Window resize callback
 * @return {[type]} [description]
 */
DocumentsList.prototype.resize = function(){
    var _this = this;

    // console.log('documents list resize');

    if(_this.$cont.length){
        _this.contWidth = _this.$cont.actual('width');
        _this.itemsPerLine = Math.floor(_this.contWidth / _this.itemWidth);
        _this.itemsWidth = (_this.itemWidth * _this.itemsPerLine) - 16;
        _this.contMarginLeft = Math.floor((_this.contWidth - _this.itemsWidth)/2);

        _this.$cont[0].style.marginLeft = _this.contMarginLeft+'px'; 

        // console.log('cont width  : '+_this.contWidth);
        // console.log('item width  : '+_this.itemWidth);
        // console.log('items /line : '+_this.itemsPerLine);
        // console.log('items width : '+_this.itemsWidth);
        // console.log('cont ml     : '+_this.contMarginLeft);
        // console.log('-----------------------');
    }

};
;/**
 * DOCUMENT WIDGET
 */
var DocumentWidget = function () {
    var _this = this;

    _this.$widgets = $('[data-document-widget]');
    _this.$sortables = $('.documents-widget-sortable');
    _this.$toggleExplorerButtons = $('[data-document-widget-toggle-explorer]');
    _this.$toggleUploaderButtons = $('[data-document-widget-toggle-uploader]');
    _this.$unlinkDocumentButtons = $('[data-document-widget-unlink-document]');

    _this.init();
};

DocumentWidget.prototype.$explorer = null;
DocumentWidget.prototype.$explorerClose = null;
DocumentWidget.prototype.$widgets = null;
DocumentWidget.prototype.$toggleExplorerButtons = null;
DocumentWidget.prototype.$unlinkDocumentButtons = null;
DocumentWidget.prototype.$sortables = null;
DocumentWidget.prototype.uploader = null;


/**
 * Init.
 *
 * @return {[type]} [description]
 */
DocumentWidget.prototype.init = function() {
    var _this = this;

    var changeProxy = $.proxy(_this.onSortableDocumentWidgetChange, _this);
    _this.$sortables.on('uk.sortable.change', changeProxy);
    _this.$sortables.on('uk.sortable.change', changeProxy);

    var onExplorerToggleP = $.proxy(_this.onExplorerToggle, _this);
    _this.$toggleExplorerButtons.off('click', onExplorerToggleP);
    _this.$toggleExplorerButtons.on('click', onExplorerToggleP);

    var onUploaderToggleP = $.proxy(_this.onUploaderToggle, _this);
    _this.$toggleUploaderButtons.off('click', onUploaderToggleP);
    _this.$toggleUploaderButtons.on('click', onUploaderToggleP);

    var onUnlinkDocumentP = $.proxy(_this.onUnlinkDocument, _this);
    _this.$unlinkDocumentButtons.off('click', onUnlinkDocumentP);
    _this.$unlinkDocumentButtons.on('click', onUnlinkDocumentP);

    Rozier.$window.on('keyup', $.proxy(_this.echapKey, _this));
};


/**
 * Update document widget input values after being sorted.
 *
 * @param  {[type]} event   [description]
 * @param  {[type]} element [description]
 * @return {void}
 */
DocumentWidget.prototype.onSortableDocumentWidgetChange = function(event, list, element) {
    var _this = this;

    //console.log("Document: "+element.data('document-id'));
    console.log(element);
    $sortable = $(element).parent();
    var inputName = 'source['+$sortable.data('input-name')+']';
    $sortable.find('li').each(function (index) {
        $(this).find('input').attr('name', inputName+'['+index+']');
    });

    return false;
};


/**
 * On uploader toggle
 * @param  {[type]} event [description]
 * @return {[type]}       [description]
 */
DocumentWidget.prototype.onUploaderToggle = function(event) {
    var _this = this;

    //documents-widget
    var $btn = $(event.currentTarget);
    var $widget = $btn.parents('.documents-widget');

    if (null !== _this.uploader) {
        _this.uploader = null;
        var $uploader = $widget.find('.documents-widget-uploader');
        $uploader.slideUp(500, function () {
            $uploader.remove();
            $btn.removeClass('active');
        });
    } else {

        $widget.append('<div class="documents-widget-uploader dropzone"></div>');
        var $uploaderNew = $widget.find('.documents-widget-uploader');

        var options = {
            selector: '.documents-widget .documents-widget-uploader',
            headers: { "_token": Rozier.ajaxToken },
            onSuccess : function (data) {
                console.log(data);

                if(typeof data.thumbnail !== "undefined") {
                    var $sortable = $widget.find('.documents-widget-sortable');
                    $sortable.append(data.thumbnail.html);

                    var $element = $sortable.find('[data-document-id="'+data.thumbnail.id+'"]');

                    _this.onSortableDocumentWidgetChange(null, $sortable, $element);
                }
            }
        };

        $.extend(options, Rozier.messages.dropzone);

        console.log(options);
        _this.uploader = new DocumentUploader(options);

        $uploaderNew.slideDown(500);
        $btn.addClass('active');
    }

    return false;
};


/**
 * Create document explorer.
 *
 * @param  {[type]} event [description]
 * @return false
 */
DocumentWidget.prototype.onExplorerToggle = function(event) {
    var _this = this;

    if (_this.$explorer === null) {

        _this.$toggleExplorerButtons.addClass('uk-active');

        var ajaxData = {
            '_action':'toggleExplorer',
            '_token': Rozier.ajaxToken
        };

        Rozier.lazyload.canvasLoader.show();

        $.ajax({
            url: Rozier.routes.documentsAjaxExplorer,
            type: 'get',
            dataType: 'json',
            data: ajaxData
        })
        .success(function(data) {
            console.log(data);
            console.log("success");
            Rozier.lazyload.canvasLoader.hide();

            if (typeof data.documents != "undefined") {

                var $currentsortable = $($(event.currentTarget).parents('.documents-widget')[0]).find('.documents-widget-sortable');
                _this.createExplorer(data, $currentsortable);
            }
        })
        .fail(function(data) {
            console.log(data.responseText);
            console.log("error");
        });
    }
    else _this.closeExplorer();

    return false;
};


/**
 * Query searched documents explorer.
 *
 * @param  {[type]} event   [description]
 * @return false
 */
DocumentWidget.prototype.onExplorerSearch = function($originWidget, event) {
    var _this = this;

    if (_this.$explorer !== null){
        var $search = $(event.currentTarget).find('#documents-search-input');

        var ajaxData = {
            '_action':'toggleExplorer',
            '_token': Rozier.ajaxToken,
            'search': $search.val()
        };

        Rozier.lazyload.canvasLoader.show();

        $.ajax({
            url: Rozier.routes.documentsAjaxExplorer,
            type: 'get',
            dataType: 'json',
            data: ajaxData
        })
        .success(function(data) {
            console.log(data);
            console.log("success");
            Rozier.lazyload.canvasLoader.hide();

            if (typeof data.documents != "undefined") {

                _this.appendItemsToExplorer(data, $originWidget, true);
            }
        })
        .fail(function(data) {
            console.log(data.responseText);
            console.log("error");
        });
    }

    return false;
};


/**
 * Query next page documents explorer.
 *
 * @param  {[type]} filters [description]
 * @param  {[type]} event   [description]
 * @return false
 */
DocumentWidget.prototype.onExplorerNextPage = function(filters, $originWidget, event) {
    var _this = this;

    if (_this.$explorer !== null){
        console.log(filters);
        var ajaxData = {
            '_action':'toggleExplorer',
            '_token': Rozier.ajaxToken,
            'search': filters.search,
            'page': filters.nextPage
        };

        Rozier.lazyload.canvasLoader.show();

        $.ajax({
            url: Rozier.routes.documentsAjaxExplorer,
            type: 'get',
            dataType: 'json',
            data: ajaxData
        })
        .success(function(data) {
            console.log(data);
            console.log("success");
            Rozier.lazyload.canvasLoader.hide();

            if (typeof data.documents != "undefined") {
                _this.appendItemsToExplorer(data, $originWidget);
            }
        })
        .fail(function(data) {
            console.log(data.responseText);
            console.log("error");
        });
    }

    return false;
};


/**
 * Unlink document.
 *
 * @param  {[type]} event [description]
 * @return {[type]}       [description]
 */
DocumentWidget.prototype.onUnlinkDocument = function( event ) {
    var _this = this;

    var $element = $(event.currentTarget);

    var $doc = $element.parents('li');
    var $widget = $element.parents('.documents-widget-sortable').first();

    $doc.remove();
    $widget.trigger('uk.sortable.change', [$widget, $doc]);

    return false;
};


/**
 * Populate explorer with documents thumbnails
 * @param  {[type]} data [description]
 * @return {[type]}      [description]
 */
DocumentWidget.prototype.createExplorer = function(data, $originWidget) {
    var _this = this;
    // console.log($originWidget);
    var changeProxy = $.proxy(_this.onSortableDocumentWidgetChange, _this);

    var explorerDom = [
        '<div class="document-widget-explorer">',
            '<div class="document-widget-explorer-header">',
                '<div class="document-widget-explorer-logo"><i class="uk-icon-rz-folder-tree-mini"></i></div>',
                '<div class="document-widget-explorer-search">',
                    '<form action="#" method="POST" class="explorer-search uk-form">',
                        '<div class="uk-form-icon">',
                            '<i class="uk-icon-search"></i>',
                            '<input id="documents-search-input" type="search" name="searchTerms" value="" placeholder="'+Rozier.messages.searchDocuments+'"/>',
                        '</div>',
                    '</form>',
                '</div>',
                '<div class="document-widget-explorer-close"><i class="uk-icon-rz-close-explorer"></i></div>',
            '</div>',
            '<ul class="uk-sortable"></ul>',
        '</div>'
    ].join('');


    $("body").append(explorerDom);
    _this.$explorer = $('.document-widget-explorer');
    _this.$explorerClose = $('.document-widget-explorer-close');

    _this.$explorerClose.on('click', $.proxy(_this.closeExplorer, _this));
    _this.$explorer.find('.explorer-search').on('submit', $.proxy(_this.onExplorerSearch, _this, $originWidget));


    _this.appendItemsToExplorer(data, $originWidget);

    window.setTimeout(function () {
        _this.$explorer.addClass('visible');
    }, 0);
};


/**
 * Append documents to explorer.
 *
 * @param  Ajax data data
 * @param  jQuery $originWidget
 * @param  boolean replace Replace instead of appending
 */
DocumentWidget.prototype.appendItemsToExplorer = function(data, $originWidget, replace) {
    var _this = this;

    var $sortable = _this.$explorer.find('.uk-sortable');

    $sortable.find('.document-widget-explorer-nextpage').remove();

    if (typeof replace !== 'undefined' &&
        replace === true) {
        $sortable.empty();
    }

    /*
     * Add documents
     */
    for (var i = 0; i < data.documents.length; i++) {
        var doc = data.documents[i];
        $sortable.append(doc.html);
    }

    /*
     * Bind add buttons.
     */
    var onAddClick = $.proxy(_this.onAddDocumentClick, _this, $originWidget);
    var $links = $sortable.find('.link-button');
    $links.on('click', onAddClick);


    /*
     * Add pagination
     */
    if (typeof data.filters.nextPage !== 'undefined' &&
        data.filters.nextPage > 1) {

        $sortable.append([
            '<li class="document-widget-explorer-nextpage">',
                '<i class="uk-icon-plus"></i><span class="label">'+Rozier.messages.moreDocuments+'</span>',
            '</li>'
        ].join(''));

        $sortable.find('.document-widget-explorer-nextpage').on('click', $.proxy(_this.onExplorerNextPage, _this, data.filters, $originWidget));
    }


};


/**
 * Add document click
 * @param  {[type]} $originWidget [description]
 * @param  {[type]} event         [description]
 * @return {[type]}               [description]
 */
DocumentWidget.prototype.onAddDocumentClick = function($originWidget, event) {
    var _this = this;

    var $object = $(event.currentTarget).parents('.uk-sortable-list-item');
    $object.appendTo($originWidget);
    console.log("click");
    console.log($originWidget);
    console.log($object);

    var inputName = 'source['+$originWidget.data('input-name')+']';
    $originWidget.find('li').each(function (index, element) {
        $(element).find('input').attr('name', inputName+'['+index+']');
    });

    return false;
};


/**
 * Echap key to close explorer
 * @return {[type]} [description]
 */
DocumentWidget.prototype.echapKey = function(e){
    var _this = this;

    if(e.keyCode == 27 && _this.$explorer !== null) _this.closeExplorer();

    return false;
};


/**
 * Close explorer
 * @return {[type]} [description]
 */
DocumentWidget.prototype.closeExplorer = function(){
    var _this = this;

    _this.$toggleExplorerButtons.removeClass('uk-active');
    _this.$explorer.removeClass('visible');
    _this.$explorer.one('transitionend webkitTransitionEnd mozTransitionEnd msTransitionEnd', function(event) {
        /* Act on the event */
        _this.$explorer.remove();
        _this.$explorer = null;
    });

};
;/**
 *
 */
var NodeWidget = function () {
    var _this = this;

    _this.$widgets = $('[data-node-widget]');
    _this.$sortables = $('.nodes-widget-sortable');
    _this.$toggleExplorerButtons = $('[data-node-widget-toggle-explorer]');
    _this.$toggleUploaderButtons = $('[data-node-widget-toggle-uploader]');
    _this.$unlinkNodeButtons = $('[data-node-widget-unlink-node]');

    _this.init();
};

NodeWidget.prototype.$explorer = null;
NodeWidget.prototype.$explorerClose = null;
NodeWidget.prototype.$widgets = null;
NodeWidget.prototype.$toggleExplorerButtons = null;
NodeWidget.prototype.$unlinkNodeButtons = null;
NodeWidget.prototype.$sortables = null;
NodeWidget.prototype.uploader = null;

NodeWidget.prototype.init = function() {
    var _this = this;

    var changeProxy = $.proxy(_this.onSortableNodeWidgetChange, _this);
    _this.$sortables.on('uk.sortable.change', changeProxy);
    _this.$sortables.on('uk.sortable.change', changeProxy);

    var onExplorerToggleP = $.proxy(_this.onExplorerToggle, _this);
    _this.$toggleExplorerButtons.off('click', onExplorerToggleP);
    _this.$toggleExplorerButtons.on('click', onExplorerToggleP);

    var onUnlinkNodeP = $.proxy(_this.onUnlinkNode, _this);
    _this.$unlinkNodeButtons.off('click', onUnlinkNodeP);
    _this.$unlinkNodeButtons.on('click', onUnlinkNodeP);

    Rozier.$window.on('keyup', $.proxy(_this.echapKey, _this));
};

/**
 * Update node widget input values after being sorted.
 *
 * @param  {[type]} event   [description]
 * @param  {[type]} element [description]
 * @return {void}
 */
NodeWidget.prototype.onSortableNodeWidgetChange = function(event, list, element) {
    var _this = this;

    //console.log("Node: "+element.data('node-id'));
    console.log(element);
    $sortable = $(element).parent();
    var inputName = 'source['+$sortable.data('input-name')+']';
    $sortable.find('li').each(function (index) {
        $(this).find('input').attr('name', inputName+'['+index+']');
    });

    return false;
};

/**
 * Create node explorer.
 *
 * @param  {[type]} event [description]
 * @return false
 */
NodeWidget.prototype.onExplorerToggle = function(event) {
    var _this = this;

    if (_this.$explorer === null) {

        _this.$toggleExplorerButtons.addClass('uk-active');

        var ajaxData = {
            '_action':'toggleExplorer',
            '_token': Rozier.ajaxToken
        };

        $.ajax({
            url: Rozier.routes.nodesAjaxExplorer,
            type: 'get',
            dataType: 'json',
            data: ajaxData
        })
        .success(function(data) {
            console.log(data);
            console.log("success");

            if (typeof data.nodes != "undefined") {

                var $currentsortable = $($(event.currentTarget).parents('.nodes-widget')[0]).find('.nodes-widget-sortable');
                _this.createExplorer(data, $currentsortable);
            }
        })
        .fail(function(data) {
            console.log(data.responseText);
            console.log("error");
        });
    }
    else _this.closeExplorer();

    return false;
};

/**
 * Query searched nodes explorer.
 *
 * @param  {[type]} event   [description]
 * @return false
 */
NodeWidget.prototype.onExplorerSearch = function($originWidget, event) {
    var _this = this;

    if (_this.$explorer !== null){
        var $search = $(event.currentTarget).find('#nodes-search-input');

        var ajaxData = {
            '_action':'toggleExplorer',
            '_token': Rozier.ajaxToken,
            'search': $search.val()
        };

        $.ajax({
            url: Rozier.routes.nodesAjaxExplorer,
            type: 'get',
            dataType: 'json',
            data: ajaxData
        })
        .success(function(data) {
            console.log(data);
            console.log("success");

            if (typeof data.nodes != "undefined") {
                _this.appendItemsToExplorer(data, $originWidget, true);
            }
        })
        .fail(function(data) {
            console.log(data.responseText);
            console.log("error");
        });
    }

    return false;
};

/**
 * Query next page nodes explorer.
 *
 * @param  {[type]} filters [description]
 * @param  {[type]} event   [description]
 * @return false
 */
NodeWidget.prototype.onExplorerNextPage = function(filters, $originWidget, event) {
    var _this = this;

    if (_this.$explorer !== null){
        console.log(filters);
        var ajaxData = {
            '_action':'toggleExplorer',
            '_token': Rozier.ajaxToken,
            'search': filters.search,
            'page': filters.nextPage
        };

        $.ajax({
            url: Rozier.routes.nodesAjaxExplorer,
            type: 'get',
            dataType: 'json',
            data: ajaxData
        })
        .success(function(data) {
            console.log(data);
            console.log("success");

            if (typeof data.nodes != "undefined") {
                _this.appendItemsToExplorer(data, $originWidget);
            }
        })
        .fail(function(data) {
            console.log(data.responseText);
            console.log("error");
        });
    }

    return false;
};

NodeWidget.prototype.onUnlinkNode = function( event ) {
    var _this = this;

    var $element = $(event.currentTarget);

    var $doc = $element.parents('li');
    var $widget = $element.parents('.nodes-widget-sortable').first();

    $doc.remove();
    $widget.trigger('uk.sortable.change', [$widget, $doc]);

    return false;
};

/**
 * Populate explorer with nodes thumbnails
 * @param  {[type]} data [description]
 * @return {[type]}      [description]
 */
NodeWidget.prototype.createExplorer = function(data, $originWidget) {
    var _this = this;
    // console.log($originWidget);
    var changeProxy = $.proxy(_this.onSortableNodeWidgetChange, _this);

    var explorerDom = [
        '<div class="node-widget-explorer">',
            '<div class="node-widget-explorer-header">',
                '<div class="node-widget-explorer-search">',
                    '<form action="#" method="POST" class="explorer-search uk-form">',
                        '<div class="uk-form-icon">',
                            '<i class="uk-icon-search"></i>',
                            '<input id="nodes-search-input" type="search" name="searchTerms" value="" placeholder="'+Rozier.messages.searchNodes+'"/>',
                        '</div>',
                    '</form>',
                '</div>',
                '<div class="node-widget-explorer-close"><i class="uk-icon-rz-close-explorer"></i></div>',
            '</div>',
            '<ul class="uk-sortable"></ul>',
        '</div>'
    ].join('');


    $("body").append(explorerDom);
    _this.$explorer = $('.node-widget-explorer');
    _this.$explorerClose = $('.node-widget-explorer-close');

    _this.$explorerClose.on('click', $.proxy(_this.closeExplorer, _this));

    _this.$explorer.find('.explorer-search').on('submit', $.proxy(_this.onExplorerSearch, _this, $originWidget));
    _this.appendItemsToExplorer(data, $originWidget);

    window.setTimeout(function () {
        _this.$explorer.addClass('visible');
    }, 0);
};

/**
 * Append nodes to explorer.
 *
 * @param  Ajax data data
 * @param  jQuery $originWidget
 * @param  boolean replace Replace instead of appending
 */
NodeWidget.prototype.appendItemsToExplorer = function(data, $originWidget, replace) {
    var _this = this;

    var $sortable = _this.$explorer.find('.uk-sortable');

    $sortable.find('.node-widget-explorer-nextpage').remove();

    if (typeof replace !== 'undefined' && replace === true) {
        $sortable.empty();
    }

    /*
     * Add nodes
     */
    for (var i = 0; i < data.nodes.length; i++) {
        var node = data.nodes[i];
        $sortable.append(node.html);
    }

    /*
     * Bind add buttons.
     */
    var onAddClick = $.proxy(_this.onAddNodeClick, _this, $originWidget);
    var $links = $sortable.find('.link-button');
    $links.on('click', onAddClick);

    /*
     * Add pagination
     */
    if (typeof data.filters.nextPage !== 'undefined' &&
        data.filters.nextPage > 1) {

        $sortable.append([
            '<li class="node-widget-explorer-nextpage">',
                '<i class="uk-icon-plus"></i><span class="label">'+Rozier.messages.moreNodes+'</span>',
            '</li>'
        ].join(''));

        $sortable.find('.node-widget-explorer-nextpage').on('click', $.proxy(_this.onExplorerNextPage, _this, data.filters, $originWidget));
    }
};


NodeWidget.prototype.onAddNodeClick = function($originWidget, event) {
    var _this = this;

    var $object = $(event.currentTarget).parents('.uk-sortable-list-item');
    $object.appendTo($originWidget);

    var inputName = 'source['+$originWidget.data('input-name')+']';
    $originWidget.find('li').each(function (index, element) {
        $(element).find('input').attr('name', inputName+'['+index+']');
    });

    return false;
};

/**
 * Echap key to close explorer
 * @return {[type]} [description]
 */
NodeWidget.prototype.echapKey = function(e){
    var _this = this;

    if(e.keyCode == 27 && _this.$explorer !== null) _this.closeExplorer();

    return false;
};

/**
 * Close explorer
 * @return {[type]} [description]
 */
NodeWidget.prototype.closeExplorer = function(){
    var _this = this;

    _this.$toggleExplorerButtons.removeClass('uk-active');
    _this.$explorer.removeClass('visible');
    _this.$explorer.one('transitionend webkitTransitionEnd mozTransitionEnd msTransitionEnd', function(event) {
        /* Act on the event */
        _this.$explorer.remove();
        _this.$explorer = null;
    });

};
;/**
 *
 */
var CustomFormWidget = function () {
    var _this = this;

    _this.$widgets = $('[data-custom-form-widget]');
    _this.$sortables = $('.custom-forms-widget-sortable');
    _this.$toggleExplorerButtons = $('[data-custom-form-widget-toggle-explorer]');
    _this.$toggleUploaderButtons = $('[data-custom-form-widget-toggle-uploader]');
    _this.$unlinkCustomFormButtons = $('[data-custom-form-widget-unlink-custom-form]');

    _this.init();
};

CustomFormWidget.prototype.$explorer = null;
CustomFormWidget.prototype.$explorerClose = null;
CustomFormWidget.prototype.$widgets = null;
CustomFormWidget.prototype.$toggleExplorerButtons = null;
CustomFormWidget.prototype.$unlinkCustomFormButtons = null;
CustomFormWidget.prototype.$sortables = null;
CustomFormWidget.prototype.uploader = null;

CustomFormWidget.prototype.init = function() {
    var _this = this;

    var changeProxy = $.proxy(_this.onSortableCustomFormWidgetChange, _this);
    _this.$sortables.on('uk.sortable.change', changeProxy);
    _this.$sortables.on('uk.sortable.change', changeProxy);

    var onExplorerToggleP = $.proxy(_this.onExplorerToggle, _this);
    _this.$toggleExplorerButtons.off('click', onExplorerToggleP);
    _this.$toggleExplorerButtons.on('click', onExplorerToggleP);

    var onUnlinkCustomFormP = $.proxy(_this.onUnlinkCustomForm, _this);
    _this.$unlinkCustomFormButtons.off('click', onUnlinkCustomFormP);
    _this.$unlinkCustomFormButtons.on('click', onUnlinkCustomFormP);

    Rozier.$window.on('keyup', $.proxy(_this.echapKey, _this));
};

/**
 * Update custom-form widget input values after being sorted.
 *
 * @param  {[type]} event   [description]
 * @param  {[type]} element [description]
 * @return {void}
 */
CustomFormWidget.prototype.onSortableCustomFormWidgetChange = function(event, list, element) {
    var _this = this;

    //console.log("CustomForm: "+element.data('custom-form-id'));
    console.log(element);
    $sortable = $(element).parent();
    var inputName = 'source['+$sortable.data('input-name')+']';
    $sortable.find('li').each(function (index) {
        $(this).find('input').attr('name', inputName+'['+index+']');
    });

    return false;
};

/**
 * Create custom-form explorer.
 *
 * @param  {[type]} event [description]
 * @return false
 */
CustomFormWidget.prototype.onExplorerToggle = function(event) {
    var _this = this;

    if (_this.$explorer === null) {

        _this.$toggleExplorerButtons.addClass('uk-active');

        var ajaxData = {
            '_action':'toggleExplorer',
            '_token': Rozier.ajaxToken
        };

        $.ajax({
            url: Rozier.routes.customFormsAjaxExplorer,
            type: 'get',
            dataType: 'json',
            data: ajaxData
        })
        .success(function(data) {
            console.log(data);
            console.log("success");

            if (typeof data.customForms != "undefined") {

                var $currentsortable = $($(event.currentTarget).parents('.custom-forms-widget')[0]).find('.custom-forms-widget-sortable');
                _this.createExplorer(data, $currentsortable);
            }
        })
        .fail(function(data) {
            console.log(data.responseText);
            console.log("error");
        });
    }
    else _this.closeExplorer();

    return false;
};

/**
 * Query searched custom-forms explorer.
 *
 * @param  {[type]} event   [description]
 * @return false
 */
CustomFormWidget.prototype.onExplorerSearch = function($originWidget, event) {
    var _this = this;

    if (_this.$explorer !== null){
        var $search = $(event.currentTarget).find('#custom-forms-search-input');

        var ajaxData = {
            '_action':'toggleExplorer',
            '_token': Rozier.ajaxToken,
            'search': $search.val()
        };

        $.ajax({
            url: Rozier.routes.customFormsAjaxExplorer,
            type: 'get',
            dataType: 'json',
            data: ajaxData
        })
        .success(function(data) {
            console.log(data);
            console.log("success");

            if (typeof data.customForms != "undefined") {
                _this.appendItemsToExplorer(data, $originWidget, true);
            }
        })
        .fail(function(data) {
            console.log(data.responseText);
            console.log("error");
        });
    }

    return false;
};

/**
 * Query next page custom-forms explorer.
 *
 * @param  {[type]} filters [description]
 * @param  {[type]} event   [description]
 * @return false
 */
CustomFormWidget.prototype.onExplorerNextPage = function(filters, $originWidget, event) {
    var _this = this;

    if (_this.$explorer !== null){
        console.log(filters);
        var ajaxData = {
            '_action':'toggleExplorer',
            '_token': Rozier.ajaxToken,
            'search': filters.search,
            'page': filters.nextPage
        };

        $.ajax({
            url: Rozier.routes.customFormsAjaxExplorer,
            type: 'get',
            dataType: 'json',
            data: ajaxData
        })
        .success(function(data) {
            console.log(data);
            console.log("success");

            if (typeof data.custom-forms != "undefined") {
                _this.appendItemsToExplorer(data, $originWidget);
            }
        })
        .fail(function(data) {
            console.log(data.responseText);
            console.log("error");
        });
    }

    return false;
};

CustomFormWidget.prototype.onUnlinkCustomForm = function( event ) {
    var _this = this;

    var $element = $(event.currentTarget);

    var $doc = $element.parents('li');
    var $widget = $element.parents('.custom-forms-widget-sortable').first();

    $doc.remove();
    $widget.trigger('uk.sortable.change', [$widget, $doc]);

    return false;
};

/**
 * Populate explorer with custom-forms thumbnails
 * @param  {[type]} data [description]
 * @return {[type]}      [description]
 */
CustomFormWidget.prototype.createExplorer = function(data, $originWidget) {
    var _this = this;
    // console.log($originWidget);
    var changeProxy = $.proxy(_this.onSortableCustomFormWidgetChange, _this);

    var explorerDom = [
        '<div class="custom-form-widget-explorer">',
            '<div class="custom-form-widget-explorer-header">',
                '<div class="custom-form-widget-explorer-search">',
                    '<form action="#" method="POST" class="explorer-search uk-form">',
                        '<div class="uk-form-icon">',
                            '<i class="uk-icon-search"></i>',
                            '<input id="custom-forms-search-input" type="search" name="searchTerms" value="" placeholder="'+Rozier.messages.searchCustomForms+'"/>',
                        '</div>',
                    '</form>',
                '</div>',
                '<div class="custom-form-widget-explorer-close"><i class="uk-icon-rz-close-explorer"></i></div>',
            '</div>',
            '<ul class="uk-sortable"></ul>',
        '</div>'
    ].join('');


    $("body").append(explorerDom);
    _this.$explorer = $('.custom-form-widget-explorer');
    _this.$explorerClose = $('.custom-form-widget-explorer-close');

    _this.$explorerClose.on('click', $.proxy(_this.closeExplorer, _this));

    _this.$explorer.find('.explorer-search').on('submit', $.proxy(_this.onExplorerSearch, _this, $originWidget));
    _this.appendItemsToExplorer(data, $originWidget);

    window.setTimeout(function () {
        _this.$explorer.addClass('visible');
    }, 0);
};

/**
 * Append custom-forms to explorer.
 *
 * @param  Ajax data data
 * @param  jQuery $originWidget
 * @param  boolean replace Replace instead of appending
 */
CustomFormWidget.prototype.appendItemsToExplorer = function(data, $originWidget, replace) {
    var _this = this;

    var $sortable = _this.$explorer.find('.uk-sortable');

    $sortable.find('.custom-form-widget-explorer-nextpage').remove();

    if (typeof replace !== 'undefined' && replace === true) {
        $sortable.empty();
    }

    /*
     * Add custom-forms
     */
    for (var i = 0; i < data.customForms.length; i++) {
        var customForm = data.customForms[i];
        $sortable.append(customForm.html);
    }

    /*
     * Bind add buttons.
     */
    var onAddClick = $.proxy(_this.onAddCustomFormClick, _this, $originWidget);
    var $links = $sortable.find('.link-button');
    $links.on('click', onAddClick);

    /*
     * Add pagination
     */
    if (typeof data.filters.nextPage !== 'undefined' &&
        data.filters.nextPage > 1) {

        $sortable.append([
            '<li class="custom-form-widget-explorer-nextpage">',
                '<i class="uk-icon-plus"></i><span class="label">'+Rozier.messages.moreCustomForms+'</span>',
            '</li>'
        ].join(''));

        $sortable.find('.custom-form-widget-explorer-nextpage').on('click', $.proxy(_this.onExplorerNextPage, _this, data.filters, $originWidget));
    }
};


CustomFormWidget.prototype.onAddCustomFormClick = function($originWidget, event) {
    var _this = this;

    var $object = $(event.currentTarget).parents('.uk-sortable-list-item');
    $object.appendTo($originWidget);

    var inputName = 'source['+$originWidget.data('input-name')+']';
    $originWidget.find('li').each(function (index, element) {
        $(element).find('input').attr('name', inputName+'['+index+']');
    });

    return false;
};

/**
 * Echap key to close explorer
 * @return {[type]} [description]
 */
CustomFormWidget.prototype.echapKey = function(e){
    var _this = this;

    if(e.keyCode == 27 && _this.$explorer !== null) _this.closeExplorer();

    return false;
};

/**
 * Close explorer
 * @return {[type]} [description]
 */
CustomFormWidget.prototype.closeExplorer = function(){
    var _this = this;

    _this.$toggleExplorerButtons.removeClass('uk-active');
    _this.$explorer.removeClass('visible');
    _this.$explorer.one('transitionend webkitTransitionEnd mozTransitionEnd msTransitionEnd', function(event) {
        /* Act on the event */
        _this.$explorer.remove();
        _this.$explorer = null;
    });

};
;var DocumentUploader = function (options) {
    var _this = this;

    _this.options = {
        'onSuccess' : function (data) {
            console.log("Success file");
            console.log(data);
        },
        'onError' : function (data) {
            console.log("Failed file");
            console.log(data);
        },
        'onAdded' : function (file) {
            console.log("Added file");
        },
        'url':           Rozier.routes.documentsUploadPage,
        'selector':      "#upload-dropzone-document",
        'paramName':     "form[attachment]",
        'uploadMultiple':false,
        'maxFilesize':   64,
        'autoDiscover':  false,
        'headers': {"_token": Rozier.ajaxToken},
        'dictDefaultMessage': "Drop files here to upload or click to open your explorer",
        'dictFallbackMessage': "Your browser does not support drag'n'drop file uploads.",
        'dictFallbackText': "Please use the fallback form below to upload your files like in the olden days.",
        'dictFileTooBig': "File is too big ({{filesize}}MiB). Max filesize: {{maxFilesize}}MiB.",
        'dictInvalidFileType': "You can't upload files of this type.",
        'dictResponseError': "Server responded with {{statusCode}} code.",
        'dictCancelUpload': "Cancel upload",
        'dictCancelUploadConfirmation': "Are you sure you want to cancel this upload?",
        'dictRemoveFile': "Remove file",
        'dictRemoveFileConfirmation': null,
        'dictMaxFilesExceeded': "You can not upload any more files."
    };

    if (typeof options !== "undefined") {
        $.extend( _this.options, options );
    }
    if ($(_this.options.selector).length) {
        _this.init();
    }
};
DocumentUploader.prototype.options = null;
DocumentUploader.prototype.init = function() {
    var _this = this;

    /*
     * Get folder id
     */
    var form = $('#upload-dropzone-document');
    if (isset(form.attr('data-folder-id')) &&
        form.attr('data-folder-id') > 0) {

        _this.options.headers.folderId = parseInt(form.attr('data-folder-id'));
        _this.options.url = Rozier.routes.documentsUploadPage + '/' + parseInt(form.attr('data-folder-id'));
    }

    Dropzone.options.uploadDropzoneDocument = {
        url:                          _this.options.url,
        method:                       'post',
        headers:                      _this.options.headers,
        paramName:                    _this.options.paramName,
        uploadMultiple:               _this.options.uploadMultiple,
        maxFilesize:                  _this.options.maxFilesize,
        dictDefaultMessage:           _this.options.dictDefaultMessage,
        dictFallbackMessage:          _this.options.dictFallbackMessage,
        dictFallbackText:             _this.options.dictFallbackText,
        dictFileTooBig:               _this.options.dictFileTooBig,
        dictInvalidFileType:          _this.options.dictInvalidFileType,
        dictResponseError:            _this.options.dictResponseError,
        dictCancelUpload:             _this.options.dictCancelUpload,
        dictCancelUploadConfirmation: _this.options.dictCancelUploadConfirmation,
        dictRemoveFile:               _this.options.dictRemoveFile,
        dictRemoveFileConfirmation:   _this.options.dictRemoveFileConfirmation,
        dictMaxFilesExceeded:         _this.options.dictMaxFilesExceeded,
        init: function() {
            this.on("addedfile", function(file, data) {
                _this.options.onAdded(file);
            });
            this.on("success", function(file, data) {
                console.log(data);
                _this.options.onSuccess(JSON.parse(data));
                Rozier.getMessages();
            });
            this.on("canceled", function(file, data) {
                _this.options.onError(JSON.parse(data));
                Rozier.getMessages();
            });
        }
    };
    Dropzone.autoDiscover = _this.options.autoDiscover;


    var dropZone = new Dropzone(
        _this.options.selector,
        Dropzone.options.uploadDropzoneDocument
    );
};;/*
 * You can add automatically form button to actions-menus
 * Just add them to the .rz-action-save class and use the data-action-save
 * attribute to point form ID to submit.
 */
var SaveButtons = function () {

    var _this = this;

    _this.$button = $($(".rz-action-save").get(0));
    _this.$actionMenu = $($('.actions-menu').get(0));
    _this.bindKeyboard();

    if (_this.$button.length &&
        _this.$actionMenu.length) {

        _this.init();
    }
};

SaveButtons.prototype.$button = null;
SaveButtons.prototype.$actionMenu = null;

SaveButtons.prototype.init = function() {
    var _this = this;

    var formToSave = $(_this.$button.attr('data-action-save'));

    if (formToSave.length) {
        _this.$button.prependTo(_this.$actionMenu);
        _this.$button.on('click', function (event) {
            formToSave.submit();
        });
        Mousetrap.bind(['mod+s'], function(e) {
            console.log("Save requested");
            formToSave.submit();

            return false;
        });
    }
};

SaveButtons.prototype.bindKeyboard = function() {
    var _this = this;

    Mousetrap.stopCallback = function(e, element, combo) {

        // if the element has the class "mousetrap" then no need to stop
        if ((' ' + element.className + ' ').indexOf(' mousetrap ') > -1) {
            return false;
        }

        // stop for input, select, and textarea
        return element.tagName == 'SELECT';
    };
};;/**
 * SETTINGS SAVE BUTTONS
 */

SettingsSaveButtons = function(){
    var _this = this;

    // Selectors
    _this.$button = $('.uk-button-settings-save');

    // Methods
    if(_this.$button.length) _this.init();

    

};


SettingsSaveButtons.prototype.$button = null;


/**
 * Init
 * @return {[type]} [description]
 */
SettingsSaveButtons.prototype.init = function(){
    var _this = this;

    // Events
    _this.$button.off('click', $.proxy(_this.buttonClick, _this));
    _this.$button.on('click', $.proxy(_this.buttonClick, _this));

};


/**
 * Button click
 * @return {[type]} [description]
 */
SettingsSaveButtons.prototype.buttonClick = function(e){
    var _this = this;

    $(e.currentTarget).parent().parent().find('.uk-form').submit();

    return false;

};


/**
 * Window resize callback
 * @return {[type]} [description]
 */
SettingsSaveButtons.prototype.resize = function(){
    var _this = this;

};;/**
 * NODE EDIT SOURCE
 */

NodeEditSource = function(){
    var _this = this;

    // Selectors
    _this.$content = $('.content-node-edit-source');

    // Methods
    if(_this.$content.length) _this.init();

};


NodeEditSource.prototype.$content = null;
NodeEditSource.prototype.$input = null;


/**
 * Init
 * @return {[type]} [description]
 */
NodeEditSource.prototype.init = function(){
    var _this = this;

   _this.$input = _this.$content.find('input, select');

    for(var i = 0; i < _this.$input.length; i++) {
        
        if(_this.$input[i].getAttribute('data-desc') !== null){
            $(_this.$input[i]).after('<div class="form-help uk-alert uk-alert-large">'+_this.$input[i].getAttribute('data-desc')+'</div>');
        }   

    }    

    _this.$input.on('focus', $.proxy(_this.inputFocus, _this));
    _this.$input.on('focusout', $.proxy(_this.inputFocusOut, _this));

};


/**
 * Input focus
 * @return {[type]} [description]
 */
NodeEditSource.prototype.inputFocus = function(e){
    var _this = this;

    $(e.currentTarget).parent().addClass('form-col-focus');

};


/**
 * Input focus out
 * @return {[type]} [description]
 */
NodeEditSource.prototype.inputFocusOut = function(e){
    var _this = this;

    
    $(e.currentTarget).parent().removeClass('form-col-focus');

};






/**
 * Destroy
 * @return {[type]} [description]
 */
NodeEditSource.prototype.destroy = function(){
    var _this = this;


};


/**
 * Window resize callback
 * @return {[type]} [description]
 */
NodeEditSource.prototype.resize = function(){
    var _this = this;

};;var NodeStatuses = function () {
    var _this = this;

    _this.$containers = $(".node-statuses");
    _this.$icon = $('.node-status header i');
    _this.$inputs = _this.$containers.find('input[type="checkbox"], input[type="radio"]');
    _this.$item = _this.$containers.find('.node-statuses-item');

    _this.init();
};

NodeStatuses.prototype.$containers = null;
NodeStatuses.prototype.$icon = null;
NodeStatuses.prototype.$inputs = null;
NodeStatuses.prototype.$item = null;

NodeStatuses.prototype.init = function() {
    var _this = this;

    _this.$item.on('click', $.proxy(_this.itemClick, _this));

    _this.$inputs.off('change', $.proxy(_this.onChange, _this));
    _this.$inputs.on('change', $.proxy(_this.onChange, _this));

    _this.$containers.find(".rz-boolean-checkbox").bootstrapSwitch({
        "onSwitchChange": $.proxy(_this.onChange, _this)
    });
};

NodeStatuses.prototype.itemClick = function(event) {
    var _this = this;
    
    $input = $(event.currentTarget).find('input[type="radio"]');

    if($input.length){
        $input.prop('checked', true);
        $input.trigger('change');
    }

};

NodeStatuses.prototype.onChange = function(event) {
    var _this = this;

    var $input = $(event.currentTarget);

    if ($input.length) {

        var statusName = $input.attr('name');
        var statusValue = null;
        if($input.is('input[type="checkbox"]')){
            statusValue = Number($input.is(':checked'));
        } else if($input.is('input[type="radio"]')){
            _this.$icon[0].className = $input.parent().find('i')[0].className;
            statusValue = Number($input.val());
        }

        var postData = {
            "_token": Rozier.ajaxToken,
            "_action":'nodeChangeStatus',
            "nodeId":parseInt($input.attr('data-node-id')),
            "statusName": statusName,
            "statusValue": statusValue
        };
        console.log(postData);

        $.ajax({
            url: Rozier.routes.nodesStatusesAjax,
            type: 'post',
            dataType: 'json',
            data: postData
        })
        .done(function(data) {
            console.log(data);
            Rozier.refreshMainNodeTree();
            $.UIkit.notify({
                message : data.responseText,
                status  : data.status,
                timeout : 3000,
                pos     : 'top-center'
            });
        })
        .fail(function(data) {
            console.log(data.responseJSON);

            data = JSON.parse(data.responseText);

            $.UIkit.notify({
                message : data.responseText,
                status  : data.status,
                timeout : 3000,
                pos     : 'top-center'
            });
        })
        .always(function(data) {

        });
    }
};;/**
 * NODE TYPE FIELD EDIT
 */

NodeTypeFieldEdit = function(){
    var _this = this;

    // Selectors
    _this.$btn = $('.node-type-field-edit-button');
    _this.$formFieldRow = $('.node-type-field-row');

    // Methods
    _this.init();

};


NodeTypeFieldEdit.prototype.$btn = null;
NodeTypeFieldEdit.prototype.indexOpen = null;
NodeTypeFieldEdit.prototype.openFormDelay = 0;
NodeTypeFieldEdit.prototype.$formRow = null;
NodeTypeFieldEdit.prototype.$formRow = null;
NodeTypeFieldEdit.prototype.$formCont = null;
NodeTypeFieldEdit.prototype.$form = null;
NodeTypeFieldEdit.prototype.$formIcon = null;
NodeTypeFieldEdit.prototype.$formContHeight = null;


/**
 * Init
 * @return {[type]} [description]
 */
NodeTypeFieldEdit.prototype.init = function(){
    var _this = this;

    // Events
    _this.$btn.on('click', $.proxy(_this.btnClick, _this));
};


/**
 * Btn click
 * @return {[type]} [description]
 */
NodeTypeFieldEdit.prototype.btnClick = function(e){
    var _this = this;

    if(_this.indexOpen !== null){
        _this.closeForm();
        _this.openFormDelay = 500;
    } 
    else _this.openFormDelay = 0;

    if(_this.indexOpen !==  parseInt(e.currentTarget.getAttribute('data-index')) ){

        setTimeout(function(){

            _this.indexOpen = parseInt(e.currentTarget.getAttribute('data-index'));

            $.ajax({
                url: e.currentTarget.href,
                type: 'get',
                dataType: 'html'
            })
            .done(function(data) {
                _this.applyContent(e.currentTarget, data, e.currentTarget.href);
            })
            .fail(function() {
                console.log("error");
                $.UIkit.notify({
                    message : Rozier.messages.forbiddenPage,
                    status  : 'danger',
                    timeout : 3000,
                    pos     : 'top-center'
                });
            });

        }, _this.openFormDelay);

    }

    return false;
};


/**
 * Apply content
 * @return {[type]} [description]
 */
NodeTypeFieldEdit.prototype.applyContent = function(target, data, url){
    var _this = this;

    var dataWrapped = [
        '<tr class="node-type-field-edit-form-row">',
            '<td colspan="4">',
                '<div class="node-type-field-edit-form-cont">',
                    data,
                '</div>',
            '</td>',
        '</tr>'
    ].join('');

    $(target).parent().parent().after(dataWrapped);  

    setTimeout(function(){
        _this.$formCont = $('.node-type-field-edit-form-cont');
        _this.formContHeight = _this.$formCont.actual('height');
        _this.$formRow = $('.node-type-field-edit-form-row');
        _this.$form = $('#edit-node-type-field-form');
        _this.$formIcon = $(_this.$formFieldRow[_this.indexOpen]).find('.node-type-field-col-1 i');

        _this.$form.attr('action', url);
        _this.$formIcon[0].className = 'uk-icon-chevron-down';

        // _this.$form[0].style.height = '0px';
        // _this.$form[0].style.display = 'table-row';
        _this.$formCont[0].style.height = '0px';
        _this.$formCont[0].style.display = 'block';
        TweenLite.to(_this.$form, 0.6, {height:_this.formContHeight, ease:Expo.easeOut});
        TweenLite.to(_this.$formCont, 0.6, {height:_this.formContHeight, ease:Expo.easeOut});
    }, 200);       

};


/**
 * Close form
 * @return {[type]} [description]
 */
NodeTypeFieldEdit.prototype.closeForm = function(){
    var _this = this;

    _this.$formIcon[0].className = 'uk-icon-chevron-right';

    TweenLite.to(_this.$formCont, 0.4, {height:0, ease:Expo.easeOut, onComplete:function(){
        _this.$formRow.remove();
        _this.indexOpen = null;
    }});

};


/**
 * Window resize callback
 * @return {[type]} [description]
 */
NodeTypeFieldEdit.prototype.resize = function(){
    var _this = this;

};
;var GeotagField = function () {
    var _this = this;

    _this.$fields = $('input.rz-geotag-field');

    if( _this.$fields.length &&
        Rozier.googleClientId !== ""){

        _this.init();
    }
};

GeotagField.prototype.geocoder = null;
GeotagField.prototype.$fields = null;
GeotagField.prototype.init = function() {
    var _this = this;

    if(!Rozier.gMapLoaded) {

        var script = document.createElement('script');
        script.type = 'text/javascript';
        script.src = '//maps.googleapis.com/maps/api/js?key='+Rozier.googleClientId +
            '&callback=initializeGeotagFields';
        document.body.appendChild(script);

    } else {
        _this.bindFields();
    }
};


GeotagField.prototype.bindFields = function() {
    var _this = this;

    _this.geocoder = new google.maps.Geocoder();

    _this.$fields.each(function (index, element) {

        _this.bindSingleField(element);
    });
};


GeotagField.prototype.bindSingleField = function(element) {
    var _this = this;

    var $input = $(element);
    var jsonCode = {'lat':45.769785, 'lng':4.833967, 'zoom':14}; // default location
    var fieldId = 'geotag-canvas-'+GeotagField.uniqid();
    var fieldAddressId = fieldId+'-address';
    var resetButtonId = fieldId+'-reset';

    var mapOptions = {
        center: new google.maps.LatLng(jsonCode.lat, jsonCode.lng),
        zoom: jsonCode.zoom,
        styles: Rozier.mapsStyle
    };

    /*
     * prepare DOM
     */
    $input.hide();
    $input.attr('data-geotag-canvas', fieldId);
    $input.after('<div class="rz-geotag-canvas" id="'+fieldId+'" style="width: 100%; height: 400px;"></div>');
    // Geocode input text
    var metaDOM = '<nav class="rz-geotag-meta"><input class="rz-geotag-address" id="'+fieldAddressId+'" type="text" value="" />';
    metaDOM += '<a id="'+resetButtonId+'" class="uk-button uk-button-content uk-button-table-delete rz-geotag-reset" title="'+Rozier.messages.geotag.resetMarker+'" data-uk-tooltip="{animation:true}"><i class="uk-icon-rz-trash-o"></i></a></nav>';
    $input.after(metaDOM);

    var $geocodeInput = $('#'+fieldAddressId);
    $geocodeInput.attr('placeholder', Rozier.messages.geotag.typeAnAddress);
    // Reset button
    var $geocodeReset = $('#'+resetButtonId);
    $geocodeReset.hide();

    /*
     * Prepare map and marker
     */
    var map = new google.maps.Map(document.getElementById(fieldId), mapOptions);
    var marker = null;

    if($input.val() !== ""){
        try {
            jsonCode = JSON.parse($input.val());
            marker = _this.createMarker(jsonCode, $input, map);
            $geocodeReset.show();
        } catch (e) {

            $input.show();
            $(document.getElementById(fieldId)).hide();

            return false;
        }
    } else {
        marker = new google.maps.Marker({
            //map:map,
            draggable:true,
            position: mapOptions.center,
            animation: google.maps.Animation.DROP
        });
    }

    google.maps.event.addListener(marker, 'dragend', $.proxy(_this.setMarkerEvent, _this, marker, $input, $geocodeReset, map));
    google.maps.event.addListener(map, 'click', $.proxy(_this.setMarkerEvent, _this, marker, $input, $geocodeReset, map));

    $geocodeInput.on('keypress', $.proxy(_this.requestGeocode, _this, marker, $input, $geocodeReset, map));
    $geocodeReset.on('click', $.proxy(_this.resetMarker, _this, marker, $input, $geocodeReset, map));

    setTimeout(function () {
        google.maps.event.trigger(map, "resize");

        if (null !== marker) {
            map.panTo(marker.getPosition());
        } else {
            map.panTo(mapOptions.center);
        }

    }, 500);
};

/**
 * @param Marker marker
 * @param jQuery DOM $input
 * @param Map map
 * @param Event event
 */
GeotagField.prototype.resetMarker = function(marker, $input, $geocodeReset, map, event) {
    var _this = this;

    marker.setMap(null);
    $input.val("");

    $geocodeReset.hide();

    return false;
};
/**
 * @param Marker marker
 * @param jQuery DOM $input
 * @param Map map
 * @param Event event
 */
GeotagField.prototype.setMarkerEvent = function(marker, $input, $geocodeReset, map, event) {
    var _this = this;

    _this.setMarker(marker, $input, $geocodeReset, map, event.latLng);
};

/**
 * @param Marker marker
 * @param jQuery DOM $input
 * @param Map map
 * @param Event event
 */
GeotagField.prototype.setMarker = function(marker, $input, $geocodeReset, map, latlng) {
    var _this = this;

    marker.setPosition(latlng);
    marker.setMap(map);

    map.panTo(latlng);

    var geoCode = {
        'lat':latlng.lat(),
        'lng':latlng.lng(),
        'zoom':map.getZoom()
    };

    $input.val(JSON.stringify(geoCode));

    $geocodeReset.show();
};

/**
 * @param  Object geocode
 * @param  jQuery DOM $input
 * @param  Map map
 *
 * @return Marker
 */
GeotagField.prototype.createMarker = function(geocode, $input, map) {
    var _this = this;
    var latlng = new google.maps.LatLng(geocode.lat, geocode.lng);
    var marker = new google.maps.Marker({
        map:map,
        draggable:true,
        animation: google.maps.Animation.DROP,
        position: latlng
    });

    map.panTo(latlng);
    map.setZoom(geocode.zoom);

    return marker;
};

GeotagField.prototype.requestGeocode = function(marker, $input, $geocodeReset, map, event) {
    var _this = this;

    var address = event.currentTarget.value;

    if(event.which == 13) {
        event.preventDefault();

        _this.geocoder.geocode( {'address': address}, function(results, status) {
            if (status == google.maps.GeocoderStatus.OK) {
                _this.setMarker(marker, $input, $geocodeReset, map, results[0].geometry.location);

            } else {
                console.err("Geocode was not successful for the following reason: " + status);
            }
        });

        return false;
    }
};

var initializeGeotagFields = function () {
    Rozier.gMapLoaded = true;
    new GeotagField();
};

GeotagField.uniqid = function () {
    var n = new Date();
    return n.getTime();
};;/**
 *
 */
var ChildrenNodesField = function () {
    var _this = this;

    _this.$fields = $('[data-children-nodes-widget]');
    _this.$quickAddNodeButtons = _this.$fields.find('.children-nodes-quick-creation a');
    //_this.$switchLangButtons = _this.$fields.find('.nodetree-langs');

    _this.init();
};
ChildrenNodesField.prototype.$fields = null;
//ChildrenNodesField.prototype.$switchLangButtons = null;
ChildrenNodesField.prototype.$quickAddNodeButtons = null;

ChildrenNodesField.prototype.init = function() {
    var _this = this;

    if (_this.$quickAddNodeButtons.length) {

        var proxiedClick = $.proxy(_this.onQuickAddClick, _this);
        _this.$quickAddNodeButtons.off("click", proxiedClick);
        _this.$quickAddNodeButtons.on("click", proxiedClick);

        /*if(_this.$switchLangButtons.length){
            var proxiedChangeLang = $.proxy(_this.onChangeLangClick, _this);
            _this.$switchLangButtons.off("click", proxiedChangeLang);
            _this.$switchLangButtons.on("click", proxiedChangeLang);
        }*/

    }
    
    _this.$fields.find('.nodetree-langs').remove();
};

/*ChildrenNodesField.prototype.onChangeLangClick = function(event) {
    var _this = this;
    var $link = $(event.currentTarget);

    var $nodeTree = $link.parents('.children-nodes-widget').find('.nodetree-widget');
    var parentNodeId = parseInt($link.attr('data-children-parent-node'));
    var translationId = parseInt($link.attr('data-translation-id'));

    _this.refreshNodeTree($nodeTree, parentNodeId, translationId);

    return false;
};*/

ChildrenNodesField.prototype.onQuickAddClick = function(event) {
    var _this = this;
    var $link = $(event.currentTarget);

    var nodeTypeId = parseInt($link.attr('data-children-node-type'));
    var parentNodeId = parseInt($link.attr('data-children-parent-node'));
    var translationId = parseInt($link.attr('data-translation-id'));

    if(nodeTypeId > 0 &&
       parentNodeId > 0) {

        var postData = {
            "_token": Rozier.ajaxToken,
            "_action":'quickAddNode',
            "nodeTypeId":nodeTypeId,
            "parentNodeId":parentNodeId,
            "translationId":translationId
        };

        $.ajax({
            url: Rozier.routes.nodesQuickAddAjax,
            type: 'post',
            dataType: 'json',
            data: postData,
        })
        .done(function(data) {
            console.log("success");
            console.log(data);

            Rozier.refreshMainNodeTree();

            var $nodeTree = $link.parents('.children-nodes-widget').find('.nodetree-widget');
            _this.refreshNodeTree($nodeTree, parentNodeId, translationId);

            $.UIkit.notify({
                message : data.responseText,
                status  : data.status,
                timeout : 3000,
                pos     : 'top-center'
            });
        })
        .fail(function(data) {
            console.log("error");
            console.log(data);

            data = JSON.parse(data.responseText);

            $.UIkit.notify({
                message : data.responseText,
                status  : data.status,
                timeout : 3000,
                pos     : 'top-center'
            });
        })
        .always(function() {
            console.log("complete");
        });
    }

    return false;
};

ChildrenNodesField.prototype.refreshNodeTree = function( $nodeTree, rootNodeId, translationId ) {
    var _this = this;

    if($nodeTree.length){

        Rozier.lazyload.canvasLoader.show();
        var postData = {
            "_token": Rozier.ajaxToken,
            "_action":'requestNodeTree',
            "parentNodeId":parseInt(rootNodeId)
        };

        var url = Rozier.routes.nodesTreeAjax;
        if(isset(translationId) && translationId > 0){
            url += '/'+translationId;
        }

        $.ajax({
            url: url,
            type: 'post',
            dataType: 'json',
            data: postData,
        })
        .done(function(data) {

            if($nodeTree.length &&
                typeof data.nodeTree != "undefined"){

                $nodeTree.fadeOut('slow', function() {
                    var $tempContainer = $nodeTree.parents('.children-nodes-widget');

                    $nodeTree.replaceWith(data.nodeTree);
                    $nodeTree = $tempContainer.find('.nodetree-widget');

                    Rozier.initNestables();
                    Rozier.bindMainTrees();
                    Rozier.lazyload.generalBind();
                    $nodeTree.fadeIn();

                    /*_this.$switchLangButtons = _this.$fields.find('.nodetree-langs a');
                    if(_this.$switchLangButtons.length){
                        var proxiedChangeLang = $.proxy(_this.onChangeLangClick, _this);
                        _this.$switchLangButtons.off("click", proxiedChangeLang);
                        _this.$switchLangButtons.on("click", proxiedChangeLang);
                    }*/
                    _this.$fields.find('.nodetree-langs').remove();

                    Rozier.lazyload.canvasLoader.hide();
                });
            }
        })
        .fail(function(data) {
            console.log(data.responseJSON);
        });
    } else {
        console.error("No node-tree available.");
    }
};
;/**
 * Markdown Editor
 */

MarkdownEditor = function(){
    var _this = this;

    // Selectors
    _this.$cont = $('.uk-htmleditor');
    _this.$textarea = _this.$cont.find('.markdown_textarea');

    // Methods
    setTimeout(function(){
        _this.init();
    }, 0);

};


MarkdownEditor.prototype.$cont = null;
MarkdownEditor.prototype.$textarea = null;
MarkdownEditor.prototype.$buttonCode = null;
MarkdownEditor.prototype.$buttonPreview = null;
MarkdownEditor.prototype.$buttonFullscreen = null;
MarkdownEditor.prototype.$count = null;
MarkdownEditor.prototype.$countCurrent = null;
MarkdownEditor.prototype.limit = [];
MarkdownEditor.prototype.countMinLimit = [];
MarkdownEditor.prototype.countMaxLimit = [];
MarkdownEditor.prototype.$countMaxLimitText = null;
MarkdownEditor.prototype.countAlertActive = [];
MarkdownEditor.prototype.fullscreenActive = [];


/**
 * Init
 * @return {[type]} [description]
 */
MarkdownEditor.prototype.init = function(){
    var _this = this;

    if(_this.$cont.length && _this.$textarea.length){

        for(var i = 0; i < _this.$cont.length; i++) {

            // Store markdown index into datas
            $(_this.$cont[i]).find('.uk-htmleditor-button-code').attr('data-index',i);
            $(_this.$cont[i]).find('.uk-htmleditor-button-preview').attr('data-index',i);
            $(_this.$cont[i]).find('.uk-htmleditor-button-fullscreen').attr('data-index',i);
            $(_this.$cont[i]).find('.markdown_textarea').attr('data-index',i);
            $(_this.$cont[i]).find('.CodeMirror').attr('data-index',i);

            // Check if a desc is defined
            if(_this.$textarea[i].getAttribute('data-desc') !== ''){
                $(_this.$cont[i]).after('<div class="form-help uk-alert uk-alert-large">'+_this.$textarea[i].getAttribute('data-desc')+'</div>');
            }

            // Check if a max length is defined
            if(_this.$textarea[i].getAttribute('data-max-length') !== ''){

                _this.limit[i] = true;
                _this.countMaxLimit[i] = parseInt(_this.$textarea[i].getAttribute('data-max-length'));
                $(_this.$cont[i]).find('.count-current')[0].innerHTML = stripTags(Rozier.lazyload.htmlEditor[i].currentvalue).length;
                $(_this.$cont[i]).find('.count-limit')[0].innerHTML = _this.$textarea[i].getAttribute('data-max-length');
                $(_this.$cont[i]).find('.uk-htmleditor-count')[0].style.display = 'block';
                
            }
            
            if(_this.$textarea[i].getAttribute('data-min-length') !== ''){

                _this.limit[i] = true;
                _this.countMinLimit[i] = parseInt(_this.$textarea[i].getAttribute('data-min-length'));
            }

            if( _this.$textarea[i].getAttribute('data-min-length') === '' && _this.$textarea[i].getAttribute('data-max-length') === ''){

                _this.limit[i] = false;
                _this.countMaxLimit[i] = null;
                _this.countAlertActive[i] = null;
            }

            _this.fullscreenActive[i] = false;

            if(_this.limit[i]){

                 // Check if current length is over limit
                if(stripTags(Rozier.lazyload.htmlEditor[i].currentvalue).length > _this.countMaxLimit[i]){
                    _this.countAlertActive[i] = true;
                    addClass(_this.$cont[i], 'content-limit');
                }
                else if(stripTags(Rozier.lazyload.htmlEditor[i].currentvalue).length < _this.countMinLimit[i]){
                    _this.countAlertActive[i] = true;
                    addClass(_this.$cont[i], 'content-limit');
                }
                else _this.countAlertActive[i] = false;   
            }

            $(_this.$cont[i]).find('.CodeMirror').on('keyup', $.proxy(_this.textareaChange, _this));
        }
        

        // Selectors
        _this.$content = _this.$cont.find('.uk-htmleditor-content');
        _this.$buttonCode = _this.$cont.find('.uk-htmleditor-button-code');
        _this.$buttonPreview = _this.$cont.find('.uk-htmleditor-button-preview');
        _this.$buttonFullscreen = _this.$cont.find('.uk-htmleditor-button-fullscreen');
        _this.$count = _this.$cont.find('.uk-htmleditor-count');
        _this.$countCurrent = _this.$cont.find('.count-current');
        _this.$countMaxLimitText = _this.$cont.find('.count-limit');

             

        // Events
        for(var j = 0; j < Rozier.lazyload.$textAreaHTMLeditor.length; j++) {
            Rozier.lazyload.htmlEditor[j].editor.on('focus', $.proxy(_this.textareaFocus, _this));
            Rozier.lazyload.htmlEditor[j].editor.on('blur', $.proxy(_this.textareaFocusOut, _this));
        }  
        _this.$buttonPreview.on('click', $.proxy(_this.buttonPreviewClick, _this));
        _this.$buttonCode.on('click', $.proxy(_this.buttonCodeClick, _this));
        _this.$buttonFullscreen.on('click', $.proxy(_this.buttonFullscreenClick, _this));
        Rozier.$window.on('keyup', $.proxy(_this.echapKey, _this));

    }

};


/**
 * Textarea change
 * @return {[type]} [description]
 */
MarkdownEditor.prototype.textareaChange = function(e){
    var _this = this;

    var index = parseInt(e.currentTarget.getAttribute('data-index'));

    if(_this.limit[index]){
        setTimeout(function(){
         
            var textareaVal = Rozier.lazyload.htmlEditor[index].currentvalue,
                textareaValStripped = stripTags(textareaVal),
                textareaValLength = textareaValStripped.length;

            _this.$countCurrent[index].innerHTML = textareaValLength;

            if(textareaValLength > _this.countMaxLimit[index]){
                if(!_this.countAlertActive[index]){
                    addClass(_this.$cont[index], 'content-limit');
                    _this.countAlertActive[index] = true;
                }
            }
            else if(textareaValLength < _this.countMinLimit[index]){
                console.log('inf limit ');
                if(!_this.countAlertActive[index]){
                    addClass(_this.$cont[index], 'content-limit');
                    _this.countAlertActive[index] = true;
                }
            }
            else{
                if(_this.countAlertActive[index]){
                    removeClass(_this.$cont[index], 'content-limit');
                    _this.countAlertActive[index] = false;
                }
            }
        }, 100);
    }    

};


/**
 * Textarea focus
 * @return {[type]} [description]
 */
MarkdownEditor.prototype.textareaFocus = function(e){
    var _this = this;

   $(e.display.wrapper).parent().parent().parent().parent().addClass('form-col-focus');

};


/**
 * Textarea focus out
 * @return {[type]} [description]
 */
MarkdownEditor.prototype.textareaFocusOut = function(e){
    var _this = this;

    $(e.display.wrapper).parent().parent().parent().parent().removeClass('form-col-focus');

};


/**
 * Button preview click
 * @return {[type]} [description]
 */
MarkdownEditor.prototype.buttonPreviewClick = function(e){
    var _this = this;

    var index = parseInt(e.currentTarget.getAttribute('data-index'));

    _this.$buttonCode[index].style.display = 'block';
    TweenLite.to(_this.$buttonCode[index], 0.5, {opacity:1, ease:Expo.easeOut});

    TweenLite.to(_this.$buttonPreview[index], 0.5, {opacity:0, ease:Expo.easeOut, onComplete:function(){
        _this.$buttonPreview[index].style.display = 'none';
    }});

};


/**
 * Button code click
 * @return {[type]} [description]
 */
MarkdownEditor.prototype.buttonCodeClick = function(e){
    var _this = this;

    var index = parseInt(e.currentTarget.getAttribute('data-index'));

    _this.$buttonPreview[index].style.display = 'block';
    TweenLite.to(_this.$buttonPreview[index], 0.5, {opacity:1, ease:Expo.easeOut});

    TweenLite.to(_this.$buttonCode[index], 0.5, {opacity:0, ease:Expo.easeOut, onComplete:function(){
        _this.$buttonCode[index].style.display = 'none';
    }});

};


/**
 * Button fullscreen click
 * @return {[type]} [description]
 */
MarkdownEditor.prototype.buttonFullscreenClick = function(e){
    var _this = this;

    var index = parseInt(e.currentTarget.getAttribute('data-index')),
        $fullscreenIcon =  $(_this.$buttonFullscreen[index]).find('i');

    if(!_this.fullscreenActive[index]){
        $fullscreenIcon[0].className = 'uk-icon-rz-fullscreen-off';
        _this.fullscreenActive[index] = true;
    }
    else{
        $fullscreenIcon[0].className = 'uk-icon-rz-fullscreen';
        _this.fullscreenActive[index] = false;
    }

};


/**
 * Echap key to close explorer
 * @return {[type]} [description]
 */
MarkdownEditor.prototype.echapKey = function(e){
    var _this = this;

    if(e.keyCode == 27){

        for(var i = 0; i < _this.$cont.length; i++) {
            
            if(_this.fullscreenActive[i]){
                $(_this.$buttonFullscreen[i]).find('a').trigger('click');
                break;
            }
        }
        

    }

    return false;
};


/**
 * Window resize callback
 * @return {[type]} [description]
 */
MarkdownEditor.prototype.resize = function(){
    var _this = this;

};;/*
 *
 *
 */
var TagAutocomplete = function () {
    var _this = this;

    function split( val ) {
        return val.split( /,\s*/ );
    }
    function extractLast( term ) {
        return split( term ).pop();
    }
    $(".rz-tag-autocomplete")
        // don't navigate away from the field on tab when selecting an item
        .bind( "keydown", function( event ) {
            if ( event.keyCode === $.ui.keyCode.TAB &&
                $( this ).autocomplete( "instance" ).menu.active ) {
                event.preventDefault();
            }
        })
        .autocomplete({
            source: function( request, response ) {

                $.getJSON( Rozier.routes.tagAjaxSearch, {
                    '_action': 'tagAutocomplete',
                    '_token': Rozier.ajaxToken,
                    'search': extractLast( request.term )
                }, response);
            },
            search: function() {

                // custom minLength
                var term = extractLast( this.value );
                if ( term.length < 2 ) {
                  return false;
                }
            },
            focus: function() {
              // prevent value inserted on focus
              return false;
            },
            select: function( event, ui ) {
              var terms = split( this.value );
              // remove the current input
              terms.pop();
              // add the selected item
              terms.push( ui.item.value );
              // add placeholder to get the comma-and-space at the end
              terms.push( "" );
              this.value = terms.join( ", " );
              return false;
        }
    });
};;/*
 *
 *
 */
var FolderAutocomplete = function () {
    var _this = this;

    function split( val ) {
        return val.split( /,\s*/ );
    }
    function extractLast( term ) {
        return split( term ).pop();
    }
    $(".rz-folder-autocomplete")
        // don't navigate away from the field on tab when selecting an item
        .bind( "keydown", function( event ) {
            if ( event.keyCode === $.ui.keyCode.TAB &&
                $( this ).autocomplete( "instance" ).menu.active ) {
                event.preventDefault();
            }
        })
        .autocomplete({
            source: function( request, response ) {

                $.getJSON( Rozier.routes.foldersAjaxSearch, {
                    '_action': 'folderAutocomplete',
                    '_token': Rozier.ajaxToken,
                    'search': extractLast( request.term )
                }, response);
            },
            search: function() {

                // custom minLength
                var term = extractLast( this.value );
                if ( term.length < 2 ) {
                  return false;
                }
            },
            focus: function() {
              // prevent value inserted on focus
              return false;
            },
            select: function( event, ui ) {
              var terms = split( this.value );
              // remove the current input
              terms.pop();
              // add the selected item
              terms.push( ui.item.value );
              // add placeholder to get the comma-and-space at the end
              terms.push( "" );
              this.value = terms.join( ", " );
              return false;
        }
    });
};;var StackNodeTree = function () {
    var _this = this;

    _this.$page = $('.stack-tree');
    _this.$quickAddNodeButtons = _this.$page.find('.stack-tree-quick-creation a');
    _this.$switchLangButtons = _this.$page.find('.nodetree-langs a');

    _this.init();
};

StackNodeTree.prototype.$page = null;
StackNodeTree.prototype.$switchLangButtons = null;
StackNodeTree.prototype.$quickAddNodeButtons = null;

StackNodeTree.prototype.init = function() {
    var _this = this;

    if (_this.$quickAddNodeButtons.length) {

        var proxiedClick = $.proxy(_this.onQuickAddClick, _this);

        _this.$quickAddNodeButtons.off("click", proxiedClick);
        _this.$quickAddNodeButtons.on("click", proxiedClick);

        if(_this.$switchLangButtons.length){
            var proxiedChangeLang = $.proxy(_this.onChangeLangClick, _this);
            _this.$switchLangButtons.off("click", proxiedChangeLang);
            _this.$switchLangButtons.on("click", proxiedChangeLang);
        }
    }
};
StackNodeTree.prototype.onChangeLangClick = function(event) {
    var _this = this;
    var $link = $(event.currentTarget);

    var $nodeTree = _this.$page.find('.nodetree-widget');
    var parentNodeId = parseInt($link.attr('data-children-parent-node'));
    var translationId = parseInt($link.attr('data-translation-id'));

    _this.refreshNodeTree(parentNodeId, translationId);

    return false;
};

StackNodeTree.prototype.onQuickAddClick = function(event) {
    var _this = this;
    var $link = $(event.currentTarget);

    var nodeTypeId = parseInt($link.attr('data-children-node-type'));
    var parentNodeId = parseInt($link.attr('data-children-parent-node'));

    if(nodeTypeId > 0 &&
       parentNodeId > 0) {

        var postData = {
            "_token": Rozier.ajaxToken,
            "_action":'quickAddNode',
            "nodeTypeId":nodeTypeId,
            "parentNodeId":parentNodeId,
            "pushTop":1
        };

        $.ajax({
            url: Rozier.routes.nodesQuickAddAjax,
            type: 'post',
            dataType: 'json',
            data: postData,
        })
        .done(function(data) {
            console.log("success");
            console.log(data);

            Rozier.refreshMainNodeTree();
            _this.refreshNodeTree(parentNodeId);

            $.UIkit.notify({
                message : data.responseText,
                status  : data.status,
                timeout : 3000,
                pos     : 'top-center'
            });
        })
        .fail(function(data) {
            console.log("error");
            console.log(data);

            data = JSON.parse(data.responseText);

            $.UIkit.notify({
                message : data.responseText,
                status  : data.status,
                timeout : 3000,
                pos     : 'top-center'
            });
        })
        .always(function() {
            console.log("complete");
        });
    }

    return false;
};

StackNodeTree.prototype.refreshNodeTree = function( rootNodeId, translationId ) {
    var _this = this;
    var $nodeTree = _this.$page.find('.nodetree-widget');

    if($nodeTree.length){
        Rozier.lazyload.canvasLoader.show();
        var postData = {
            "_token":       Rozier.ajaxToken,
            "_action":      'requestNodeTree',
            "stackTree":    true,
            "parentNodeId": parseInt(rootNodeId)
        };

        var url = Rozier.routes.nodesTreeAjax;
        if(isset(translationId) && translationId > 0){
            url += '/'+translationId;
        }

        $.ajax({
            url: url,
            type: 'post',
            dataType: 'json',
            data: postData,
        })
        .done(function(data) {

            if($nodeTree.length &&
                typeof data.nodeTree != "undefined"){

                $nodeTree.fadeOut('slow', function() {
                    $nodeTree.replaceWith(data.nodeTree);
                    $nodeTree = _this.$page.find('.nodetree-widget');

                    Rozier.initNestables();
                    Rozier.bindMainTrees();

                    Rozier.lazyload.generalBind();
                    $nodeTree.fadeIn();
                    Rozier.resize();

                    _this.$switchLangButtons = _this.$page.find('.nodetree-langs a');
                    if(_this.$switchLangButtons.length){
                        var proxiedChangeLang = $.proxy(_this.onChangeLangClick, _this);
                        _this.$switchLangButtons.off("click", proxiedChangeLang);
                        _this.$switchLangButtons.on("click", proxiedChangeLang);
                    }

                    Rozier.lazyload.canvasLoader.hide();
                });
            }
        })
        .fail(function(data) {
            console.log(data.responseJSON);
        });
    } else {
        console.error("No node-tree available.");
    }
};;var NodeTypeFieldsPosition = function () {
    var _this = this;

    _this.$list = $(".node-type-fields > .uk-sortable");

    _this.init();
};
NodeTypeFieldsPosition.prototype.$list = null;
NodeTypeFieldsPosition.prototype.init = function() {
    var _this = this;

    if (_this.$list.length &&
        _this.$list.children().length > 1) {
        var onChange = $.proxy(_this.onSortableChange, _this);
        _this.$list.off('uk.sortable.change', onChange);
        _this.$list.on('uk.sortable.change', onChange);
    }
};

NodeTypeFieldsPosition.prototype.onSortableChange = function(event, list, element) {
    var _this = this;

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

    $.ajax({
        url: Rozier.routes.nodeTypesFieldAjaxEdit.replace("%nodeTypeFieldId%", nodeTypeFieldId),
        type: 'POST',
        dataType: 'json',
        data: postData,
    })
    .done(function(data) {
        console.log(data);
        $element.attr('data-position', newPosition);
        $.UIkit.notify({
            message : data.responseText,
            status  : data.status,
            timeout : 3000,
            pos     : 'top-center'
        });
    })
    .fail(function(data) {
        console.log(data);
    })
    .always(function() {
        console.log("complete");
    });

};;var CustomFormFieldsPosition = function () {
    var _this = this;

    _this.$list = $(".custom-form-fields > .uk-sortable");

    _this.init();
};
CustomFormFieldsPosition.prototype.$list = null;
CustomFormFieldsPosition.prototype.init = function() {
    var _this = this;

    if (_this.$list.length &&
        _this.$list.children().length > 1) {
        var onChange = $.proxy(_this.onSortableChange, _this);
        _this.$list.off('uk.sortable.change', onChange);
        _this.$list.on('uk.sortable.change', onChange);
    }
};

CustomFormFieldsPosition.prototype.onSortableChange = function(event, list, element) {
    var _this = this;

    var $element = $(element);
    var customFormFieldId = parseInt($element.data('field-id'));
    var $sibling = $element.prev();
    var newPosition = 0.0;

    if ($sibling.length === 0) {
        $sibling = $element.next();
        newPosition = parseInt($sibling.data('position')) - 0.5;
    } else {
        newPosition = parseInt($sibling.data('position')) + 0.5;
    }

    console.log("customFormFieldId="+customFormFieldId+"; newPosition="+newPosition);


    var postData = {
        '_token':          Rozier.ajaxToken,
        '_action':         'updatePosition',
        'customFormFieldId': customFormFieldId,
        'newPosition':     newPosition
    };

    $.ajax({
        url: Rozier.routes.customFormsFieldAjaxEdit.replace("%customFormFieldId%", customFormFieldId),
        type: 'POST',
        dataType: 'json',
        data: postData,
    })
    .done(function(data) {
        console.log(data);
        $element.attr('data-position', newPosition);
        $.UIkit.notify({
            message : data.responseText,
            status  : data.status,
            timeout : 3000,
            pos     : 'top-center'
        });
    })
    .fail(function(data) {
        console.log(data);
    })
    .always(function() {
        console.log("complete");
    });

};
;/**
 * CUSTOM FORM FIELD EDIT
 */

CustomFormFieldEdit = function(){
    var _this = this;

    // Selectors
    _this.$btn = $('.custom-form-field-edit-button');
    _this.$formFieldRow = $('.custom-form-field-row');

    // Methods
    _this.init();

};


CustomFormFieldEdit.prototype.$btn = null;
CustomFormFieldEdit.prototype.indexOpen = null;
CustomFormFieldEdit.prototype.openFormDelay = 0;
CustomFormFieldEdit.prototype.$formFieldRow = null;
CustomFormFieldEdit.prototype.$formRow = null;
CustomFormFieldEdit.prototype.$formCont = null;
CustomFormFieldEdit.prototype.$form = null;
CustomFormFieldEdit.prototype.$formIcon = null;
CustomFormFieldEdit.prototype.$formContHeight = null;


/**
 * Init
 * @return {[type]} [description]
 */
CustomFormFieldEdit.prototype.init = function(){
    var _this = this;

    // Events
    _this.$btn.on('click', $.proxy(_this.btnClick, _this));
};


/**
 * Btn click
 * @return {[type]} [description]
 */
CustomFormFieldEdit.prototype.btnClick = function(e){
    var _this = this;

    if(_this.indexOpen !== null){
        _this.closeForm();
        _this.openFormDelay = 500;
    } 
    else _this.openFormDelay = 0;

    if(_this.indexOpen !==  parseInt(e.currentTarget.getAttribute('data-index')) ){

        setTimeout(function(){

            _this.indexOpen = parseInt(e.currentTarget.getAttribute('data-index'));

            $.ajax({
                url: e.currentTarget.href,
                type: 'get',
                dataType: 'html'
            })
            .done(function(data) {
                _this.applyContent(e.currentTarget, data, e.currentTarget.href);
            })
            .fail(function() {
                console.log("error");
                $.UIkit.notify({
                    message : Rozier.messages.forbiddenPage,
                    status  : 'danger',
                    timeout : 3000,
                    pos     : 'top-center'
                });
            });

        }, _this.openFormDelay);

    }

    return false;
};


/**
 * Apply content
 * @return {[type]} [description]
 */
CustomFormFieldEdit.prototype.applyContent = function(target, data, url){
    var _this = this;

    var dataWrapped = [
        '<tr class="custom-form-field-edit-form-row">',
            '<td colspan="4">',
                '<div class="custom-form-field-edit-form-cont">',
                    data,
                '</div>',
            '</td>',
        '</tr>'
    ].join('');

    $(target).parent().parent().after(dataWrapped);  

    setTimeout(function(){
        _this.$formCont = $('.custom-form-field-edit-form-cont');
        _this.formContHeight = _this.$formCont.actual('height');
        _this.$formRow = $('.custom-form-field-edit-form-row');
        _this.$form = $('#edit-custom-form-field-form');
        _this.$formIcon = $(_this.$formFieldRow[_this.indexOpen]).find('.custom-form-field-col-1 i');

        _this.$form.attr('action', url);
        _this.$formIcon[0].className = 'uk-icon-chevron-down';

        _this.$formCont[0].style.height = '0px';
        _this.$formCont[0].style.display = 'block';
        TweenLite.to(_this.$form, 0.6, {height:_this.formContHeight, ease:Expo.easeOut});
        TweenLite.to(_this.$formCont, 0.6, {height:_this.formContHeight, ease:Expo.easeOut});
    }, 200);       

};


/**
 * Close form
 * @return {[type]} [description]
 */
CustomFormFieldEdit.prototype.closeForm = function(){
    var _this = this;

    _this.$formIcon[0].className = 'uk-icon-chevron-right';

    TweenLite.to(_this.$formCont, 0.4, {height:0, ease:Expo.easeOut, onComplete:function(){
        _this.$formRow.remove();
        _this.indexOpen = null;
    }});

};


/**
 * Window resize callback
 * @return {[type]} [description]
 */
CustomFormFieldEdit.prototype.resize = function(){
    var _this = this;

};
;/**
 * Rozier Mobile
 */

RozierMobile = function(){
    var _this = this;

    // Selectors
    _this.$menu = $('#menu-mobile');
    _this.$adminMenu = $('#admin-menu');
    _this.$adminMenuLink = _this.$adminMenu.find('a');
    _this.$adminMenuNavParent = _this.$adminMenu.find('.uk-parent');

    _this.$searchButton = $('#search-button');
    _this.$searchPanel = $('#nodes-sources-search');

    _this.$treeButton = $('#tree-button');
    _this.$treeWrapper = $('#tree-wrapper');
    _this.$treeWrapperLink = _this.$treeWrapper.find('a');

    _this.$userPicture = $('#user-picture');
    _this.$userActions = $('.user-actions');
    _this.$userActionsLink = _this.$userActions.find('a');

    _this.$mainContentOverlay = $('#main-content-overlay');

    // Methods
    _this.init();

};


RozierMobile.prototype.$menu = null;
RozierMobile.prototype.$adminMenu = null;
RozierMobile.prototype.$adminMenuNavParent = null;
RozierMobile.prototype.menuOpen = false;

RozierMobile.prototype.$searchButton = null;
RozierMobile.prototype.$searchPanel = null;
RozierMobile.prototype.searchOpen = false;

RozierMobile.prototype.$treeButton = null;
RozierMobile.prototype.$treeWrapper = null;
RozierMobile.prototype.$treeWrapperLink = null;
RozierMobile.prototype.treeOpen = false;

RozierMobile.prototype.$userPicture = null;
RozierMobile.prototype.$userActions = null;
RozierMobile.prototype.$userActionsLink = null;
RozierMobile.prototype.adminOpen = false;

RozierMobile.prototype.$mainContentOverlay = null;


/**
 * Init
 * @return {[type]} [description]
 */
RozierMobile.prototype.init = function(){
    var _this = this;

    // Add class on user picture link to unbind default event
    addClass(_this.$userPicture[0],'rz-no-ajax-link');

    // Events
    _this.$menu.on('click', $.proxy(_this.menuClick, _this));
    _this.$adminMenuLink.on('click', $.proxy(_this.adminMenuLinkClick, _this));
    _this.$adminMenuNavParent.on('click', $.proxy(_this.adminMenuNavParentClick, _this));

    _this.$searchButton.on('click', $.proxy(_this.searchButtonClick, _this));
    // _this.$treeWrapperLink.on('click', $.proxy(_this.treeWrapperLinkClick, _this));

    _this.$treeButton.on('click', $.proxy(_this.treeButtonClick, _this));
    _this.$treeWrapperLink.on('click', $.proxy(_this.treeWrapperLinkClick, _this));
    
    _this.$userPicture.on('click', $.proxy(_this.userPictureClick, _this));
    _this.$userActionsLink.on('click', $.proxy(_this.userActionsLinkClick, _this));

    _this.$mainContentOverlay.on('click', $.proxy(_this.mainContentOverlayClick, _this));

};


/**
 * Menu click
 * @return {[type]} [description]
 */
RozierMobile.prototype.menuClick = function(e){
    var _this = this;

    if(!_this.menuOpen)_this.openMenu();
    else _this.closeMenu();

};


/**
 * Admin menu nav parent click
 * @return {[type]} [description]
 */
RozierMobile.prototype.adminMenuNavParentClick = function(e){
    var _this = this;

    var $ukNavSub = $(e.currentTarget).find('.uk-nav-sub');

    // Open
    if(e.currentTarget.className.indexOf('nav-open') == -1) {
        var $ukNavSubItem = $ukNavSub.find('.uk-nav-sub-item'),
            ukNavSubHeight = ($ukNavSubItem.length * 41) - 3;

        $ukNavSub[0].style.display = 'block';
        TweenLite.to($ukNavSub, 0.6, {height:ukNavSubHeight, ease:Expo.easeOut, onComplete:function(){
        }}); 

        addClass(e.currentTarget, 'nav-open');

    }
    // Close
    else{
        TweenLite.to($ukNavSub, 0.6, {height:0, ease:Expo.easeOut, onComplete:function(){
            $ukNavSub[0].style.display = 'none';
        }});
        
        removeClass(e.currentTarget, 'nav-open');
    }

};


/**
 * Admin menu link click
 * @return {[type]} [description]
 */
RozierMobile.prototype.adminMenuLinkClick = function(e){
    var _this = this;

    if(_this.menuOpen) _this.closeMenu();

};


/**
 * Open menu
 * @return {[type]} [description]
 */
RozierMobile.prototype.openMenu = function(){
    var _this = this;

    // Close panel if open
    if(_this.searchOpen) _this.closeSearch();
    else if(_this.treeOpen) _this.closeTree();
    else if(_this.userOpen) _this.closeUser();

    // Translate menu panel
    TweenLite.to(_this.$adminMenu, 0.6, {x:0, ease:Expo.easeOut});

    _this.$mainContentOverlay[0].style.display = 'block';
    TweenLite.to(_this.$mainContentOverlay, 0.6, {opacity:0.5, ease:Expo.easeOut});
     
    _this.menuOpen = true;
};


/**
 * Close menu
 * @return {[type]} [description]
 */
RozierMobile.prototype.closeMenu = function(){
    var _this = this;

    var adminMenuX = -Rozier.windowWidth*0.8;

    TweenLite.to(_this.$adminMenu, 0.6, {x:adminMenuX, ease:Expo.easeOut});

    TweenLite.to(_this.$mainContentOverlay, 0.6, {opacity:0, ease:Expo.easeOut, onComplete:function(){
        _this.$mainContentOverlay[0].style.display = 'none';
    }});
    
    _this.menuOpen = false;  
};


/**
 * Search button click
 * @return {[type]} [description]
 */
RozierMobile.prototype.searchButtonClick = function(e){
    var _this = this;

    if(!_this.searchOpen)_this.openSearch();
    else _this.closeSearch();

};


/**
 * Search link click
 * @return {[type]} [description]
 */
// RozierMobile.prototype.searchLinkClick = function(e){
//     var _this = this;

//     if_this.searchOpen){
//         _this.closeSearch();
//     }
// };


/**
 * Open search
 * @return {[type]} [description]
 */
RozierMobile.prototype.openSearch = function(){
    var _this = this;
    
    // Close panel if open
    if(_this.menuOpen) _this.closeMenu();
    else if(_this.treeOpen) _this.closeTree();
    else if(_this.userOpen) _this.closeUser();

    // Translate search panel
    TweenLite.to(_this.$searchPanel, 0.6, {x:0, ease:Expo.easeOut});

    _this.$mainContentOverlay[0].style.display = 'block';
    TweenLite.to(_this.$mainContentOverlay, 0.6, {opacity:0.5, ease:Expo.easeOut});

    // Add active class
    addClass(_this.$searchButton[0],'active');

    _this.searchOpen = true;
};


/**
 * Close search
 * @return {[type]} [description]
 */
RozierMobile.prototype.closeSearch = function(){
    var _this = this;

    var searchPanelX = -Rozier.windowWidth*0.8;

    TweenLite.to(_this.$searchPanel, 0.6, {x:searchPanelX, ease:Expo.easeOut});

    TweenLite.to(_this.$mainContentOverlay, 0.6, {opacity:0, ease:Expo.easeOut, onComplete:function(){
        _this.$mainContentOverlay[0].style.display = 'none';
    }});

    // Remove active class
    removeClass(_this.$searchButton[0],'active');

    _this.searchOpen = false;  
};


/**
 * Tree button click
 * @return {[type]} [description]
 */
RozierMobile.prototype.treeButtonClick = function(e){
    var _this = this;

    if(!_this.treeOpen)_this.openTree();
    else _this.closeTree();

};


/**
 * Tree wrapper link click
 * @return {[type]} [description]
 */
RozierMobile.prototype.treeWrapperLinkClick = function(e){
    var _this = this;

    if(e.currentTarget.className.indexOf('tab-link') == -1 && _this.treeOpen){
        _this.closeTree();
    }
};


/**
 * Open tree
 * @return {[type]} [description]
 */
RozierMobile.prototype.openTree = function(){
    var _this = this;
    
    // Close panel if open
    if(_this.menuOpen) _this.closeMenu();
    else if(_this.searchOpen) _this.closeSearch();
    else if(_this.userOpen) _this.closeUser();

    // Translate tree panel
    TweenLite.to(_this.$treeWrapper, 0.6, {x:0, ease:Expo.easeOut});

    _this.$mainContentOverlay[0].style.display = 'block';
    TweenLite.to(_this.$mainContentOverlay, 0.6, {opacity:0.5, ease:Expo.easeOut});
    
    // Add active class
    addClass(_this.$treeButton[0],'active');

    _this.treeOpen = true;
};


/**
 * Close tree
 * @return {[type]} [description]
 */
RozierMobile.prototype.closeTree = function(){
    var _this = this;

    var treeWrapperX = -Rozier.windowWidth*0.8;

    TweenLite.to(_this.$treeWrapper, 0.6, {x:treeWrapperX, ease:Expo.easeOut});

    TweenLite.to(_this.$mainContentOverlay, 0.6, {opacity:0, ease:Expo.easeOut, onComplete:function(){
        _this.$mainContentOverlay[0].style.display = 'none';
    }});

    // Remove active class
    removeClass(_this.$treeButton[0],'active');
    
    _this.treeOpen = false;  
};


/**
 * User picture click
 * @return {[type]} [description]
 */
RozierMobile.prototype.userPictureClick = function(e){
    var _this = this;

    if(!_this.userOpen)_this.openUser();
    else _this.closeUser();

    return false;
};


/**
 * User actions link click
 * @return {[type]} [description]
 */
RozierMobile.prototype.userActionsLinkClick = function(e){
    var _this = this;

    if(_this.userOpen){
        _this.closeUser();
    }
};


/**
 * Open user
 * @return {[type]} [description]
 */
RozierMobile.prototype.openUser = function(){
    var _this = this;

    // Close panel if open
    if(_this.menuOpen) _this.closeMenu();
    else if(_this.searchOpen) _this.closeSearch();
    else if(_this.treeOpen) _this.closeTree();

    // Translate user panel
    TweenLite.to(_this.$userActions, 0.6, {x:0, ease:Expo.easeOut});

    _this.$mainContentOverlay[0].style.display = 'block';
    TweenLite.to(_this.$mainContentOverlay, 0.6, {opacity:0.5, ease:Expo.easeOut});
        
    // Add active class
    addClass(_this.$userPicture[0],'active');

    _this.userOpen = true;
};


/**
 * Close user
 * @return {[type]} [description]
 */
RozierMobile.prototype.closeUser = function(){
    var _this = this;

    var userActionsX = Rozier.windowWidth*0.8;

    TweenLite.to(_this.$userActions, 0.6, {x:userActionsX, ease:Expo.easeOut});

    TweenLite.to(_this.$mainContentOverlay, 0.6, {opacity:0, ease:Expo.easeOut, onComplete:function(){
        _this.$mainContentOverlay[0].style.display = 'none';
    }});

    // Remove active class
    removeClass(_this.$userPicture[0],'active');
    
    _this.userOpen = false;  
};


/**
 * Main content overlay click
 * @return {[type]} [description]
 */
RozierMobile.prototype.mainContentOverlayClick = function(e){
    var _this = this;

     if(_this.menuOpen) _this.closeMenu();
     else if(_this.treeOpen) _this.closeTree();
     else if(_this.userOpen) _this.closeUser();
};


/**
 * Window resize callback
 * @return {[type]} [description]
 */
RozierMobile.prototype.resize = function(){
    var _this = this;

};;/**
 * Lazyload
 */
var Lazyload = function() {
    var _this = this;

    var onStateChangeProxy = $.proxy(_this.onPopState, _this);

    _this.$linksSelector = $("a:not('[target=_blank]')");

    $(window).on('popstate', function (event) {
        _this.onPopState(event);
    });

    _this.$canvasLoaderContainer = $('#canvasloader-container');
    _this.mainColor = isset(Rozier.mainColor) ? Rozier.mainColor : '#ffffff';
    _this.initLoader();

};

Lazyload.prototype.$linksSelector = null;
Lazyload.prototype.$textAreaHTMLeditor = null;
Lazyload.prototype.$HTMLeditor = null;
Lazyload.prototype.htmlEditor = [];
Lazyload.prototype.$HTMLeditorContent = null;
Lazyload.prototype.$HTMLeditorNav = null;
Lazyload.prototype.HTMLeditorNavToRemove = null;
Lazyload.prototype.documentsList = null;
Lazyload.prototype.mainColor = null;
Lazyload.prototype.$canvasLoaderContainer = null;


/**
 * Init loader
 * @return {[type]} [description]
 */
Lazyload.prototype.initLoader = function(){
    var _this = this;

    _this.canvasLoader = new CanvasLoader('canvasloader-container');
    _this.canvasLoader.setColor(_this.mainColor);
    _this.canvasLoader.setShape('square');
    _this.canvasLoader.setDensity(90);
    _this.canvasLoader.setRange(0.8);
    _this.canvasLoader.setSpeed(4);
    _this.canvasLoader.setFPS(30);

};


/**
 * Bind links to load pages
 * @param  {[type]} event [description]
 * @return {[type]}       [description]
 */
Lazyload.prototype.onClick = function(event) {
    var _this = this;

    var $link = $(event.currentTarget),
        href = $link.attr('href');

    if(typeof href !== "undefined" &&
        !$link.hasClass('rz-no-ajax-link') &&
        href !== "" &&
        href != "#" &&
        href.indexOf(Rozier.baseUrl) >= 0){

        history.pushState({}, null, $link.attr('href'));
        _this.onPopState(null);
        return false;
    }
};


/**
 * On pop state
 * @param  {[type]} event [description]
 * @return {[type]}       [description]
 */
Lazyload.prototype.onPopState = function(event) {
    var _this = this;

    var state;

    if(null !== event){
        state = event.originalEvent.state;
    }

    if(null !== state &&
        typeof state != "undefined"){

    } else {
        state = window.history.state;
    }

    //console.log(state);
    //console.log(document.location);

    if (null !== state) {
        _this.canvasLoader.show();
        _this.loadContent(state, window.location);
    }

};


/**
 * Load content (ajax)
 * @param  {[type]} state    [description]
 * @param  {[type]} location [description]
 * @return {[type]}          [description]
 */
Lazyload.prototype.loadContent = function(state, location) {
    var _this = this;

    $.ajax({
        url: location.href,
        type: 'get',
        dataType: 'html',
        data: state.headerData
    })
    .done(function(data) {
        _this.applyContent(data);
        _this.canvasLoader.hide();
    })
    .fail(function() {
        console.log("error");
        $.UIkit.notify({
            message : Rozier.messages.forbiddenPage,
            status  : 'danger',
            timeout : 3000,
            pos     : 'top-center'
        });
    });
};


/**
 * Apply content to main content
 * @param  {[type]} data [description]
 * @return {[type]}      [description]
 */
Lazyload.prototype.applyContent = function(data) {
    var _this = this;

    var $container = $('#main-content-scrollable');
    var $old = $container.find('.content-global');

    var $tempData = $(data);

    $tempData.addClass('new-content-global');
    $container.append($tempData);
    $tempData = $container.find('.new-content-global');

    $old.fadeOut(100, function () {
        $old.remove();

        _this.generalBind();
        if(isMobile.any() === null) Rozier.centerVerticalObjects('ajax');
        $tempData.fadeIn(200, function () {

            $tempData.removeClass('new-content-global');
        });
    });
};


/**
 * General bind on page load
 * @return {[type]} [description]
 */
Lazyload.prototype.generalBind = function() {
    var _this = this;

    // console.log('General bind');
    new DocumentsBulk();
    new NodesBulk();
    new DocumentWidget();
    new NodeWidget();
    new CustomFormWidget();
    new DocumentUploader(Rozier.messages.dropzone);
    new ChildrenNodesField();
    new GeotagField();
    new StackNodeTree();
    if(isMobile.any() === null) new SaveButtons();
    new TagAutocomplete();
    new FolderAutocomplete();
    new NodeTypeFieldsPosition();
    new CustomFormFieldsPosition();

    _this.documentsList = new DocumentsList();
    _this.settingsSaveButtons = new SettingsSaveButtons();
    _this.nodeTypeFieldEdit = new NodeTypeFieldEdit();
    _this.nodeEditSource = new NodeEditSource();
    _this.customFormFieldEdit = new CustomFormFieldEdit();


    _this.$linksSelector.off('click', $.proxy(_this.onClick, _this));
    _this.$linksSelector = $("a:not('[target=_blank]')");
    _this.$linksSelector.on('click', $.proxy(_this.onClick, _this));


    // Init markdown-preview
    _this.$textAreaHTMLeditor = $('textarea[data-uk-htmleditor], textarea[data-uk-rz-htmleditor]').not('[data-uk-check-display]');

    if(_this.$textAreaHTMLeditor.length){

        setTimeout(function(){
            for(var i = 0; i < _this.$textAreaHTMLeditor.length; i++) {

                _this.htmlEditor[i] = $.UIkit.htmleditor(
                    $(_this.$textAreaHTMLeditor[i]),
                    {
                        markdown:true,
                        mode:'tab',
                        labels : Rozier.messages.htmleditor
                    }
                );
                _this.$HTMLeditor = $('.uk-htmleditor');
                _this.$HTMLeditorNav = $('.uk-htmleditor-navbar');
                _this.HTMLeditorNavInner = '<div class="uk-htmleditor-navbar bottom">'+_this.$HTMLeditorNav[0].innerHTML+'</div>';

                $(_this.$HTMLeditor[i]).append(_this.HTMLeditorNavInner);

                _this.htmlEditor[i].redraw();

            }

            $(".uk-htmleditor-preview").css("height", 250);
            $(".CodeMirror").css("height", 250);

            setTimeout(function(){
                _this.$HTMLeditorNavToRemove = $('.uk-htmleditor-navbar:not(.bottom)');
                _this.$HTMLeditorNavToRemove.remove();
                new MarkdownEditor();
            }, 0);

        }, 0);

    }

    // Init colorpicker
    if($('.colorpicker-input').length){
        $('.colorpicker-input').minicolors();
    }

    // Animate actions menu
    if($('.actions-menu').length && isMobile.any() === null){
        TweenLite.to('.actions-menu', 0.5, {right:0, delay:0.4, ease:Expo.easeOut});
    }

    Rozier.initNestables();
    Rozier.bindMainTrees();
    Rozier.nodeStatuses = new NodeStatuses();

    // Switch checkboxes
    $(".rz-boolean-checkbox").bootstrapSwitch();

    Rozier.getMessages();
};


/**
 * Resize
 * @return {[type]} [description]
 */
Lazyload.prototype.resize = function(){
    var _this = this;

    _this.$canvasLoaderContainer[0].style.left = Rozier.mainContentScrollableOffsetLeft + (Rozier.mainContentScrollableWidth/2) + 'px';
};
;// is mobile
var isMobile = {
    Android: function() {
        return navigator.userAgent.match(/Android/i);
    },
    BlackBerry: function() {
        return navigator.userAgent.match(/BlackBerry/i);
    },
    iOS: function() {
        return navigator.userAgent.match(/iPhone|iPad|iPod/i);
    },
    Opera: function() {
        return navigator.userAgent.match(/Opera Mini/i);
    },
    Windows: function() {
        return navigator.userAgent.match(/IEMobile/i);
    },
    any: function() {
        return (isMobile.Android() || isMobile.BlackBerry() || isMobile.iOS() || isMobile.Opera() || isMobile.Windows());
    }
};


// Avoid `console` errors in browsers that lack a console.
(function() {
    var method;
    var noop = function () {};
    var methods = [
        'assert', 'clear', 'count', 'debug', 'dir', 'dirxml', 'error',
        'exception', 'group', 'groupCollapsed', 'groupEnd', 'info', 'log',
        'markTimeline', 'profile', 'profileEnd', 'table', 'time', 'timeEnd',
        'timeStamp', 'trace', 'warn'
    ];
    var length = methods.length;
    var console = (window.console = window.console || {});

    while (length--) {
        method = methods[length];

        // Only stub undefined methods.
        if (!console[method]) {
            console[method] = noop;
        }
    }
}());


// Strip tags
var stripTags = function(stringToStrip){
    return stringToStrip.replace(/(<([^>]+)>)/ig,"");
};


// Isset
var isset = function(element) {
    if (typeof(element) !== 'undefined') return true;
    else return false;
};


// Add class
var addClass = function(el, classToAdd){

    if (el.classList) el.classList.add(classToAdd);
    else el.className += ' ' + classToAdd;
};


// Remove class
var removeClass = function(el, classToRemove){

    if(el.classList) el.classList.remove(classToRemove);
    else{
        el.className = el.className.replace(new RegExp('(^|\\b)' + classToRemove.split(' ').join('|') + '(\\b|$)', 'gi'), '');
    
        var posLastCar = el.className.length-1;
        if(el.className[posLastCar] == ' ') el.className = el.className.substring(0, posLastCar);
    }    
};

// Place any jQuery/helper plugins in here.
// Actual
(function(a){a.fn.addBack=a.fn.addBack||a.fn.andSelf;
a.fn.extend({actual:function(b,l){if(!this[b]){throw'$.actual => The jQuery method "'+b+'" you called does not exist';}var f={absolute:false,clone:false,includeMargin:false};
var i=a.extend(f,l);var e=this.eq(0);var h,j;if(i.clone===true){h=function(){var m="position: absolute !important; top: -1000 !important; ";e=e.clone().attr("style",m).appendTo("body");
};j=function(){e.remove();};}else{var g=[];var d="";var c;h=function(){c=e.parents().addBack().filter(":hidden");d+="visibility: hidden !important; display: block !important; ";
if(i.absolute===true){d+="position: absolute !important; ";}c.each(function(){var m=a(this);var n=m.attr("style");g.push(n);m.attr("style",n?n+";"+d:d);
});};j=function(){c.each(function(m){var o=a(this);var n=g[m];if(n===undefined){o.removeAttr("style");}else{o.attr("style",n);}});};}h();var k=/(outer)/.test(b)?e[b](i.includeMargin):e[b]();
j();return k;}});})(jQuery);

// Heartcode canvas loader - http://heartcode-canvasloader.googlecode.com/files/heartcode-canvasloader-min-0.9.1.js
(function(w){var k=function(b,c){typeof c=="undefined"&&(c={});this.init(b,c)},a=k.prototype,o,p=["canvas","vml"],f=["oval","spiral","square","rect","roundRect"],x=/^\#([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/,v=navigator.appVersion.indexOf("MSIE")!==-1&&parseFloat(navigator.appVersion.split("MSIE")[1])===8?true:false,y=!!document.createElement("canvas").getContext,q=true,n=function(b,c,a){var b=document.createElement(b),d;for(d in a)b[d]=a[d];typeof c!=="undefined"&&c.appendChild(b);return b},m=function(b,
c){for(var a in c)b.style[a]=c[a];return b},t=function(b,c){for(var a in c)b.setAttribute(a,c[a]);return b},u=function(b,c,a,d){b.save();b.translate(c,a);b.rotate(d);b.translate(-c,-a);b.beginPath()};a.init=function(b,c){if(typeof c.safeVML==="boolean")q=c.safeVML;try{this.mum=document.getElementById(b)!==void 0?document.getElementById(b):document.body}catch(a){this.mum=document.body}c.id=typeof c.id!=="undefined"?c.id:"canvasLoader";this.cont=n("div",this.mum,{id:c.id});if(y)o=p[0],this.can=n("canvas",
this.cont),this.con=this.can.getContext("2d"),this.cCan=m(n("canvas",this.cont),{display:"none"}),this.cCon=this.cCan.getContext("2d");else{o=p[1];if(typeof k.vmlSheet==="undefined"){document.getElementsByTagName("head")[0].appendChild(n("style"));k.vmlSheet=document.styleSheets[document.styleSheets.length-1];var d=["group","oval","roundrect","fill"],e;for(e in d)k.vmlSheet.addRule(d[e],"behavior:url(#default#VML); position:absolute;")}this.vml=n("group",this.cont)}this.setColor(this.color);this.draw();
m(this.cont,{display:"none"})};a.cont={};a.can={};a.con={};a.cCan={};a.cCon={};a.timer={};a.activeId=0;a.diameter=40;a.setDiameter=function(b){this.diameter=Math.round(Math.abs(b));this.redraw()};a.getDiameter=function(){return this.diameter};a.cRGB={};a.color="#000000";a.setColor=function(b){this.color=x.test(b)?b:"#000000";this.cRGB=this.getRGB(this.color);this.redraw()};a.getColor=function(){return this.color};a.shape=f[0];a.setShape=function(b){for(var c in f)if(b===f[c]){this.shape=b;this.redraw();
break}};a.getShape=function(){return this.shape};a.density=40;a.setDensity=function(b){this.density=q&&o===p[1]?Math.round(Math.abs(b))<=40?Math.round(Math.abs(b)):40:Math.round(Math.abs(b));if(this.density>360)this.density=360;this.activeId=0;this.redraw()};a.getDensity=function(){return this.density};a.range=1.3;a.setRange=function(b){this.range=Math.abs(b);this.redraw()};a.getRange=function(){return this.range};a.speed=2;a.setSpeed=function(b){this.speed=Math.round(Math.abs(b))};a.getSpeed=function(){return this.speed};
a.fps=24;a.setFPS=function(b){this.fps=Math.round(Math.abs(b));this.reset()};a.getFPS=function(){return this.fps};a.getRGB=function(b){b=b.charAt(0)==="#"?b.substring(1,7):b;return{r:parseInt(b.substring(0,2),16),g:parseInt(b.substring(2,4),16),b:parseInt(b.substring(4,6),16)}};a.draw=function(){var b=0,c,a,d,e,h,k,j,r=this.density,s=Math.round(r*this.range),l,i,q=0;i=this.cCon;var g=this.diameter;if(o===p[0]){i.clearRect(0,0,1E3,1E3);t(this.can,{width:g,height:g});for(t(this.cCan,{width:g,height:g});b<
r;){l=b<=s?1-1/s*b:l=0;k=270-360/r*b;j=k/180*Math.PI;i.fillStyle="rgba("+this.cRGB.r+","+this.cRGB.g+","+this.cRGB.b+","+l.toString()+")";switch(this.shape){case f[0]:case f[1]:c=g*0.07;e=g*0.47+Math.cos(j)*(g*0.47-c)-g*0.47;h=g*0.47+Math.sin(j)*(g*0.47-c)-g*0.47;i.beginPath();this.shape===f[1]?i.arc(g*0.5+e,g*0.5+h,c*l,0,Math.PI*2,false):i.arc(g*0.5+e,g*0.5+h,c,0,Math.PI*2,false);break;case f[2]:c=g*0.12;e=Math.cos(j)*(g*0.47-c)+g*0.5;h=Math.sin(j)*(g*0.47-c)+g*0.5;u(i,e,h,j);i.fillRect(e,h-c*0.5,
c,c);break;case f[3]:case f[4]:a=g*0.3,d=a*0.27,e=Math.cos(j)*(d+(g-d)*0.13)+g*0.5,h=Math.sin(j)*(d+(g-d)*0.13)+g*0.5,u(i,e,h,j),this.shape===f[3]?i.fillRect(e,h-d*0.5,a,d):(c=d*0.55,i.moveTo(e+c,h-d*0.5),i.lineTo(e+a-c,h-d*0.5),i.quadraticCurveTo(e+a,h-d*0.5,e+a,h-d*0.5+c),i.lineTo(e+a,h-d*0.5+d-c),i.quadraticCurveTo(e+a,h-d*0.5+d,e+a-c,h-d*0.5+d),i.lineTo(e+c,h-d*0.5+d),i.quadraticCurveTo(e,h-d*0.5+d,e,h-d*0.5+d-c),i.lineTo(e,h-d*0.5+c),i.quadraticCurveTo(e,h-d*0.5,e+c,h-d*0.5))}i.closePath();i.fill();
i.restore();++b}}else{m(this.cont,{width:g,height:g});m(this.vml,{width:g,height:g});switch(this.shape){case f[0]:case f[1]:j="oval";c=140;break;case f[2]:j="roundrect";c=120;break;case f[3]:case f[4]:j="roundrect",c=300}a=d=c;e=500-d;for(h=-d*0.5;b<r;){l=b<=s?1-1/s*b:l=0;k=270-360/r*b;switch(this.shape){case f[1]:a=d=c*l;e=500-c*0.5-c*l*0.5;h=(c-c*l)*0.5;break;case f[0]:case f[2]:v&&(h=0,this.shape===f[2]&&(e=500-d*0.5));break;case f[3]:case f[4]:a=c*0.95,d=a*0.28,v?(e=0,h=500-d*0.5):(e=500-a,h=
-d*0.5),q=this.shape===f[4]?0.6:0}i=t(m(n("group",this.vml),{width:1E3,height:1E3,rotation:k}),{coordsize:"1000,1000",coordorigin:"-500,-500"});i=m(n(j,i,{stroked:false,arcSize:q}),{width:a,height:d,top:h,left:e});n("fill",i,{color:this.color,opacity:l});++b}}this.tick(true)};a.clean=function(){if(o===p[0])this.con.clearRect(0,0,1E3,1E3);else{var b=this.vml;if(b.hasChildNodes())for(;b.childNodes.length>=1;)b.removeChild(b.firstChild)}};a.redraw=function(){this.clean();this.draw()};a.reset=function(){typeof this.timer===
"number"&&(this.hide(),this.show())};a.tick=function(b){var a=this.con,f=this.diameter;b||(this.activeId+=360/this.density*this.speed);o===p[0]?(a.clearRect(0,0,f,f),u(a,f*0.5,f*0.5,this.activeId/180*Math.PI),a.drawImage(this.cCan,0,0,f,f),a.restore()):(this.activeId>=360&&(this.activeId-=360),m(this.vml,{rotation:this.activeId}))};a.show=function(){if(typeof this.timer!=="number"){var a=this;this.timer=self.setInterval(function(){a.tick()},Math.round(1E3/this.fps));m(this.cont,{display:"block"})}};
a.hide=function(){typeof this.timer==="number"&&(clearInterval(this.timer),delete this.timer,m(this.cont,{display:"none"}))};a.kill=function(){var a=this.cont;typeof this.timer==="number"&&this.hide();o===p[0]?(a.removeChild(this.can),a.removeChild(this.cCan)):a.removeChild(this.vml);for(var c in this)delete this[c]};w.CanvasLoader=k})(window);


/*
 * Pointer Events Polyfill: Adds support for the style attribute "pointer-events: none" to browsers without this feature (namely, IE).
 * (c) 2013, Kent Mewhort, licensed under BSD. See LICENSE.txt for details.
 */
// constructor
function PointerEventsPolyfill(options){
    // set defaults
    this.options = {
        selector: '*',
        mouseEvents: ['click','dblclick','mousedown','mouseup'],
        usePolyfillIf: function(){
            if(navigator.appName == 'Microsoft Internet Explorer')
            {
                var agent = navigator.userAgent;
                if (agent.match(/MSIE ([0-9]{1,}[\.0-9]{0,})/) != null){
                    var version = parseFloat( RegExp.$1 );
                    if(version < 11)
                      return true;
                }
            }
            return false;
        }
    };
    if(options){
        var obj = this;
        $.each(options, function(k,v){
          obj.options[k] = v;
        });
    }

    if(this.options.usePolyfillIf())
      this.register_mouse_events();
}

// singleton initializer
PointerEventsPolyfill.initialize = function(options){
    if(PointerEventsPolyfill.singleton == null)
      PointerEventsPolyfill.singleton = new PointerEventsPolyfill(options);
    return PointerEventsPolyfill.singleton;
};

// handle mouse events w/ support for pointer-events: none
PointerEventsPolyfill.prototype.register_mouse_events = function(){
    // register on all elements (and all future elements) matching the selector
    $(document).on(this.options.mouseEvents.join(" "), this.options.selector, function(e){
       if($(this).css('pointer-events') == 'none'){
             // peak at the element below
             var origDisplayAttribute = $(this).css('display');
             $(this).css('display','none');

             var underneathElem = document.elementFromPoint(e.clientX, e.clientY);

            if(origDisplayAttribute)
                $(this)
                    .css('display', origDisplayAttribute);
            else
                $(this).css('display','');

             // fire the mouse event on the element below
            e.target = underneathElem;
            $(underneathElem).trigger(e);

            return false;
        }
        return true;
    });
};
;/*
 * ============================================================================
 * Rozier entry point
 * ============================================================================
 */

var Rozier = {};

Rozier.$window = null;
Rozier.$body = null;

Rozier.windowWidth = null;
Rozier.windowHeight = null;
Rozier.resizeFirst = true;
Rozier.gMapLoaded = false;

Rozier.searchNodesSourcesDelay = null;
Rozier.nodeTrees = [];
Rozier.treeTrees = [];

Rozier.$userPanelContainer = null;
Rozier.$minifyTreePanelButton = null;
Rozier.$mainTrees = null;
Rozier.$mainTreesContainer = null;
Rozier.$mainTreeElementName = null;
Rozier.$treeContextualButton = null;
Rozier.$nodesSourcesSearch = null;
Rozier.nodesSourcesSearchHeight = null;
Rozier.$nodeTreeHead = null;
Rozier.nodeTreeHeadHeight = null;
Rozier.$treeScrollCont = null;
Rozier.$treeScroll = null;
Rozier.treeScrollHeight = null;

Rozier.$mainContentScrollable = null;
Rozier.mainContentScrollableWidth = null;
Rozier.mainContentScrollableOffsetLeft = null;
Rozier.$backTopBtn = null;


Rozier.onDocumentReady = function(event) {

	/*
	 * Store Rozier configuration
	 */
	for( var index in temp ){
		Rozier[index] = temp[index];
	}

	Rozier.lazyload = new Lazyload();

	Rozier.$window = $(window);
	Rozier.$body = $('body');

	if(isMobile.any() === null) Rozier.centerVerticalObjects(); // this must be done before generalBind!


	// --- Selectors --- //
	Rozier.$userPanelContainer = $('#user-panel-container');
	Rozier.$minifyTreePanelButton = $('#minify-tree-panel-button');
	Rozier.$mainTrees = $('#main-trees');
	Rozier.$mainTreesContainer = $('#main-trees-container');
	Rozier.$nodesSourcesSearch = $('#nodes-sources-search');

	Rozier.$mainContentScrollable = $('#main-content-scrollable');
	Rozier.$backTopBtn = $('#back-top-button');

	// Pointer events polyfill
    if(!Modernizr.testProp('pointerEvents')){
        PointerEventsPolyfill.initialize({'selector':'#main-trees-overlay'});
    }


    // --- Events --- //

	// Search node
	$("#nodes-sources-search-input").on('focus', function(){
		$('#nodes-sources-search').addClass("focus-on");
		$('#nodes-sources-search-results').fadeIn();
		setTimeout(function(){ Rozier.resize(); }, 500);
	});
	$("#nodes-sources-search-input").on('focusout', function(){
		$('#nodes-sources-search-results').fadeOut();
		$('#nodes-sources-search').removeClass("focus-on");
		$(this).val("");
		setTimeout(function(){ Rozier.resize(); }, 500);
	});
	$("#nodes-sources-search-input").on('keyup', Rozier.onSearchNodesSources);

	// Minify trees panel toggle button
	Rozier.$minifyTreePanelButton.on('click', Rozier.toggleTreesPanel);

	// Back top btn
	Rozier.$backTopBtn.on('click', $.proxy(Rozier.backTopBtnClick, Rozier));

	Rozier.lazyload.generalBind();
	Rozier.bindMainNodeTreeLangs();

	Rozier.$window.on('resize', $.proxy(Rozier.resize, Rozier));
	Rozier.$window.trigger('resize');
};


/**
 * init nestable for ajax
 * @return {[type]} [description]
 */
Rozier.initNestables = function  () {
	var _this = this;

	$('.uk-nestable').each(function (index, element) {
        $.UIkit.nestable(element);
    });
};


/**
 * Bind main trees
 * @return {[type]} [description]
 */
Rozier.bindMainTrees = function () {
	var _this = this;

	// TREES
	$('.nodetree-widget .root-tree').off('uk.nestable.change');
	$('.nodetree-widget .root-tree').on('uk.nestable.change', Rozier.onNestableNodeTreeChange );

	$('.tagtree-widget .root-tree').off('uk.nestable.change');
	$('.tagtree-widget .root-tree').on('uk.nestable.change', Rozier.onNestableTagTreeChange );

	$('.foldertree-widget .root-tree').off('uk.nestable.change');
	$('.foldertree-widget .root-tree').on('uk.nestable.change', Rozier.onNestableFolderTreeChange );
	
	// Tree element name 
	_this.$mainTreeElementName = _this.$mainTrees.find('.tree-element-name');	
	_this.$mainTreeElementName.off('contextmenu', $.proxy(_this.maintreeElementNameRightClick, _this));
	_this.$mainTreeElementName.on('contextmenu', $.proxy(_this.maintreeElementNameRightClick, _this));

};


/**
 * Main tree element name right click
 * @return {[type]} [description]
 */
Rozier.maintreeElementNameRightClick = function(e){
	var _this = this;

	var $contextualMenu = $(e.currentTarget).parent().find('.tree-contextualmenu');

	if($contextualMenu[0].className.indexOf('uk-open') == -1) {
		addClass($contextualMenu[0], 'uk-open');
	}
	else removeClass($contextualMenu[0], 'uk-open');


	return false;

};


/**
 * Bind main node tree langs
 * @return {[type]} [description]
 */
Rozier.bindMainNodeTreeLangs = function () {
	var _this = this;

	$('body').on('click', '#tree-container .nodetree-langs a', function (event) {

		var $link = $(event.currentTarget);
		var translationId = parseInt($link.attr('data-translation-id'));

		Rozier.refreshMainNodeTree(translationId);
	});
};


/**
 * Get messages
 * @return {[type]} [description]
 */
Rozier.getMessages = function () {
	var _this = this;

	$.ajax({
		url: Rozier.routes.ajaxSessionMessages,
		type: 'GET',
		dataType: 'json',
		data: {
			"_action": 'messages',
			"_token": Rozier.ajaxToken
		},
	})
	.done(function(data) {
		if (typeof data.messages !== "undefined") {

			if (typeof data.messages.confirm !== "undefined" &&
						data.messages.confirm.length > 0) {

				for (var i = data.messages.confirm.length - 1; i >= 0; i--) {

					$.UIkit.notify({
						message : data.messages.confirm[i],
						status  : 'success',
						timeout : 2000,
						pos     : 'top-center'
					});
				}
			}

			if (typeof data.messages.error !== "undefined" &&
						data.messages.error.length > 0) {

				for (var j = data.messages.error.length - 1; j >= 0; j--) {

					$.UIkit.notify({
						message : data.messages.error[j],
						status  : 'error',
						timeout : 2000,
						pos     : 'top-center'
					});
				}
			}
		}
	})
	.fail(function() {
		console.log("[Rozier.getMessages] error");
	});
};


/**
 * Refresh only main nodeTree.
 *
 */
Rozier.refreshMainNodeTree = function (translationId) {
	var _this = this;

	var $currentNodeTree = $('#tree-container').find('.nodetree-widget');

	if($currentNodeTree.length){

		var postData = {
		    "_token": Rozier.ajaxToken,
		    "_action":'requestMainNodeTree'
		};

		var url = Rozier.routes.nodesTreeAjax;
		if(isset(translationId) && translationId > 0){
			url += '/'+translationId;
		}

		$.ajax({
			url: url,
			type: 'post',
			dataType: 'json',
			data: postData,
		})
		.done(function(data) {
			//console.log("success");
			//console.log(data);

			if($currentNodeTree.length &&
				typeof data.nodeTree != "undefined"){

				$currentNodeTree.fadeOut('slow', function() {
					$currentNodeTree.replaceWith(data.nodeTree);
					$currentNodeTree = $('#tree-container').find('.nodetree-widget');
					$currentNodeTree.fadeIn();
					Rozier.initNestables();
					Rozier.bindMainTrees();
					Rozier.resize();
					Rozier.lazyload.generalBind();
				});
			}
		})
		.fail(function(data) {
			console.log(data.responseJSON);
		});
	} else {
		console.error("No main node-tree available.");
	}
};


/*
 * Center vetically every DOM objects that have
 * the data-vertical-center attribute
 */
Rozier.centerVerticalObjects = function(context) {
	var _this = this;

	// console.log('center vertical objects');
	// console.log(context);
	var $objects = $(".rz-vertical-align");

	for(var i = 0; i < $objects.length; i++) {
		$objects[i].style.top = '50%';
		$objects[i].style.marginTop = $($objects[i]).actual('outerHeight')/-2 +'px';
		if($objects[i].className.indexOf('actions-menu') >= 0 && context == 'ajax'){
			$objects[i].style.right = - $($objects[i]).actual('outerWidth')+'px';
		}
	}
};


/**
 * Toggle trees panel
 * @param  {[type]} event [description]
 * @return {[type]}       [description]
 */
Rozier.toggleTreesPanel = function (event) {
	var _this = this;

	$('#main-trees').toggleClass('minified');
	$('#minify-tree-panel-button i').toggleClass('uk-icon-rz-panel-tree-open');
	$('#minify-tree-panel-area').toggleClass('tree-panel-hidden');

	return false;
};


/**
 * Toggle user panel
 * @param  {[type]} event [description]
 * @return {[type]}       [description]
 */
Rozier.toggleUserPanel = function (event) {
	var _this = this;

	$('#user-panel').toggleClass('minified');

	return false;
};


/**
 * Handle ajax search node source.
 *
 * @param event
 */
Rozier.onSearchNodesSources = function (event) {
	var _this = this;

	var $input = $(event.currentTarget);

	if ($input.val().length > 2) {
		clearTimeout(Rozier.searchNodesSourcesDelay);
		Rozier.searchNodesSourcesDelay = setTimeout(function () {
			var postData = {
				_token: Rozier.ajaxToken,
				_action:'searchNodesSources',
				searchTerms: $input.val()
			};
			console.log(postData);
			$.ajax({
				url: Rozier.routes.searchNodesSourcesAjax,
				type: 'POST',
				dataType: 'json',
				data: postData
			})
			.done(function( data ) {
				console.log(data);

				if (typeof data.data != "undefined" &&
					data.data.length > 0) {

					$results = $('#nodes-sources-search-results');
					$results.empty();

					for(var i in data.data) {
						$results.append('<li><a href="'+data.data[i].url+
								'" style="border-left-color:'+data.data[i].typeColor+'"><span class="title">'+data.data[i].title+
						    	'</span> <span class="type">'+data.data[i].typeName+
						    	'</span></a></li>');
					}
					$results.append('<a id="see-all" href="#">'+Rozier.messages.see_all+'</a>'); //Trans message (base.html.twig)
				}
			})
			.fail(function( data ) {
				console.log(data);
			});
		}, 300);
	}
};


/**
 *
 * @param  Event event
 * @param  jQueryNode element
 * @param  string status  added, moved or removed
 * @return boolean
 */
Rozier.onNestableNodeTreeChange = function (event, element, status) {
	var _this = this;

	console.log("Node: "+element.data('node-id')+ " status : "+status);

	/*
	 * If node removed, do not do anything, the otheuk.nestable.changer nodeTree will be triggered
	 */
	if (status == 'removed') {
		return false;
	}

	var node_id = parseInt(element.data('node-id'));
	var parent_node_id = parseInt(element.parents('ul').first().data('parent-node-id'));

	console.log(parent_node_id);
	/*
	 * User dragged node inside itself
	 * It will destroy the Internet !
	 */
	if (node_id === parent_node_id) {
		console.log("You cannot move a node inside itself!");
		alert("You cannot move a node inside itself!");
		window.location.reload();
		return false;
	}

	var postData = {
		_token: Rozier.ajaxToken,
		_action: 'updatePosition',
		nodeId: node_id
	};

	/*
	 * Get node siblings id to compute new position
	 */
	if (element.next().length) {
		postData.nextNodeId = parseInt(element.next().data('node-id'));
	}
	else if(element.prev().length) {
		postData.prevNodeId = parseInt(element.prev().data('node-id'));
	}

	/*
	 * When dropping to route
	 * set parentNodeId to NULL
	 */
	if(isNaN(parent_node_id)){
		parent_node_id = null;
	}
	postData.newParent = parent_node_id;

	console.log(postData);
	$.ajax({
		url: Rozier.routes.nodeAjaxEdit.replace("%nodeId%", node_id),
		type: 'POST',
		dataType: 'json',
		data: postData
	})
	.done(function( data ) {
		console.log(data);
		$.UIkit.notify({
			message : data.responseText,
			status  : data.status,
			timeout : 3000,
			pos     : 'top-center'
		});

	})
	.fail(function( data ) {
		console.log(data);
	});
};


/**
 *
 * @param  Event event
 * @param  jQueryTag element
 * @param  string status  added, moved or removed
 * @return boolean
 */
Rozier.onNestableTagTreeChange = function (event, element, status) {
	var _this = this;

	console.log("Tag: "+element.data('tag-id')+ " status : "+status);

	/*
	 * If tag removed, do not do anything, the other tagTree will be triggered
	 */
	if (status == 'removed') {
		return false;
	}

	var tag_id = parseInt(element.data('tag-id'));
	var parent_tag_id = parseInt(element.parents('ul').first().data('parent-tag-id'));

	/*
	 * User dragged tag inside itself
	 * It will destroy the Internet !
	 */
	if (tag_id === parent_tag_id) {
		console.log("You cannot move a tag inside itself!");
		alert("You cannot move a tag inside itself!");
		window.location.reload();
		return false;
	}

	var postData = {
		_token: Rozier.ajaxToken,
		_action: 'updatePosition',
		tagId: tag_id
	};

	/*
	 * Get tag siblings id to compute new position
	 */
	if (element.next().length) {
		postData.nextTagId = parseInt(element.next().data('tag-id'));
	}
	else if(element.prev().length) {
		postData.prevTagId = parseInt(element.prev().data('tag-id'));
	}

	/*
	 * When dropping to route
	 * set parentTagId to NULL
	 */
	if(isNaN(parent_tag_id)){
		parent_tag_id = null;
	}
	postData.newParent = parent_tag_id;

	console.log(postData);
	$.ajax({
		url: Rozier.routes.tagAjaxEdit.replace("%tagId%", tag_id),
		type: 'POST',
		dataType: 'json',
		data: postData
	})
	.done(function( data ) {
		console.log(data);
		$.UIkit.notify({
			message : data.responseText,
			status  : data.status,
			timeout : 3000,
			pos     : 'top-center'
		});

	})
	.fail(function( data ) {
		console.log(data);
	});
};

/**
 *
 * @param  Event event
 * @param  jQueryFolder element
 * @param  string status  added, moved or removed
 * @return boolean
 */
Rozier.onNestableFolderTreeChange = function (event, element, status) {
	var _this = this;

	console.log("Folder: "+element.data('folder-id')+ " status : "+status);

	/*
	 * If folder removed, do not do anything, the other folderTree will be triggered
	 */
	if (status == 'removed') {
		return false;
	}

	var folder_id = parseInt(element.data('folder-id'));
	var parent_folder_id = parseInt(element.parents('ul').first().data('parent-folder-id'));

	/*
	 * User dragged folder inside itself
	 * It will destroy the Internet !
	 */
	if (folder_id === parent_folder_id) {
		console.log("You cannot move a folder inside itself!");
		alert("You cannot move a folder inside itself!");
		window.location.reload();
		return false;
	}

	var postData = {
		_token: Rozier.ajaxToken,
		_action: 'updatePosition',
		folderId: folder_id
	};

	/*
	 * Get folder siblings id to compute new position
	 */
	if (element.next().length) {
		postData.nextFolderId = parseInt(element.next().data('folder-id'));
	}
	else if(element.prev().length) {
		postData.prevFolderId = parseInt(element.prev().data('folder-id'));
	}

	/*
	 * When dropping to route
	 * set parentFolderId to NULL
	 */
	if(isNaN(parent_folder_id)){
		parent_folder_id = null;
	}
	postData.newParent = parent_folder_id;

	console.log(postData);
	$.ajax({
		url: Rozier.routes.folderAjaxEdit.replace("%folderId%", folder_id),
		type: 'POST',
		dataType: 'json',
		data: postData
	})
	.done(function( data ) {
		console.log(data);
		$.UIkit.notify({
			message : data.responseText,
			status  : data.status,
			timeout : 3000,
			pos     : 'top-center'
		});

	})
	.fail(function( data ) {
		console.log(data);
	});
};


/**
 * Back top click
 * @return {[type]} [description]
 */
Rozier.backTopBtnClick = function(e){
	var _this = this;

	TweenLite.to(_this.$mainContentScrollable, 0.6, {scrollTo:{y:0}, ease:Expo.easeOut});

	return false;
};


/**
 * Resize
 * @return {[type]} [description]
 */
Rozier.resize = function(){
	var _this = this;

	_this.windowWidth = _this.$window.width();
	_this.windowHeight = _this.$window.height();

	// Close tree panel if small screen & first resize
	if(_this.windowWidth > 768 && _this.windowWidth <= 1200 && _this.resizeFirst){
		_this.$mainTrees[0].style.display = 'none';
		_this.$minifyTreePanelButton.trigger('click');
		setTimeout(function(){
			_this.$mainTrees[0].style.display = 'table-cell';
		}, 1000);
	}

	// Check if mobile
	if(_this.windowWidth <= 768 && _this.resizeFirst) _this.mobile = new RozierMobile(); // && isMobile.any() !== null 


	// Set height to panels (fix for IE9,10)
	if(isMobile.any() === null){
		_this.$userPanelContainer[0].style.height = _this.windowHeight+'px';
		_this.$mainTreesContainer[0].style.height = _this.windowHeight+'px';
	}
	_this.$mainContentScrollable[0].style.height = _this.windowHeight+'px';  

	// Tree scroll height
	_this.$nodeTreeHead = _this.$mainTrees.find('.nodetree-head');
	_this.$treeScrollCont = _this.$mainTrees.find('.tree-scroll-cont');
	_this.$treeScroll = _this.$mainTrees.find('.tree-scroll');
	_this.nodesSourcesSearchHeight = _this.$nodesSourcesSearch.height();
	_this.nodeTreeHeadHeight = _this.$nodeTreeHead.height();
	_this.treeScrollHeight = _this.windowHeight - (_this.nodesSourcesSearchHeight + _this.nodeTreeHeadHeight);

	if(isMobile.any() !== null) _this.treeScrollHeight = _this.windowHeight - (50 + 50 + _this.nodeTreeHeadHeight); // Menu + tree menu + tree head

	// console.log('search height           : '+_this.nodesSourcesSearchHeight);
	// console.log('node tree head height : '+_this.nodeTreeHeadHeight);
	// console.log('windows height          : '+_this.windowHeight);
	// console.log('tree scroll height     : '+_this.treeScrollHeight);
	// console.log('----------------');

	for(var i = 0; i < _this.$treeScrollCont.length; i++) {
		_this.$treeScrollCont[i].style.height = _this.treeScrollHeight + 'px';
	}

	// Main content
	_this.mainContentScrollableWidth = _this.$mainContentScrollable.width();
	_this.mainContentScrollableOffsetLeft = _this.windowWidth - _this.mainContentScrollableWidth;

	_this.lazyload.resize();

	// Documents list
	if(_this.lazyload !== null && !_this.resizeFirst) _this.lazyload.documentsList.resize();

	// Set resize first to false
	if(_this.resizeFirst) _this.resizeFirst = false;

};


/*
 * ============================================================================
 * Plug into jQuery standard events
 * ============================================================================
 */
$(document).ready(Rozier.onDocumentReady);
