import $ from 'jquery'

export default function NodeTreeContextActions () {
    var _this = this

    var $contextualMenus = $('.tree-contextualmenu')
    _this.$links = $contextualMenus.find('.node-actions a')
    _this.$nodeMoveFirstLinks = $contextualMenus.find('a.move-node-first-position')
    _this.$nodeMoveLastLinks = $contextualMenus.find('a.move-node-last-position')

    if (_this.$links.length) {
        _this.bind()
    }
};

NodeTreeContextActions.prototype.bind = function () {
    var _this = this

    var proxy = $.proxy(_this.onClick, _this)
    _this.$links.off('click', proxy)
    _this.$links.on('click', proxy)

    var moveFirstProxy = $.proxy(_this.moveNodeToPosition, _this, 'first')
    _this.$nodeMoveFirstLinks.off('click')
    _this.$nodeMoveFirstLinks.on('click', moveFirstProxy)

    var moveLastProxy = $.proxy(_this.moveNodeToPosition, _this, 'last')
    _this.$nodeMoveLastLinks.off('click')
    _this.$nodeMoveLastLinks.on('click', moveLastProxy)
}

NodeTreeContextActions.prototype.onClick = function (event) {
    var _this = this

    event.preventDefault()

    var $link = $(event.currentTarget)
    var $element = $($link.parents('.nodetree-element')[0])
    var nodeId = parseInt($element.data('node-id'))

    // console.log('Clicked on '+linkClass);

    var statusName = $link.attr('data-status')
    var statusValue = $link.attr('data-value')
    var action = $link.attr('data-action')

    if (typeof action !== 'undefined') {
        window.Rozier.lazyload.canvasLoader.show()

        if (typeof statusName !== 'undefined' &&
            typeof statusValue !== 'undefined' &&
            !isNaN(statusValue)) {
            /*
             * Change node status
             */
            _this.changeStatus(nodeId, statusName, parseInt(statusValue))
        } else {
            /*
             * Other actions
             */
            if (action === 'duplicate') {
                _this.duplicateNode(nodeId)
            }
        }
    }
}

NodeTreeContextActions.prototype.changeStatus = function (nodeId, statusName, statusValue) {
    var _this = this

    if (_this.ajaxTimeout) {
        clearTimeout(_this.ajaxTimeout)
    }
    _this.ajaxTimeout = window.setTimeout(function () {
        var postData = {
            '_token': window.Rozier.ajaxToken,
            '_action': 'nodeChangeStatus',
            'nodeId': nodeId,
            'statusName': statusName,
            'statusValue': statusValue
        }

        $.ajax({
            url: window.Rozier.routes.nodesStatusesAjax,
            type: 'post',
            dataType: 'json',
            data: postData
        })
        .done(function (data) {
            window.Rozier.refreshAllNodeTrees()
            window.UIkit.notify({
                message: data.responseText,
                status: data.status,
                timeout: 3000,
                pos: 'top-center'
            })
        })
        .fail(function (data) {
            data = JSON.parse(data.responseText)
            window.UIkit.notify({
                message: data.responseText,
                status: data.status,
                timeout: 3000,
                pos: 'top-center'
            })
        })
        .always(function () {
            window.Rozier.lazyload.canvasLoader.hide()
        })
    }, 100)
}

/**
 * Move a node to the position.
 *
 * @param nodeId
 */
NodeTreeContextActions.prototype.duplicateNode = function (nodeId) {
    var _this = this

    if (_this.ajaxTimeout) {
        clearTimeout(_this.ajaxTimeout)
    }
    _this.ajaxTimeout = window.setTimeout(function () {
        var postData = {
            _token: window.Rozier.ajaxToken,
            _action: 'duplicate',
            nodeId: nodeId
        }
        $.ajax({
            url: window.Rozier.routes.nodeAjaxEdit.replace('%nodeId%', nodeId),
            type: 'POST',
            dataType: 'json',
            data: postData
        })
        .done(function (data) {
            window.Rozier.refreshAllNodeTrees()
            window.UIkit.notify({
                message: data.responseText,
                status: data.status,
                timeout: 3000,
                pos: 'top-center'
            })
        })
        .fail(function (data) {
            console.log(data)
        })
        .always(function () {
            window.Rozier.lazyload.canvasLoader.hide()
        })
    }, 100)
}

/**
 * Move a node to the position.
 *
 * @param  Event event
 */
NodeTreeContextActions.prototype.moveNodeToPosition = function (position, event) {
    window.Rozier.lazyload.canvasLoader.show()

    var element = $($(event.currentTarget).parents('.nodetree-element')[0])
    var nodeId = parseInt(element.data('node-id'))
    var parentNodeId = parseInt(element.parents('ul').first().data('parent-node-id'))

    var postData = {
        _token: window.Rozier.ajaxToken,
        _action: 'updatePosition',
        nodeId: nodeId
    }

    /*
     * Force to first position
     */
    if (typeof position !== 'undefined' && position === 'first') {
        postData.firstPosition = true
    } else if (typeof position !== 'undefined' && position === 'last') {
        postData.lastPosition = true
    }

    /*
     * When dropping to root
     * set parentNodeId to NULL
     */
    if (isNaN(parentNodeId)) {
        parentNodeId = null
    }
    postData.newParent = parentNodeId

    $.ajax({
        url: window.Rozier.routes.nodeAjaxEdit.replace('%nodeId%', nodeId),
        type: 'POST',
        dataType: 'json',
        data: postData
    })
    .done(function (data) {
        window.Rozier.refreshAllNodeTrees()
        window.UIkit.notify({
            message: data.responseText,
            status: data.status,
            timeout: 3000,
            pos: 'top-center'
        })
    })
    .always(function () {
        window.Rozier.lazyload.canvasLoader.hide()
    })
}
