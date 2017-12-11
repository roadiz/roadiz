import $ from 'jquery'
import NodesBulk from '../bulk-edits/nodesBulk'
import NodeTreeContextActions from '../trees/nodeTreeContextActions'
import {
    isset
} from '../plugins'

export default function StackNodeTree () {
    var _this = this
    _this.$page = $('.stack-tree').eq(0)
    _this.currentRequest = null
    _this.$quickAddNodeButtons = _this.$page.find('.stack-tree-quick-creation a')
    _this.$switchLangButtons = _this.$page.find('.nodetree-langs a')
    _this.$nodeTree = _this.$page.find('.root-tree').eq(0)

    _this.init()
}

/**
 * @return {Number}
 */
StackNodeTree.prototype.getCurrentPage = function () {
    var _this = this

    _this.$nodeTree = _this.$page.find('.root-tree').eq(0)
    var currentPage = parseInt(_this.$nodeTree.attr('data-page'))
    if (isNaN(currentPage)) {
        return 1
    }

    return currentPage
}

/**
 * @return {Number|null}
 */
StackNodeTree.prototype.getTranslationId = function () {
    var _this = this

    _this.$nodeTree = _this.$page.find('.root-tree').eq(0)
    var currentTranslationId = parseInt(_this.$nodeTree.attr('data-translation-id'))
    if (isNaN(currentTranslationId)) {
        return null
    }

    return currentTranslationId
}

StackNodeTree.prototype.init = function () {
    var _this = this

    if (_this.$quickAddNodeButtons.length) {
        var proxiedClick = $.proxy(_this.onQuickAddClick, _this)
        _this.$quickAddNodeButtons.off('click', proxiedClick)
        _this.$quickAddNodeButtons.on('click', proxiedClick)
    }
    if (_this.$switchLangButtons.length) {
        var proxiedChangeLang = $.proxy(_this.onChangeLangClick, _this)
        _this.$switchLangButtons.off('click', proxiedChangeLang)
        _this.$switchLangButtons.on('click', proxiedChangeLang)
    }
}

StackNodeTree.prototype.onChangeLangClick = function (event) {
    var _this = this
    event.preventDefault()

    var $link = $(event.currentTarget)
    var parentNodeId = parseInt($link.attr('data-children-parent-node'))
    var translationId = parseInt($link.attr('data-translation-id'))
    var tagId = $link.attr('data-filter-tag')
    _this.refreshNodeTree(parentNodeId, translationId, tagId)
    return false
}

StackNodeTree.prototype.onQuickAddClick = function (event) {
    var _this = this

    if (_this.currentRequest && _this.currentRequest.readyState !== 4) {
        _this.currentRequest.abort()
    }

    var $link = $(event.currentTarget)
    var nodeTypeId = parseInt($link.attr('data-children-node-type'))
    var parentNodeId = parseInt($link.attr('data-children-parent-node'))

    if (nodeTypeId > 0 && parentNodeId > 0) {
        var postData = {
            '_token': window.Rozier.ajaxToken,
            '_action': 'quickAddNode',
            'nodeTypeId': nodeTypeId,
            'parentNodeId': parentNodeId,
            'pushTop': 1
        }
        if ($link.attr('data-filter-tag')) {
            postData.tagId = parseInt($link.attr('data-filter-tag'))
        }
        _this.currentRequest = $.ajax({
            url: window.Rozier.routes.nodesQuickAddAjax,
            type: 'post',
            dataType: 'json',
            data: postData
        })
        .done(function (data) {
            window.Rozier.refreshMainNodeTree()
            _this.refreshNodeTree(parentNodeId, null, postData.tagId, 1)
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
            console.log('complete')
        })
    }

    return false
}

StackNodeTree.prototype.treeAvailable = function () {
    var _this = this
    var $nodeTree = _this.$page.find('.nodetree-widget')
    if ($nodeTree.length) {
        return true
    } else {
        return false
    }
}

/**
 *
 * @param rootNodeId
 * @param translationId
 * @param tagId
 * @param page
 */
StackNodeTree.prototype.refreshNodeTree = function (rootNodeId, translationId, tagId, page) {
    var _this = this

    if (_this.currentRequest && _this.currentRequest.readyState !== 4) {
        _this.currentRequest.abort()
    }

    var $nodeTree = _this.$page.find('.nodetree-widget')

    if ($nodeTree.length) {
        var $rootTree = $nodeTree.find('.root-tree').eq(0)

        if (typeof rootNodeId === 'undefined') {
            if (!$rootTree.attr('data-parent-node-id')) {
                rootNodeId = null
            } else {
                rootNodeId = parseInt($rootTree.attr('data-parent-node-id'))
            }
        } else {
            rootNodeId = parseInt(rootNodeId)
        }

        window.Rozier.lazyload.canvasLoader.show()
        var postData = {
            '_token': window.Rozier.ajaxToken,
            '_action': 'requestNodeTree',
            'stackTree': true,
            'parentNodeId': rootNodeId,
            'page': _this.getCurrentPage(),
            'translationId': _this.getTranslationId()
        }

        var url = window.Rozier.routes.nodesTreeAjax
        if (translationId && translationId > 0) {
            postData.translationId = parseInt(translationId)
        }

        /*
         * Add translation id route param manually
         */
        if (isset(postData.translationId) && postData.translationId !== null) {
            url += '/' + postData.translationId
        }

        if (isset(page)) {
            postData.page = parseInt(page)
        }

        // data-filter-tag
        if (isset(tagId)) {
            postData.tagId = parseInt(tagId)
        }

        console.log('refresh stackNodeTree', postData)

        _this.currentRequest = $.ajax({
            url: url,
            type: 'get',
            cache: false,
            dataType: 'json',
            data: postData
        })
        .done(function (data) {
            if ($nodeTree.length && typeof data.nodeTree !== 'undefined') {
                $nodeTree.fadeOut('slow', function () {
                    $nodeTree.replaceWith(data.nodeTree)
                    $nodeTree = _this.$page.find('.nodetree-widget')

                    window.Rozier.initNestables()
                    window.Rozier.bindMainTrees()
                    window.Rozier.lazyload.bindAjaxLink()
                    $nodeTree.fadeIn()
                    window.Rozier.resize()

                    /* eslint-disable no-new */
                    new NodesBulk()

                    _this.$switchLangButtons = _this.$page.find('.nodetree-langs a')
                    _this.$nodeTree = _this.$page.find('.root-tree').eq(0)

                    if (_this.$switchLangButtons.length) {
                        var proxiedChangeLang = $.proxy(_this.onChangeLangClick, _this)
                        _this.$switchLangButtons.off('click', proxiedChangeLang)
                        _this.$switchLangButtons.on('click', proxiedChangeLang)
                    }

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
