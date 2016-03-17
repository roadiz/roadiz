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
 * @file documentExplorer.js
 * @author Ambroise Maupate
 */
var DocumentExplorer = function ($explorer, data, $originWidget, documentWidget) {
    var _this = this;

    _this.$explorer = $explorer;
    _this.$originWidget = $originWidget;
    _this.data = data;
    _this.documentWidget = documentWidget;
    _this.$explorerFolderToggle = _this.$explorer.find('.document-widget-explorer-logo');
    _this.$explorerClose = _this.$explorer.find('.document-widget-explorer-close');
    _this.$explorerSearchForm = _this.$explorer.find('.explorer-search');
    _this.appendItemsToExplorer(data);
    _this.folderExplorer = null;

    _this.init();

    TweenLite.fromTo(
        _this.$explorer,
        0.5,
        {x: _this.$explorer.outerWidth()*-1},
        {x: 0, ease:Expo.easeOut}
    );
};

DocumentExplorer.prototype.init = function() {
    var _this = this;

    Rozier.$window.on('keyup', $.proxy(_this.echapKey, _this));
    _this.$explorerFolderToggle.off('click');
    _this.$explorerFolderToggle.on('click', $.proxy(_this.toggleFolders, _this));
    _this.$explorerClose.on('click', $.proxy(_this.closeExplorer, _this));
    _this.$explorerSearchForm.on('submit', $.proxy(_this.onExplorerSearch, _this));
};

DocumentExplorer.prototype.destroy = function() {
    var _this = this;

    Rozier.$window.off('keyup', $.proxy(_this.echapKey, _this));
    _this.$explorerFolderToggle.off('click');
    _this.$explorerClose.off('click');
    _this.$explorerSearchForm.off('submit');
};

DocumentExplorer.prototype.toggleFolders = function(event) {
    var _this = this;

    if (_this.folderExplorer === null ||
        _this.folderExplorer.destroyed === true) {

        Rozier.lazyload.canvasLoader.show();

        var ajaxData = {
            '_action':'toggleExplorer',
            '_token': Rozier.ajaxToken
        };

        $.ajax({
            url: Rozier.routes.foldersAjaxExplorer,
            type: 'GET',
            dataType: 'json',
            cache: false,
            data: ajaxData
        })
        .done(function(data) {
            console.log(data);
            _this.folderExplorer = new FolderExplorer(data, _this);
        })
        .fail(function() {
            console.log("error");
        })
        .always(function() {
            Rozier.lazyload.canvasLoader.hide();
        });
    } else {
        _this.folderExplorer.destroy();
        _this.folderExplorer = null;
    }

    return false;
};

/**
 * Append documents to explorer.
 *
 * @param  Ajax data data
 * @param  jQuery $originWidget
 * @param  boolean replace Replace instead of appending
 */
DocumentExplorer.prototype.appendItemsToExplorer = function(data, replace) {
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
    var docLength = data.documents.length;
    for (var i = 0; i < docLength; i++) {
        var doc = data.documents[i];
        $sortable.append(doc.html);
    }

    /*
     * Bind add buttons.
     */
    var onAddClick = $.proxy(_this.onAddDocumentClick, _this);
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

        $sortable.find('.document-widget-explorer-nextpage').on('click', $.proxy(_this.onExplorerNextPage, _this, data.filters));
    }
};


/**
 * Add document click
 * @param  {[type]} event         [description]
 */
DocumentExplorer.prototype.onAddDocumentClick = function(event) {
    var _this = this;

    var $object = $(event.currentTarget).parents('.uk-sortable-list-item');
    $object.appendTo(_this.$originWidget);

    var inputName = 'source['+_this.$originWidget.data('input-name')+']';
    _this.$originWidget.find('li').each(function (index, element) {
        $(element).find('input').attr('name', inputName+'['+index+']');
    });

    _this.documentWidget.initUnlinkEvent();

    return false;
};


/**
 * Echap key to close explorer
 * @return {[type]} [description]
 */
DocumentExplorer.prototype.echapKey = function(e){
    var _this = this;

    if(e.keyCode == 27) {
        _this.closeExplorer();
    }

    return false;
};


/**
 * Close explorer
 * @return {[type]} [description]
 */
DocumentExplorer.prototype.closeExplorer = function(){
    var _this = this;

    if (_this.folderExplorer !== null &&
        _this.folderExplorer.destroyed === false) {
        _this.folderExplorer.destroy();
    }

    _this.documentWidget.$toggleExplorerButtons.removeClass('uk-active');
    TweenLite.to(_this.$explorer, 0.5, {x: _this.$explorer.outerWidth()*-1, opacity:0, ease:Expo.easeOut, onComplete: function () {
        _this.destroyed = true;
        _this.destroy();
        _this.$explorer.remove();
        _this.documentWidget.$explorer = null;
        _this.$explorer = null;
    }});
};


/**
 * Query searched documents explorer.
 *
 * @param  {[type]} event   [description]
 * @return false
 */
DocumentExplorer.prototype.onExplorerSearch = function(event) {
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
            cache: false,
            data: ajaxData
        })
        .success(function(data) {
            //console.log(data);
            //console.log("success");
            Rozier.lazyload.canvasLoader.hide();
            if (typeof data.documents != "undefined") {
                _this.appendItemsToExplorer(data, true);
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
DocumentExplorer.prototype.onExplorerNextPage = function(filters, event) {
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
            cache: false,
            data: ajaxData
        })
        .success(function(data) {
            console.log(data);
            console.log("success");
            Rozier.lazyload.canvasLoader.hide();

            if (typeof data.documents != "undefined") {
                _this.appendItemsToExplorer(data);
            }
        })
        .fail(function(data) {
            console.log(data.responseText);
            console.log("error");
        });
    }

    return false;
};

