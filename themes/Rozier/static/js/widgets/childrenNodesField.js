/**
 *
 */
var ChildrenNodesField = function () {
    var _this = this;

    _this.$fields = $('[data-children-nodes-widget]');
    _this.$quickAddNodeButtons = _this.$fields.find('.children-nodes-quick-creation a');
    //_this.$switchLangButtons = _this.$fields.find('.nodetree-langs');

    _this.init();
};
ChildrenNodesField.prototype.$fields = null;
ChildrenNodesField.prototype.$quickAddNodeButtons = null;

ChildrenNodesField.prototype.init = function() {
    var _this = this;

    if (_this.$quickAddNodeButtons.length) {

        var proxiedClick = $.proxy(_this.onQuickAddClick, _this);
        _this.$quickAddNodeButtons.off("click", proxiedClick);
        _this.$quickAddNodeButtons.on("click", proxiedClick);
    }

    _this.$fields.find('.nodetree-langs').remove();
};

ChildrenNodesField.prototype.onQuickAddClick = function(event) {
    var _this = this;
    var $link = $(event.currentTarget);

    var nodeTypeId = parseInt($link.attr('data-children-node-type'));
    var parentNodeId = parseInt($link.attr('data-children-parent-node'));
    var translationId = parseInt($link.attr('data-translation-id'));

    if(nodeTypeId > 0 &&
       parentNodeId > 0) {

        var postData = {
            "_token": Rozier.ajaxToken,
            "_action":'quickAddNode',
            "nodeTypeId":nodeTypeId,
            "parentNodeId":parentNodeId,
            "translationId":translationId
        };

        $.ajax({
            url: Rozier.routes.nodesQuickAddAjax,
            type: 'post',
            dataType: 'json',
            data: postData,
        })
        .done(function(data) {
            console.log("success");
            console.log(data);

            Rozier.refreshMainNodeTree();

            var $nodeTree = $link.parents('.children-nodes-widget').find('.nodetree-widget');
            _this.refreshNodeTree($nodeTree, parentNodeId, translationId);

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

ChildrenNodesField.prototype.refreshNodeTree = function( $nodeTree, rootNodeId, translationId ) {
    var _this = this;

    if($nodeTree.length){

        Rozier.lazyload.canvasLoader.show();
        var postData = {
            "_token": Rozier.ajaxToken,
            "_action":'requestNodeTree',
            "parentNodeId":parseInt(rootNodeId)
        };

        var url = Rozier.routes.nodesTreeAjax;
        if(isset(translationId) && translationId > 0){
            url += '/'+translationId;
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
                    var $tempContainer = $nodeTree.parents('.children-nodes-widget');

                    $nodeTree.replaceWith(data.nodeTree);
                    $nodeTree = $tempContainer.find('.nodetree-widget');

                    Rozier.initNestables();
                    Rozier.bindMainTrees();
                    Rozier.lazyload.generalBind();
                    $nodeTree.fadeIn();

                    _this.$fields.find('.nodetree-langs').remove();

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
