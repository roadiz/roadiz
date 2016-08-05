/**
 * DOCUMENT WIDGET
 */
var DocumentWidget = function () {
    var _this = this;

    _this.$widgets = $('[data-document-widget]');

    if (_this.$widgets.length) {
        _this.$sortables = $('.documents-widget-sortable');
        _this.$toggleExplorerButtons = $('[data-document-widget-toggle-explorer]');
        _this.$toggleUploaderButtons = $('[data-document-widget-toggle-uploader]');
        _this.$explorer = null;
        _this.explorer = null;
        _this.$explorerClose = null;
        _this.uploader = null;

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
};

DocumentWidget.prototype.initUnlinkEvent = function() {
    var _this = this;

    _this.$unlinkDocumentButtons = $('[data-document-widget-unlink-document]');

    var onUnlinkDocumentP = $.proxy(_this.onUnlinkDocument, _this);
    _this.$unlinkDocumentButtons.off('click', onUnlinkDocumentP);
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

    //console.log("Document: "+element.data('document-id'));
    //console.log(element);
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

    var $btn = $(event.currentTarget);
    var $widget = $btn.parents('.documents-widget').eq(0);
    var $uploader = $widget.find('.documents-widget-uploader').eq(0);


    if (null !== _this.uploader &&
        $uploader.length) {
        $uploader.remove();
        _this.uploader = null;
        $btn.removeClass('active uk-active');
    } else {
        $widget.append('<div class="documents-widget-uploader dropzone"></div>');
        var $uploaderNew = $widget.find('.documents-widget-uploader').eq(0);
        if ($uploaderNew.length) {
            var options = {
                selector: '.documents-widget .documents-widget-uploader',
                headers: { "_token": Rozier.ajaxToken },
                onSuccess : function (data) {
                    if(typeof data.thumbnail !== "undefined") {
                        var $sortable = $widget.find('.documents-widget-sortable');
                        $sortable.append(data.thumbnail.html);
                        var $element = $sortable.find('[data-document-id="'+data.thumbnail.id+'"]');

                        _this.onSortableDocumentWidgetChange(null, $sortable, $element);
                    }
                }
            };

            $.extend(options, Rozier.messages.dropzone);
            //console.log(options);
            _this.uploader = new DocumentUploader(options);

            $uploaderNew.slideDown(500);
            $btn.addClass('active uk-active');
        }
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

    var $btn = $(event.currentTarget);
    var $widget = $btn.parents('.documents-widget').eq(0);

    if (_this.$explorer === null && $widget.length) {
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
    }
    else _this.explorer.closeExplorer();

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

