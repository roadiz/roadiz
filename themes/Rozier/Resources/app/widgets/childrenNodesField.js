import $ from 'jquery'
import {
    isset
} from '../plugins'
import NodeTreeContextActions from '../components/trees/nodeTreeContextActions'

export default function ChildrenNodesField () {
    var _this = this
    _this.currentRequest = null

    _this.init()
}

ChildrenNodesField.prototype.init = function () {
    var _this = this

    _this.$fields = $('[data-children-nodes-widget]')
    _this.$nodeTrees = _this.$fields.find('.nodetree-widget')
    _this.$quickAddNodeButtons = _this.$fields.find('.children-nodes-quick-creation a')

    if (_this.$quickAddNodeButtons.length) {
        var proxiedClick = $.proxy(_this.onQuickAddClick, _this)
        _this.$quickAddNodeButtons.off('click', proxiedClick)
        _this.$quickAddNodeButtons.on('click', proxiedClick)
    }

    _this.$fields.find('.nodetree-langs').remove()
}

ChildrenNodesField.prototype.treeAvailable = function () {
    var _this = this

    var $nodeTree = _this.$fields.find('.nodetree-widget')

    if ($nodeTree.length) {
        return true
    } else {
        return false
    }
}

ChildrenNodesField.prototype.onQuickAddClick = function (event) {
    var _this = this

    if (_this.ajaxTimeout) {
        clearTimeout(_this.ajaxTimeout)
    }
    _this.ajaxTimeout = window.setTimeout(function () {
        var $link = $(event.currentTarget)
        var nodeTypeId = parseInt($link.attr('data-children-node-type'))
        var parentNodeId = parseInt($link.attr('data-children-parent-node'))
        var translationId = parseInt($link.attr('data-translation-id'))

        if (nodeTypeId > 0 && parentNodeId > 0) {
            var postData = {
                '_token': window.Rozier.ajaxToken,
                '_action': 'quickAddNode',
                'nodeTypeId': nodeTypeId,
                'parentNodeId': parentNodeId,
                'translationId': translationId
            }
            $.ajax({
                url: window.Rozier.routes.nodesQuickAddAjax,
                type: 'post',
                dataType: 'json',
                data: postData
            })
            .done(function (data) {
                window.Rozier.refreshMainNodeTree()
                var $nodeTree = $link.parents('.children-nodes-widget').find('.nodetree-widget')
                _this.refreshNodeTree($nodeTree, parentNodeId, translationId)

                window.UIkit.notify({
                    message: data.responseText,
                    status: data.status,
                    timeout: 3000,
                    pos: 'top-center'
                })
            })
            .fail(function (data) {
                console.log('error')
                console.log(data)

                data = JSON.parse(data.responseText)

                window.UIkit.notify({
                    message: data.responseText,
                    status: data.status,
                    timeout: 3000,
                    pos: 'top-center'
                })
            })
            .always(function () {
                // console.log("complete");
            })
        }
    }, 200)

    return false
}

ChildrenNodesField.prototype.refreshNodeTree = function ($nodeTree, rootNodeId, translationId) {
    var _this = this

    if ($nodeTree.length) {
        if (_this.currentRequest && _this.currentRequest.readyState !== 4) {
            _this.currentRequest.abort()
        }

        if (typeof rootNodeId === 'undefined') {
            var $rootTree = $($nodeTree.find('.root-tree')[0])
            rootNodeId = parseInt($rootTree.attr('data-parent-node-id'))
            translationId = parseInt($rootTree.attr('data-translation-id'))
        }
        window.Rozier.lazyload.canvasLoader.show()
        var postData = {
            '_token': window.Rozier.ajaxToken,
            '_action': 'requestNodeTree',
            'parentNodeId': parseInt(rootNodeId)
        }

        var url = window.Rozier.routes.nodesTreeAjax
        if (isset(translationId) && translationId > 0) {
            url += '/' + translationId
        }

        _this.currentRequest = $.ajax({
            url: url,
            type: 'get',
            dataType: 'json',
            cache: false,
            data: postData
        })
        .done(function (data) {
            if ($nodeTree.length &&
                typeof data.nodeTree !== 'undefined') {
                $nodeTree.fadeOut('slow', function () {
                    var $tempContainer = $nodeTree.parents('.children-nodes-widget')
                    $nodeTree.replaceWith(data.nodeTree)

                    $nodeTree = $tempContainer.find('.nodetree-widget')
                    window.Rozier.initNestables()
                    window.Rozier.bindMainTrees()
                    window.Rozier.lazyload.bindAjaxLink()
                    $nodeTree.fadeIn()

                    _this.init()

                    window.Rozier.lazyload.canvasLoader.hide()
                    window.Rozier.lazyload.nodeTreeContextActions = new NodeTreeContextActions()
                })
            }
        })
        .fail(function (data) {
            console.log(data.responseJSON)
        })
    } else {
        console.error('No node-tree available.')
    }
}
