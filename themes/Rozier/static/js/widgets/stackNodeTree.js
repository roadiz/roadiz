var StackNodeTree = function () {
    var _this = this;

    _this.$page = $('.stack-tree');
    _this.$quickAddNodeButtons = _this.$page.find('.stack-tree-quick-creation a');

    _this.init();
};

StackNodeTree.prototype.$page = null;
StackNodeTree.prototype.$quickAddNodeButtons = null;

StackNodeTree.prototype.init = function() {
    var _this = this;

    if (_this.$quickAddNodeButtons.length) {

        var proxiedClick = $.proxy(_this.onQuickAddClick, _this);

        _this.$quickAddNodeButtons.off("click", proxiedClick);
        _this.$quickAddNodeButtons.on("click", proxiedClick);
    }
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
            _this.refreshNodeTree($link, parentNodeId);

            $.UIkit.notify({
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

            $.UIkit.notify({
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

StackNodeTree.prototype.refreshNodeTree = function( $link, rootNodeId ) {
    var _this = this;
    var $nodeTree = _this.$page.find('.nodetree-widget');

    if($nodeTree.length){
        var postData = {
            "_token":       Rozier.ajaxToken,
            "_action":      'requestNodeTree',
            "stackTree":    true,
            "parentNodeId": parseInt(rootNodeId)
        };

        $.ajax({
            url: Rozier.routes.nodesTreeAjax,
            type: 'post',
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
                    $nodeTree.fadeIn();
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