/**
 * DOCUMENT WIDGET
 */
var DocumentWidget = function () {
    var _this = this;

    _this.$explorer = null;
    _this.explorer = null;
    _this.$explorerClose = null;
    _this.uploader = null;

    _this.options = {
        selector: '.documents-widget .documents-widget-uploader',
        headers: { "_token": Rozier.ajaxToken },
        onSuccess : $.proxy(_this.onDocumentUploaded, _this),
    };
    $.extend(_this.options, Rozier.messages.dropzone);

    _this.$widgets = $('[data-document-widget]');

    if (_this.$widgets.length) {
        _this.$sortables = $('.documents-widget-sortable');
        _this.$toggleExplorerButtons = $('[data-document-widget-toggle-explorer]');
        _this.$toggleUploaderButtons = $('[data-document-widget-toggle-uploader]');

        _this.init();
    }
};

/**
 * Init.
 *
 * @return {[type]} [description]
 */
DocumentWidget.prototype.init = function() {
    var _this = this;

    var changeProxy = $.proxy(_this.onSortableDocumentWidgetChange, _this);
    _this.$sortables.off('change.uk.sortable', changeProxy);
    _this.$sortables.on('change.uk.sortable', changeProxy);

    var onExplorerToggleP = $.proxy(_this.onExplorerToggle, _this);
    _this.$toggleExplorerButtons.off('click', onExplorerToggleP);
    _this.$toggleExplorerButtons.on('click', onExplorerToggleP);

    var onUploaderToggleP = $.proxy(_this.onUploaderToggle, _this);
    _this.$toggleUploaderButtons.off('click', onUploaderToggleP);
    _this.$toggleUploaderButtons.on('click', onUploaderToggleP);

    _this.initUnlinkEvent();

    Rozier.$window.on('keyup', $.proxy(_this.echapKey, _this));
    Rozier.$window.on('uploader-open', $.proxy(_this.closeAll, _this));
    Rozier.$window.on('explorer-open', $.proxy(_this.closeUploader, _this));
    Rozier.$window.on('pagechange', $.proxy(_this.closeAll, _this));
};

DocumentWidget.prototype.closeAll = function(event) {
    var _this = this;
    event.preventDefault();

    var $uploaders = $('.documents-widget-uploader');

    if (null !== _this.uploader) {
        _this.uploader = null;
        _this.$toggleUploaderButtons.removeClass('active uk-active');
        $uploaders.remove();
    }
};

DocumentWidget.prototype.initUnlinkEvent = function() {
    var _this = this;

    _this.$unlinkDocumentButtons = $('[data-document-widget-unlink-document]');

    var onUnlinkDocumentP = $.proxy(_this.onUnlinkDocument, _this);
    _this.$unlinkDocumentButtons.off('click');
    _this.$unlinkDocumentButtons.on('click', onUnlinkDocumentP);
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

    _this.$btn = $(event.currentTarget);

    if (_this.$btn.hasClass('active') &&
        null !== _this.uploader &&
        _this.$uploader &&
        _this.$uploader.length) {
        _this.closeUploader(event);
    } else {
        /*
         * Dispatch event to close every other
         * uploaders
         */
        var openevent = new CustomEvent("uploader-open", {"detail":_this});
        window.dispatchEvent(openevent);


        _this.$widget = _this.$btn.parents('.documents-widget').eq(0);
        _this.$widget.append('<div class="documents-widget-uploader dropzone"></div>');
        _this.$uploader = _this.$widget.find('.documents-widget-uploader').eq(0);

        if (_this.$uploader) {
            _this.uploader = new DocumentUploader(_this.options);
            _this.$uploader.show();
            _this.$btn.addClass('active uk-active');
        }
    }

    return false;
};

/**
 * On uploader toggle
 * @param  {[type]} event [description]
 * @return {[type]}       [description]
 */
DocumentWidget.prototype.closeUploader = function(event) {
    var _this = this;

    if (null !== _this.uploader &&
        _this.$uploader &&
        _this.$uploader.length) {
        _this.$uploader.remove();
        _this.uploader = null;
        _this.$btn.removeClass('active uk-active');
    }

    return false;
};

DocumentWidget.prototype.onDocumentUploaded = function(data) {
    var _this = this;

    if(typeof data.thumbnail !== "undefined" && _this.$widget.length) {
        var $sortable = _this.$widget.find('.documents-widget-sortable');
        $sortable.append(data.thumbnail.html);
        var $element = $sortable.find('[data-document-id="'+data.thumbnail.id+'"]').eq(0);

        _this.initUnlinkEvent();
        Rozier.lazyload.bindAjaxLink();

        _this.onSortableDocumentWidgetChange(null, $sortable, $element);
    }
};

/**
 * Create document explorer.
 *
 * @param  {[type]} event [description]
 * @return false
 */
DocumentWidget.prototype.onExplorerToggle = function(event) {
    var _this = this;

    var $btn = $(event.currentTarget);
    var $widget = $btn.parents('.documents-widget').eq(0);

    if (_this.$explorer === null && $widget.length) {
        if (_this.toggleTimeout) {
            clearTimeout(_this.toggleTimeout);
        }
        /*
         * Dispatch event to close every other
         * uploaders
         */
        var openevent = new CustomEvent("explorer-open", {"detail":_this});
        window.dispatchEvent(openevent);

        _this.toggleTimeout = window.setTimeout(function () {
            $btn.addClass('uk-active');
            var ajaxData = {
                '_action':'toggleExplorer',
                '_token': Rozier.ajaxToken
            };
            Rozier.lazyload.canvasLoader.show();
            $.ajax({
                    url: Rozier.routes.documentsAjaxExplorer,
                    type: 'get',
                    dataType: 'json',
                    cache: false,
                    data: ajaxData
                })
                .success(function(data) {
                    Rozier.lazyload.canvasLoader.hide();
                    if (typeof data.documents != "undefined") {
                        var $currentsortable = $(event.currentTarget).parents('.documents-widget').eq(0).find('.documents-widget-sortable');
                        _this.createExplorer(data, $currentsortable);
                    }
                })
                .fail(function(data) {
                    console.log(data.responseText);
                    console.log("error");
                });
        }, 100);
    } else {
        _this.explorer.closeExplorer();
        _this.explorer = null;
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
    var $doc = $($element.parents('li')[0]);
    var $widget = $element.parents('.documents-widget-sortable').first();

    $doc.remove();
    $widget.trigger('change.uk.sortable', [$widget, $doc]);

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
                '<a href="#" class="document-widget-explorer-logo rz-no-ajax-link"><i class="uk-icon-rz-folder-tree-mini"></i></a>',
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
    _this.explorer = new DocumentExplorer(_this.$explorer, data, $originWidget, _this);
};

