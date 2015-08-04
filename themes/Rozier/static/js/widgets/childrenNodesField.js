/**
 *
 */
var ChildrenNodesField = function () {
    var _this = this;

    _this.$fields = $('[data-children-nodes-widget]');
    _this.$nodeTrees = _this.$fields.find('.nodetree-widget');
    _this.$quickAddNodeButtons = _this.$fields.find('.children-nodes-quick-creation a');

    _this.init();
    _this.dropDownize();
};

ChildrenNodesField.prototype.init = function() {
    var _this = this;

    if (_this.$quickAddNodeButtons.length) {

        var proxiedClick = $.proxy(_this.onQuickAddClick, _this);
        _this.$quickAddNodeButtons.off("click", proxiedClick);
        _this.$quickAddNodeButtons.on("click", proxiedClick);
    }

    _this.$fields.find('.nodetree-langs').remove();
};

ChildrenNodesField.prototype.treeAvailable  = function() {
    var _this = this;

    var $nodeTree = _this.$fields.find('.nodetree-widget');

    if($nodeTree.length) {
        return true;
    } else {
        return false;
    }
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
            //console.log("success");
            //console.log(data);

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
            //console.log("complete");
        });
    }

    return false;
};

ChildrenNodesField.prototype.dropDownize = function() {
    var _this = this;

    for (var i = _this.$fields.length - 1; i >= 0; i--) {
        var $quickAddNode = $(_this.$fields[i]).find('.children-nodes-quick-creation');

        if(!$quickAddNode.hasClass('uk-dropdown') &&
            $quickAddNode.find('a').length > 2){
            console.log("Need to convert buttons to dropdown");

            $quickAddNode.addClass('uk-dropdown uk-dropdown-navbar uk-dropdown-flip');
            $quickAddNode.removeClass('uk-button-group');
            $quickAddNode.wrap('<div data-uk-dropdown="{mode:\'click\'}"></div>');
            $quickAddNode.before('<a class="uk-button"><i class="uk-icon-rz-plus-simple"></i></a>');

            $($quickAddNode.parents('.uk-navbar-content')[0]).removeClass('uk-navbar-content');
        }
    }
};

ChildrenNodesField.prototype.refreshNodeTree = function($nodeTree, rootNodeId, translationId) {
    var _this = this;

    if($nodeTree.length){

        if (typeof rootNodeId === "undefined") {
            var $rootTree = $($nodeTree.find('.root-tree')[0]);
            rootNodeId = parseInt($rootTree.attr("data-parent-node-id"));
        }
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
                typeof data.nodeTree != "undefined") {

                $nodeTree.fadeOut('slow', function() {
                    var $tempContainer = $nodeTree.parents('.children-nodes-widget');
                    $nodeTree.replaceWith(data.nodeTree);
                    $nodeTree = $tempContainer.find('.nodetree-widget');
                    Rozier.initNestables();
                    Rozier.bindMainTrees();
                    Rozier.lazyload.bindAjaxLink();
                    $nodeTree.fadeIn();
                    _this.$fields.find('.nodetree-langs').remove();
                    Rozier.lazyload.canvasLoader.hide();
                    Rozier.lazyload.nodeTreeContextActions = new NodeTreeContextActions();
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
