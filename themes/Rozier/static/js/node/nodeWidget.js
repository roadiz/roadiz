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
 * @file nodeWidget.js
 * @author Ambroise Maupate
 */
var NodeWidget = function () {
    var _this = this;

    _this.$widgets = $('[data-node-widget]');
    _this.currentRequest = null;

    if (_this.$widgets.length) {
        _this.$sortables = $('.nodes-widget-sortable');
        _this.$toggleExplorerButtons = $('[data-node-widget-toggle-explorer]');
        _this.$unlinkNodeButtons = $('[data-node-widget-unlink-node]');
        _this.$explorer = null;
        _this.explorer = null;
        _this.$explorerClose = null;
        _this.uploader = null;

        _this.init();
    }
};

NodeWidget.prototype.init = function() {
    var _this = this;

    var changeProxy = $.proxy(_this.onSortableNodeWidgetChange, _this);
    _this.$sortables.on('change.uk.sortable', changeProxy);
    _this.$sortables.on('change.uk.sortable', changeProxy);

    var onExplorerToggleP = $.proxy(_this.onExplorerToggle, _this);
    _this.$toggleExplorerButtons.off('click', onExplorerToggleP);
    _this.$toggleExplorerButtons.on('click', onExplorerToggleP);

    _this.initUnlinkEvent();
};

NodeWidget.prototype.initUnlinkEvent = function() {
    var _this = this;

    _this.$unlinkNodeButtons = $('[data-node-widget-unlink-node]');

    var onUnlinkNodeP = $.proxy(_this.onUnlinkNode, _this);
    _this.$unlinkNodeButtons.off('click', onUnlinkNodeP);
    _this.$unlinkNodeButtons.on('click', onUnlinkNodeP);
};

/**
 * Update node widget input values after being sorted.
 *
 * @param  {[type]} event   [description]
 * @param list
 * @param  {[type]} element [description]
 * @return {false}
 */
NodeWidget.prototype.onSortableNodeWidgetChange = function(event, list, element) {
    var _this = this;

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

    if (_this.explorer === null) {
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
            _this.$toggleExplorerButtons.addClass('uk-active');
            var $currentWidget = $(event.currentTarget).parents('.nodes-widget').eq(0);

            var ajaxData = {
                '_action':'toggleExplorer',
                '_token': Rozier.ajaxToken
            };
            var nodeTypes = $currentWidget.attr('data-nodetypes');
            if (nodeTypes && nodeTypes !== '') {
                ajaxData.nodeTypes = JSON.parse(nodeTypes);
            }

            _this.currentRequest = $.ajax({
                url: Rozier.routes.nodesAjaxExplorer,
                type: 'get',
                dataType: 'json',
                cache: false,
                data: ajaxData
            })
            .success(function(data) {
                if (typeof data.nodes != "undefined") {
                    var $currentsortable = $currentWidget.find('.nodes-widget-sortable');
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


NodeWidget.prototype.onUnlinkNode = function( event ) {
    var _this = this;

    var $element = $(event.currentTarget);
    var $doc = $($element.parents('li')[0]);
    var $widget = $element.parents('.nodes-widget-sortable').first();

    $doc.remove();
    $widget.trigger('change.uk.sortable', [$widget, $doc]);

    return false;
};

/**
 * Populate explorer with nodes thumbnails
 * @param  {[type]} data [description]
 * @param $originWidget
 * @return {[type]}      [description]
 */
NodeWidget.prototype.createExplorer = function(data, $originWidget) {
    var _this = this;
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
    _this.explorer = new NodeExplorer(_this.$explorer, data, $originWidget, _this);
};


