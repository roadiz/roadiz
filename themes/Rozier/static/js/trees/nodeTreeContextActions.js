var NodeTreeContextActions = function () {
    var _this = this;

    var $contextualMenus = $('.tree-contextualmenu');
    _this.$links = $contextualMenus.find('.node-actions a');
    _this.$nodeMoveFirstLinks = $contextualMenus.find('a.move-node-first-position');
    _this.$nodeMoveLastLinks = $contextualMenus.find('a.move-node-last-position');

    if(_this.$links.length){
        _this.bind();
    }
};

NodeTreeContextActions.prototype.bind = function() {
    var _this = this;

    var proxy = $.proxy(_this.onClick, _this);
    _this.$links.off('click', proxy);
    _this.$links.on('click', proxy);

    var moveFirstProxy = $.proxy(_this.moveNodeToPosition, _this, "first");
    _this.$nodeMoveFirstLinks.off('click');
    _this.$nodeMoveFirstLinks.on('click', moveFirstProxy);

    var moveLastProxy = $.proxy(_this.moveNodeToPosition, _this, "last");
    _this.$nodeMoveLastLinks.off('click');
    _this.$nodeMoveLastLinks.on('click', moveLastProxy);
};

NodeTreeContextActions.prototype.onClick = function(event) {
    var _this = this;

    event.preventDefault();

    var $link = $(event.currentTarget);
    var $element = $($link.parents('.nodetree-element')[0]);
    var node_id = parseInt($element.data('node-id'));

    var linkClass = $link.attr('data-action');

    console.log('Clicked on '+linkClass);

    var statusName= $link.attr('data-status');
    var statusValue = $link.attr('data-value');
    var action = $link.attr('data-action');

    if(typeof action !== "undefined") {

        Rozier.lazyload.canvasLoader.show();

        if(typeof statusName !== "undefined" &&
            typeof statusValue !== "undefined") {
            /*
             * Change node status
             */
            _this.changeStatus(node_id, statusName, parseInt(statusValue));
        } else {
            /*
             * Other actions
             */
            if(action == "duplicate"){
                _this.duplicateNode(node_id);
            }
        }
    }
};

NodeTreeContextActions.prototype.changeStatus = function(node_id, statusName, statusValue) {
    var _this = this;

    var postData = {
        "_token": Rozier.ajaxToken,
        "_action":'nodeChangeStatus',
        "nodeId":node_id,
        "statusName": statusName,
        "statusValue": statusValue
    };
    console.log(postData);

    $.ajax({
        url: Rozier.routes.nodesStatusesAjax,
        type: 'post',
        dataType: 'json',
        data: postData
    })
    .done(function(data) {
        console.log(data);
        Rozier.refreshAllNodeTrees();
        UIkit.notify({
            message : data.responseText,
            status  : data.status,
            timeout : 3000,
            pos     : 'top-center'
        });
    })
    .fail(function(data) {
        console.log(data.responseJSON);

        data = JSON.parse(data.responseText);

        UIkit.notify({
            message : data.responseText,
            status  : data.status,
            timeout : 3000,
            pos     : 'top-center'
        });
    })
    .always(function() {
        Rozier.lazyload.canvasLoader.hide();
    });
};

/**
 * Move a node to the position.
 *
 * @param  Event event
 */
NodeTreeContextActions.prototype.duplicateNode = function(node_id) {
    var _this = this;

    var postData = {
        _token: Rozier.ajaxToken,
        _action: 'duplicate',
        nodeId: node_id
    };

    $.ajax({
        url: Rozier.routes.nodeAjaxEdit.replace("%nodeId%", node_id),
        type: 'POST',
        dataType: 'json',
        data: postData
    })
    .done(function( data ) {
        console.log(data);

        Rozier.refreshAllNodeTrees();

        UIkit.notify({
            message : data.responseText,
            status  : data.status,
            timeout : 3000,
            pos     : 'top-center'
        });

    })
    .fail(function( data ) {
        console.log(data);
    })
    .always(function() {
        Rozier.lazyload.canvasLoader.hide();
    });
};

/**
 * Move a node to the position.
 *
 * @param  Event event
 */
NodeTreeContextActions.prototype.moveNodeToPosition = function (position, event) {
    var _this = this;

    Rozier.lazyload.canvasLoader.show();

    var element = $($(event.currentTarget).parents('.nodetree-element')[0]);
    var node_id = parseInt(element.data('node-id'));
    var parent_node_id = parseInt(element.parents('ul').first().data('parent-node-id'));

    var postData = {
        _token: Rozier.ajaxToken,
        _action: 'updatePosition',
        nodeId: node_id
    };

    /*
     * Force to first position
     */
    if (typeof position !== "undefined" && position == "first") {
        postData.firstPosition = true;
    } else if (typeof position !== "undefined" && position == "last") {
        postData.lastPosition = true;
    }

    /*
     * When dropping to root
     * set parentNodeId to NULL
     */
    if(isNaN(parent_node_id)){
        parent_node_id = null;
    }
    postData.newParent = parent_node_id;

    console.log(postData);
    $.ajax({
        url: Rozier.routes.nodeAjaxEdit.replace("%nodeId%", node_id),
        type: 'POST',
        dataType: 'json',
        data: postData
    })
    .done(function( data ) {
        console.log(data);

        Rozier.refreshAllNodeTrees();

        UIkit.notify({
            message : data.responseText,
            status  : data.status,
            timeout : 3000,
            pos     : 'top-center'
        });

    })
    .fail(function( data ) {
        console.log(data);
    })
    .always(function() {
        Rozier.lazyload.canvasLoader.hide();
    });
};