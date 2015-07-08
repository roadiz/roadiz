var StackNodeTree = function () {
    var _this = this;

    _this.$page = $('.stack-tree');
    _this.$quickAddNodeButtons = _this.$page.find('.stack-tree-quick-creation a');
    _this.$switchLangButtons = _this.$page.find('.nodetree-langs a');

    _this.init();
};

StackNodeTree.prototype.init = function() {
    var _this = this;

    if (_this.$quickAddNodeButtons.length) {

        var proxiedClick = $.proxy(_this.onQuickAddClick, _this);

        _this.$quickAddNodeButtons.off("click", proxiedClick);
        _this.$quickAddNodeButtons.on("click", proxiedClick);

        if(_this.$switchLangButtons.length){
            var proxiedChangeLang = $.proxy(_this.onChangeLangClick, _this);
            _this.$switchLangButtons.off("click");
            _this.$switchLangButtons.on("click", proxiedChangeLang);
        }
    }
};
StackNodeTree.prototype.onChangeLangClick = function(event) {
    var _this = this;

    event.preventDefault();

    console.log('Changed lang on stack tree');

    var $link = $(event.currentTarget);

    var $nodeTree = _this.$page.find('.nodetree-widget');
    var parentNodeId = parseInt($link.attr('data-children-parent-node'));
    var translationId = parseInt($link.attr('data-translation-id'));
    var tagId = $link.attr('data-filter-tag');

    _this.refreshNodeTree(parentNodeId, translationId, tagId);

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

        if (isset($link.attr('data-filter-tag'))) {
            postData.tagId = parseInt($link.attr('data-filter-tag'));
        }

        $.ajax({
            url: Rozier.routes.nodesQuickAddAjax,
            type: 'post',
            dataType: 'json',
            data: postData,
        })
        .done(function(data) {
            Rozier.refreshMainNodeTree();
            _this.refreshNodeTree(parentNodeId, null, postData.tagId);

            UIkit.notify({
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

            UIkit.notify({
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

StackNodeTree.prototype.treeAvailable  = function() {
    var _this = this;

    var $nodeTree = _this.$page.find('.nodetree-widget');

    if($nodeTree.length) {
        return true;
    } else {
        return false;
    }
};

StackNodeTree.prototype.refreshNodeTree = function(rootNodeId, translationId, tagId) {
    var _this = this;
    var $nodeTree = _this.$page.find('.nodetree-widget');

    if($nodeTree.length){

        var $rootTree = $($nodeTree.find('.root-tree')[0]);

        if (typeof rootNodeId === "undefined") {
            rootNodeId = parseInt($rootTree.attr("data-parent-node-id"));
        }

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
            postData.translationId = parseInt(translationId);
        }

        //data-filter-tag
        if (isset(tagId)) {
            postData.tagId = parseInt(tagId);
        }

        $.ajax({
            url: url,
            type: 'get',
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
                    Rozier.lazyload.bindAjaxLink();
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
};
