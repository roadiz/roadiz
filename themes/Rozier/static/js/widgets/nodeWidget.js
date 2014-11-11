/**
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
NodeWidget.prototype.onExplorerSearch = function(event) {
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

                var $currentsortable = $($(event.currentTarget).parents('.nodes-widget')[0]).find('.nodes-widget-sortable');
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
 * Query next page nodes explorer.
 *
 * @param  {[type]} filters [description]
 * @param  {[type]} event   [description]
 * @return false
 */
NodeWidget.prototype.onExplorerNextPage = function(filters, event) {
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

                var $currentsortable = $($(event.currentTarget).parents('.nodes-widget')[0]).find('.nodes-widget-sortable');
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
                //'<div class="node-widget-explorer-logo"><i class="uk-icon-rz-folder-tree"></i></div>',
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

    _this.$explorer.find('.explorer-search').on('submit', $.proxy(_this.onExplorerSearch, _this));
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
     * Add pagination
     */
    if (typeof data.filters.nextPage !== 'undefined' &&
        data.filters.nextPage > 1) {

        $sortable.append([
            '<li class="node-widget-explorer-nextpage">',
                '<i class="uk-icon-plus"></i><span class="label">More nodes</span>',
            '</li>'
        ].join(''));

        $sortable.find('.node-widget-explorer-nextpage').on('click', $.proxy(_this.onExplorerNextPage, _this, data.filters));
    }

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
