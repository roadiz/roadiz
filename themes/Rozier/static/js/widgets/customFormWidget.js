/**
 *
 */
var CustomFormWidget = function () {
    var _this = this;

    _this.$widgets = $('[data-custom-form-widget]');
    _this.$sortables = $('.custom-forms-widget-sortable');
    _this.$toggleExplorerButtons = $('[data-custom-form-widget-toggle-explorer]');
    _this.$toggleUploaderButtons = $('[data-custom-form-widget-toggle-uploader]');
    _this.$unlinkCustomFormButtons = $('[data-custom-form-widget-unlink-custom-form]');

    _this.$explorer = null;
    _this.$explorerClose = null;
    _this.uploader = null;

    _this.init();
};

CustomFormWidget.prototype.init = function() {
    var _this = this;

    var changeProxy = $.proxy(_this.onSortableCustomFormWidgetChange, _this);
    _this.$sortables.on('change.uk.sortable', changeProxy);
    _this.$sortables.on('change.uk.sortable', changeProxy);

    var onExplorerToggleP = $.proxy(_this.onExplorerToggle, _this);
    _this.$toggleExplorerButtons.off('click', onExplorerToggleP);
    _this.$toggleExplorerButtons.on('click', onExplorerToggleP);

    _this.initUnlinkEvent();

    Rozier.$window.on('keyup', $.proxy(_this.echapKey, _this));
};

CustomFormWidget.prototype.initUnlinkEvent = function() {
    var _this = this;

    _this.$unlinkCustomFormButtons = $('[data-custom-form-widget-unlink-custom-form]');

    var onUnlinkCustomFormP = $.proxy(_this.onUnlinkCustomForm, _this);
    _this.$unlinkCustomFormButtons.off('click', onUnlinkCustomFormP);
    _this.$unlinkCustomFormButtons.on('click', onUnlinkCustomFormP);
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

    var $doc = $($element.parents('li')[0]);
    var $widget = $element.parents('.custom-forms-widget-sortable').first();

    $doc.remove();
    $widget.trigger('change.uk.sortable', [$widget, $doc]);

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

    _this.initUnlinkEvent();

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
