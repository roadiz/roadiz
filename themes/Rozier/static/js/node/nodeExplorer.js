/**
 * Copyright © 2015, Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * Except as contained in this notice, the name of the ROADIZ shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from Ambroise Maupate and Julien Blanchet.
 *
 * @file nodeExplorer.js
 * @author Ambroise Maupate
 */
var NodeExplorer = function ($explorer, data, $originWidget, nodeWidget) {
    var _this = this;

    _this.nodeWidget = nodeWidget;
    _this.$explorer = $explorer;
    _this.data = data;
    _this.$originWidget = $originWidget;
    _this.$parentWidget = $($originWidget.parents('.nodes-widget')[0]);
    _this.isDestroyed = false;

    _this.init();
};

NodeExplorer.prototype.init = function() {
    var _this = this;

    _this.$explorerClose = _this.$explorer.find('.node-widget-explorer-close');

    _this.$explorerClose.on('click', $.proxy(_this.closeExplorer, _this));
    _this.$explorer.find('.explorer-search').on('submit', $.proxy(_this.onExplorerSearch, _this));
    _this.appendItemsToExplorer(_this.data);

    Rozier.$window.on('keyup', $.proxy(_this.echapKey, _this));

    window.setTimeout(function () {
        _this.$explorer.addClass('visible');
    }, 0);
};


/**
 * Query searched nodes explorer.
 *
 * @param  {[type]} event   [description]
 * @return false
 */
NodeExplorer.prototype.onExplorerSearch = function(event) {
    var _this = this;

    if (_this.$explorer !== null){
        var $search = $(event.currentTarget).find('#nodes-search-input');

        var ajaxData = {
            '_action':'toggleExplorer',
            '_token': Rozier.ajaxToken,
            'search': $search.val(),
            'nodeTypes': JSON.parse(_this.$parentWidget.attr('data-nodetypes'))
        };

        $.ajax({
            url: Rozier.routes.nodesAjaxExplorer,
            type: 'get',
            dataType: 'json',
            cache: false,
            data: ajaxData
        })
        .success(function(data) {
            if (typeof data.nodes != "undefined") {
                _this.appendItemsToExplorer(data, true);
            }
        })
        .fail(function(data) {
            console.log(data.responseText);
            console.log("error");
            UIkit.notify({
                message : "Error while searching.",
                status  : 'danger',
                timeout : 2000,
                pos     : 'top-center'
            });
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
NodeExplorer.prototype.onExplorerNextPage = function(filters, event) {
    var _this = this;

    console.log(_this.$originWidget);
    if (_this.$explorer !== null){
        console.log(filters);
        var ajaxData = {
            '_action':'toggleExplorer',
            '_token': Rozier.ajaxToken,
            'search': filters.search,
            'page': filters.nextPage,
            'nodeTypes': JSON.parse(_this.$parentWidget.attr('data-nodetypes'))
        };

        $.ajax({
            url: Rozier.routes.nodesAjaxExplorer,
            type: 'get',
            dataType: 'json',
            cache: false,
            data: ajaxData
        })
        .success(function(data) {
            if (typeof data.nodes != "undefined") {
                _this.appendItemsToExplorer(data);
            }
        })
        .fail(function(data) {
            console.log(data.responseText);
            console.log("error");
            UIkit.notify({
                message : "Error while loading next page.",
                status  : 'danger',
                timeout : 2000,
                pos     : 'top-center'
            });
        });
    }

    return false;
};

/**
 * Append nodes to explorer.
 *
 * @param  Ajax data data
 * @param  boolean replace Replace instead of appending
 */
NodeExplorer.prototype.appendItemsToExplorer = function(data, replace) {
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
    var onAddClick = $.proxy(_this.onAddNodeClick, _this);
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

        $sortable.find('.node-widget-explorer-nextpage').on('click', $.proxy(_this.onExplorerNextPage, _this, data.filters));
    }
};


NodeExplorer.prototype.onAddNodeClick = function(event) {
    var _this = this;

    var $object = $(event.currentTarget).parents('.uk-sortable-list-item');
    $object.appendTo(_this.$originWidget);

    var inputName = 'source['+_this.$originWidget.data('input-name')+']';
    _this.$originWidget.find('li').each(function (index, element) {
        $(element).find('input').attr('name', inputName+'['+index+']');
    });

    _this.nodeWidget.initUnlinkEvent();

    return false;
};

/**
 * Echap key to close explorer
 * @return {[type]} [description]
 */
NodeExplorer.prototype.echapKey = function(e){
    var _this = this;

    if(e.keyCode == 27 && _this.$explorer !== null) _this.closeExplorer();

    return false;
};

/**
 * Close explorer
 * @return {[type]} [description]
 */
NodeExplorer.prototype.closeExplorer = function(){
    var _this = this;

    _this.nodeWidget.$toggleExplorerButtons.removeClass('uk-active');
    _this.$explorer.removeClass('visible');
    _this.$explorer.one('transitionend webkitTransitionEnd mozTransitionEnd msTransitionEnd', function(event) {
        /* Act on the event */
        _this.$explorer.remove();
        _this.$explorer = null;
        Rozier.$window.off('keyup', $.proxy(_this.echapKey, _this));
        _this.isDestroyed = true;
    });
};
