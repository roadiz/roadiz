/**
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
 * Init
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

    // _this.$toggleExplorerButtons.trigger('click');

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

        $.ajax({
            url: Rozier.routes.documentsAjaxExplorer,
            type: 'get',
            dataType: 'json',
            data: ajaxData
        })
        .success(function(data) {
            console.log(data);
            console.log("success");

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
DocumentWidget.prototype.onExplorerSearch = function(event) {
    var _this = this;

    if (_this.$explorer !== null){
        var $search = $(event.currentTarget).find('#documents-search-input');

        var ajaxData = {
            '_action':'toggleExplorer',
            '_token': Rozier.ajaxToken,
            'search': $search.val()
        };

        $.ajax({
            url: Rozier.routes.documentsAjaxExplorer,
            type: 'get',
            dataType: 'json',
            data: ajaxData
        })
        .success(function(data) {
            console.log(data);
            console.log("success");

            if (typeof data.documents != "undefined") {

                var $currentsortable = $($(event.currentTarget).parents('.documents-widget')[0]).find('.documents-widget-sortable');
                _this.appendItemsToExplorer(data, $currentsortable, true);
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
DocumentWidget.prototype.onExplorerNextPage = function(filters, event) {
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
            url: Rozier.routes.documentsAjaxExplorer,
            type: 'get',
            dataType: 'json',
            data: ajaxData
        })
        .success(function(data) {
            console.log(data);
            console.log("success");

            if (typeof data.documents != "undefined") {

                var $currentsortable = $($(event.currentTarget).parents('.documents-widget')[0]).find('.documents-widget-sortable');
                _this.appendItemsToExplorer(data, $currentsortable);
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
    _this.$explorer.find('.explorer-search').on('submit', $.proxy(_this.onExplorerSearch, _this));


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

    if (typeof replace !== 'undefined' && replace === true) {
        $sortable.empty();
    };

    /*
     * Add documents
     */
    for (var i = 0; i < data.documents.length; i++) {
        var doc = data.documents[i];
        $sortable.append(doc.html);
    }

    /*
     * Add pagination
     */
    if (typeof data.filters.nextPage !== 'undefined' &&
        data.filters.nextPage > 1) {

        $sortable.append([
            '<li class="document-widget-explorer-nextpage">',
                '<i class="uk-icon-plus"></i><span class="label">More documents</span>',
            '</li>'
        ].join(''));

        $sortable.find('.document-widget-explorer-nextpage').on('click', $.proxy(_this.onExplorerNextPage, _this, data.filters));
    };

    var onAddClick = $.proxy(function (event) {

        var $object = $(event.currentTarget).parent();
        $object.appendTo($originWidget);

        var inputName = 'source['+$originWidget.data('input-name')+']';
        $originWidget.find('li').each(function (index, element) {
            $(element).find('input').attr('name', inputName+'['+index+']');
        });

        return false;
    }, _this);

    $sortable.find('li').each (function (index, element) {
        var $link = $(element).find('.link-button');
        if($link.length){
            $link.off('click', onAddClick);
            $link.on('click', onAddClick);
        }
    });
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
