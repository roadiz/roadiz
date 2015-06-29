var FolderExplorer = function (data, documentExplorer) {
    var _this = this;

    _this.buildExplorer(data);
    _this.documentExplorer = documentExplorer;
    _this.destroyed = false;
    _this.$explorer = $('.folder-widget-explorer');
    _this.$folderLinks = _this.$explorer.find('.folder-item-link');

    _this.initialExplorerWidth = _this.documentExplorer.$explorer.outerWidth();

    TweenLite.fromTo(
        _this.$explorer,
        0.5,
        {x: _this.$explorer.outerWidth()*-1},
        {x: 0, ease:Expo.easeOut}
    );
    TweenLite.to(
        _this.documentExplorer.$explorer,
        0.5,
        {x: _this.$explorer.outerWidth(), width: _this.initialExplorerWidth - _this.$explorer.outerWidth(), ease:Expo.easeOut}
    );
    TweenLite.to(
        _this.documentExplorer.$explorerSearchForm,
        0.5,
        {opacity:0, 'pointer-events': 'none', ease:Expo.easeOut}
    );


    _this.$folderLinks.on('click', $.proxy(_this.onFolderClick, _this));
};

FolderExplorer.prototype.destroy = function() {
    var _this = this;

    _this.$folderLinks.off('click');

    TweenLite.to(
        _this.documentExplorer.$explorerSearchForm,
        0.5,
        {opacity:1, 'pointer-events': 'auto', ease:Expo.easeOut}
    );
    TweenLite.to(_this.$explorer, 0.5, {x: _this.$explorer.outerWidth()*-1, ease:Expo.easeOut, onComplete: function () {
        _this.$explorer.remove();
        _this.destroyed = true;
    }});
    TweenLite.to(
        _this.documentExplorer.$explorer,
        0.5,
        {x: 0, width:_this.initialExplorerWidth, ease:Expo.easeOut}
    );
};

FolderExplorer.prototype.onFolderClick = function(event) {
    var _this = this;

    var $link = $(event.currentTarget);
    var folderId = parseInt($link.attr('data-folder-id'));

    var ajaxData = {
        '_action':'toggleExplorer',
        '_token': Rozier.ajaxToken,
    };

    if (folderId > 0) {
        ajaxData.folderId = folderId;
    }

    Rozier.lazyload.canvasLoader.show();

    $.ajax({
        url: Rozier.routes.documentsAjaxExplorer,
        type: 'get',
        dataType: 'json',
        data: ajaxData
    })
    .success(function(data) {
        if (typeof data.documents != "undefined") {
            _this.documentExplorer.appendItemsToExplorer(data, true);
        }
    })
    .fail(function(data) {
        console.log(data.responseText);
        console.log("error");
    })
    .always(function() {
        Rozier.lazyload.canvasLoader.hide();
    });


    return false;
};


FolderExplorer.prototype.buildExplorer = function(data) {
    var _this = this;

    var explorerDom = [
        '<div class="folder-widget-explorer">',
            '<ul class="folders">',
                '<li class="folder-close">',
                    '<a href="#" class="folder-item-link" data-folder-id="0">',
                        '<i class="uk-icon-rz-reset"></i>',
                    '</a>',
                '</li>',
            _this.insertFolders(data.folders),
            '</ul>',
        '</div>'
    ].join('');

    $("body").append(explorerDom);
};

FolderExplorer.prototype.insertFolders = function(folders) {
    var _this = this;

    var explorer = '';
    var foldersLength = folders.length;

    for(var i = 0; i < foldersLength; i++) {
        explorer += [
            '<li class="folder-item">',
                '<a href="#" class="folder-item-link" data-folder-id="'+ folders[i].id +'">',
                    '<i class="uk-icon-rz-folder-tree-mini"></i><span class="text">' + folders[i].name + '</span>',
                '</a>',
                '<ul class="sub-folders">',
                    _this.insertFolders(folders[i].children),
                '</ul>',
            '</li>'
        ].join('');
    }

    return explorer;
};
