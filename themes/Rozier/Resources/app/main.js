import './scss/styles.scss'
import './less/vendor.less'
import './less/style.less'

// Include bower dependencies
import '../../bower_components/CanvasLoader/js/heartcode-canvasloader'
import '../../bower_components/jquery.actual/jquery.actual'
import '../../bower_components/jquery-tag-editor/jquery.tag-editor'
import '../../bower_components/bootstrap-switch/dist/js/bootstrap-switch'
import '../../bower_components/mousetrap/mousetrap'
import '../../bower_components/caret/jquery.caret.js'
import '../../bower_components/jquery-minicolors/jquery.minicolors.js'

import UIkit from '../../bower_components/uikit/js/uikit'
import '../../bower_components/uikit/js/components/nestable'
import '../../bower_components/uikit/js/components/sortable.js'
import '../../bower_components/uikit/js/components/datepicker.js'
import '../../bower_components/uikit/js/components/pagination.js'
import '../../bower_components/uikit/js/components/notify.js'
import '../../bower_components/uikit/js/components/tooltip.js'

import CodeMirror from 'codemirror'
import 'codemirror/mode/markdown/markdown.js'
import 'codemirror/mode/javascript/javascript.js'
import 'codemirror/mode/css/css.js'
import 'codemirror/addon/mode/overlay.js'
import 'codemirror/mode/xml/xml.js'
import 'codemirror/mode/yaml/yaml.js'
import 'codemirror/mode/gfm/gfm.js'

import 'jquery-ui'
import 'jquery-ui/ui/widgets/autocomplete'
import './components/login/login'

import Lazyload from './lazyload'
import EntriesPanel from './components/panels/EntriesPanel'
import NodeTreeContextActions from './components/trees/NodeTreeContextActions'
import RozierMobile from './rozierMobile'
import VueApp from './App'
import $ from 'jquery'
import {
    TweenLite,
    Expo
} from 'gsap'
import {
    PointerEventsPolyfill,
    isMobile
} from './plugins'
import GeotagField from './widgets/GeotagField'
import MultiGeotagField from './widgets/MultiGeotagField'

window.CodeMirror = CodeMirror
window.UIkit = UIkit

// eslint-disable-next-line
window.initializeGeotagFields = () => {
    window.Rozier.gMapLoaded = true
    window.Rozier.gMapLoading = false

    /* eslint-disable no-new */
    new GeotagField()
    new MultiGeotagField()
}

/*
 * ============================================================================
 * Rozier entry point
 * ============================================================================
 */

const Rozier = {}

window.Rozier = Rozier

Rozier.$window = null
Rozier.$body = null

Rozier.windowWidth = null
Rozier.windowHeight = null
Rozier.resizeFirst = true
Rozier.gMapLoading = false
Rozier.gMapLoaded = false

Rozier.searchNodesSourcesDelay = null
Rozier.nodeTrees = []
Rozier.treeTrees = []

Rozier.$userPanelContainer = null
Rozier.$minifyTreePanelButton = null
Rozier.$mainTrees = null
Rozier.$mainTreesContainer = null
Rozier.$mainTreeElementName = null
Rozier.$treeContextualButton = null
Rozier.$nodesSourcesSearch = null
Rozier.nodesSourcesSearchHeight = null
Rozier.$nodeTreeHead = null
Rozier.nodeTreeHeadHeight = null
Rozier.$treeScrollCont = null
Rozier.$treeScroll = null
Rozier.treeScrollHeight = null

Rozier.$mainContentScrollable = null
Rozier.mainContentScrollableWidth = null
Rozier.mainContentScrollableOffsetLeft = null
Rozier.$backTopBtn = null

Rozier.entriesPanel = null

Rozier.onDocumentReady = function () {
    /*
     * Store Rozier configuration
     */
    for (let index in window.temp) {
        Rozier[index] = window.temp[index]
    }

    Rozier.lazyload = new Lazyload()
    Rozier.entriesPanel = new EntriesPanel()
    Rozier.vueApp = new VueApp()

    Rozier.$window = $(window)
    Rozier.$body = $('body')

    // --- Selectors --- //
    Rozier.$userPanelContainer = $('#user-panel-container')
    Rozier.$minifyTreePanelButton = $('#minify-tree-panel-button')
    Rozier.$mainTrees = $('#main-trees')
    Rozier.$mainTreesContainer = $('#main-trees-container')
    Rozier.$nodesSourcesSearch = $('#nodes-sources-search')
    Rozier.$mainContentScrollable = $('#main-content-scrollable')
    Rozier.$backTopBtn = $('#back-top-button')

    // Pointer events polyfill
    if (!window.Modernizr.testProp('pointerEvents')) {
        PointerEventsPolyfill.initialize({'selector': '#main-trees-overlay'})
    }

    // Minify trees panel toggle button
    Rozier.$minifyTreePanelButton.on('click', Rozier.toggleTreesPanel)

    // Rozier.$body.on('markdownPreviewOpen', '.markdown-editor-preview', Rozier.toggleTreesPanel);
    document.body.addEventListener('markdownPreviewOpen', Rozier.openTreesPanel, false)

    // Back top btn
    Rozier.$backTopBtn.on('click', $.proxy(Rozier.backTopBtnClick, Rozier))

    Rozier.$window.on('resize', $.proxy(Rozier.resize, Rozier))
    Rozier.$window.trigger('resize')

    Rozier.lazyload.generalBind()
    Rozier.bindMainNodeTreeLangs()
}

/**
 * init nestable for ajax
 * @return {[type]} [description]
 */
Rozier.initNestables = function () {
    $('.uk-nestable').each(function (index, element) {
        let $tree = $(element)
        /*
         * make drag&drop only available on handle
         * very important for Touch based device which need to
         * scroll on trees.
         */
        let options = {
            handleClass: 'uk-nestable-handle'
        }

        if ($tree.hasClass('nodetree')) {
            options.group = 'nodeTree'
        } else if ($tree.hasClass('tagtree')) {
            options.group = 'tagTree'
        } else if ($tree.hasClass('foldertree')) {
            options.group = 'folderTree'
        }

        window.UIkit.nestable(element, options)
    })
}

/**
 * Bind main trees
 * @return {[type]} [description]
 */
Rozier.bindMainTrees = function () {
    let _this = this

    // TREES
    let $nodeTree = $('.nodetree-widget .root-tree')
    $nodeTree.off('change.uk.nestable')
    $nodeTree.on('change.uk.nestable', Rozier.onNestableNodeTreeChange)

    let $tagTree = $('.tagtree-widget .root-tree')
    $tagTree.off('change.uk.nestable')
    $tagTree.on('change.uk.nestable', Rozier.onNestableTagTreeChange)

    let $folderTree = $('.foldertree-widget .root-tree')
    $folderTree.off('change.uk.nestable')
    $folderTree.on('change.uk.nestable', Rozier.onNestableFolderTreeChange)

    // Tree element name
    _this.$mainTreeElementName = _this.$mainTrees.find('.tree-element-name')
    _this.$mainTreeElementName.off('contextmenu', $.proxy(_this.maintreeElementNameRightClick, _this))
    _this.$mainTreeElementName.on('contextmenu', $.proxy(_this.maintreeElementNameRightClick, _this))
}

/**
 * Main tree element name right click.
 *
 * @return {[type]}
 */
Rozier.maintreeElementNameRightClick = function (e) {
    let $contextualMenu = $(e.currentTarget).parent().find('.tree-contextualmenu')
    if ($contextualMenu.length) {
        if ($contextualMenu[0].className.indexOf('uk-open') === -1) {
            $contextualMenu.addClass('uk-open')
        } else $contextualMenu.removeClass('uk-open')
    }

    return false
}

/**
 * Bind main node tree langs.
 *
 * @return {[type]}
 */
Rozier.bindMainNodeTreeLangs = function () {
    $('body').on('click', '#tree-container .nodetree-langs a', function (event) {
        Rozier.lazyload.canvasLoader.show()
        let $link = $(event.currentTarget)
        let translationId = parseInt($link.attr('data-translation-id'))

        Rozier.refreshMainNodeTree(translationId)
        return false
    })
}

/**
 * Get messages.
 *
 * @return {[type]} [description]
 */
Rozier.getMessages = function () {
    $.ajax({
        url: Rozier.routes.ajaxSessionMessages,
        type: 'GET',
        dataType: 'json',
        cache: false,
        data: {
            '_action': 'messages',
            '_token': Rozier.ajaxToken
        }
    })
        .done(function (data) {
            if (typeof data.messages !== 'undefined') {
                if (typeof data.messages.confirm !== 'undefined' &&
                    data.messages.confirm.length > 0) {
                    for (let i = data.messages.confirm.length - 1; i >= 0; i--) {
                        window.UIkit.notify({
                            message: data.messages.confirm[i],
                            status: 'success',
                            timeout: 2000,
                            pos: 'top-center'
                        })
                    }
                }

                if (typeof data.messages.error !== 'undefined' &&
                    data.messages.error.length > 0) {
                    for (let j = data.messages.error.length - 1; j >= 0; j--) {
                        window.UIkit.notify({
                            message: data.messages.error[j],
                            status: 'error',
                            timeout: 2000,
                            pos: 'top-center'
                        })
                    }
                }
            }
        })
        .fail(function () {
            console.log('[Rozier.getMessages] error')
        })
}

/**
 *
 * @param translationId
 */
Rozier.refreshAllNodeTrees = function (translationId) {
    let _this = this

    _this.refreshMainNodeTree(translationId)

    /*
     * Stack trees
     */
    if (_this.lazyload.stackNodeTrees.treeAvailable()) {
        _this.lazyload.stackNodeTrees.refreshNodeTree()
    }

    /*
     * Children node fields widgets;
     */
    if (_this.lazyload.childrenNodesFields.treeAvailable()) {
        for (let i = _this.lazyload.childrenNodesFields.$nodeTrees.length - 1; i >= 0; i--) {
            let $nodeTree = _this.lazyload.childrenNodesFields.$nodeTrees.eq(i)
            _this.lazyload.childrenNodesFields.refreshNodeTree($nodeTree)
        }
    }
}

/**
 * Refresh only main nodeTree.
 *
 * @param translationId
 */
Rozier.refreshMainNodeTree = function (translationId) {
    let _this = this

    let $currentNodeTree = $('#tree-container').find('.nodetree-widget')
    let $currentRootTree = $currentNodeTree.find('.root-tree').eq(0)

    if ($currentNodeTree.length) {
        let postData = {
            '_token': Rozier.ajaxToken,
            '_action': 'requestMainNodeTree'
        }

        if ($currentRootTree.length && !translationId) {
            translationId = parseInt($currentRootTree.attr('data-translation-id'))
        }

        let url = Rozier.routes.nodesTreeAjax
        if (translationId && translationId > 0) {
            url += '/' + translationId
        }

        $.ajax({
            url: url,
            type: 'get',
            cache: false,
            dataType: 'json',
            data: postData
        })
            .done(function (data) {
                if ($currentNodeTree.length &&
                    typeof data.nodeTree !== 'undefined') {
                    $currentNodeTree.fadeOut('slow', function () {
                        $currentNodeTree.replaceWith(data.nodeTree)
                        $currentNodeTree = $('#tree-container').find('.nodetree-widget')
                        $currentNodeTree.fadeIn()
                        Rozier.initNestables()
                        Rozier.bindMainTrees()
                        Rozier.resize()
                        Rozier.lazyload.bindAjaxLink()
                        _this.lazyload.nodeTreeContextActions = new NodeTreeContextActions()
                    })
                }
            })
            .fail(function (data) {
                console.log(data.responseJSON)
            })
            .always(function () {
                Rozier.lazyload.canvasLoader.hide()
            })
    } else {
        console.error('No main node-tree available.')
    }
}

/**
 * Toggle trees panel
 * @param  {[type]} event [description]
 * @return {[type]}       [description]
 */
Rozier.toggleTreesPanel = function () {
    $('#main-trees').toggleClass('minified')
    $('#main-content').toggleClass('maximized')
    $('#minify-tree-panel-button').find('i').toggleClass('uk-icon-rz-panel-tree-open')
    $('#minify-tree-panel-area').toggleClass('tree-panel-hidden')

    return false
}

Rozier.openTreesPanel = function (event) {
    if ($('#main-trees').hasClass('minified')) {
        Rozier.toggleTreesPanel(null)
    }

    return false
}

/**
 * Toggle user panel
 * @param  {[type]} event [description]
 * @return {[type]}       [description]
 */
Rozier.toggleUserPanel = function (event) {
    $('#user-panel').toggleClass('minified')

    return false
}

/**
 * Handle ajax search node source.
 * @param event
 */
Rozier.onSearchNodesSources = function (event) {
    let $input = $(event.currentTarget)

    if (event.keyCode === 27) {
        $input.blur()
    }

    if ($input.val().length > 1) {
        clearTimeout(Rozier.searchNodesSourcesDelay)
        Rozier.searchNodesSourcesDelay = setTimeout(function () {
            let postData = {
                _token: Rozier.ajaxToken,
                _action: 'searchNodesSources',
                searchTerms: $input.val()
            }
            console.log(postData)
            $.ajax({
                url: Rozier.routes.searchNodesSourcesAjax,
                type: 'GET',
                dataType: 'json',
                data: postData
            })
                .done(function (data) {
                    let $results = $('#nodes-sources-search-results')
                    if (typeof data.data !== 'undefined' &&
                        data.data.length > 0) {
                        $results.empty()
                        for (let i in data.data) {
                            $results.append('<li><a href="' + data.data[i].url +
                                '" style="border-left-color:' + data.data[i].typeColor + '"><span class="title">' + data.data[i].title +
                                '</span> <span class="type">' + data.data[i].typeName +
                                '</span></a></li>')
                        }
                    } else {
                        $results.empty()
                    }
                })
                .fail(function (data) {
                    let $results = $('#nodes-sources-search-results')
                    $results.empty()
                })
        }, 200)
    }
}

/**
 * On submit search nodes sources
 * @return {[type]} [description]
 */
Rozier.onSubmitSearchNodesSources = function (e) {
    return false
}

/**
 *
 * @param event
 * @param rootEl
 * @param el
 * @param status
 * @returns {boolean}
 */
Rozier.onNestableNodeTreeChange = function (event, rootEl, el, status) {
    let element = $(el)
    /*
     * If node removed, do not do anything, the other change.uk.nestable nodeTree will be triggered
     */
    if (status === 'removed') {
        return false
    }
    let nodeId = parseInt(element.attr('data-node-id'))
    let parentNodeId = null
    if (element.parents('.nodetree-element').length) {
        parentNodeId = parseInt(element.parents('.nodetree-element').eq(0).attr('data-node-id'))
    } else if (element.parents('.stack-tree-widget').length) {
        parentNodeId = parseInt(element.parents('.stack-tree-widget').eq(0).attr('data-parent-node-id'))
    } else if (element.parents('.children-node-widget').length) {
        parentNodeId = parseInt(element.parents('.children-node-widget').eq(0).attr('data-parent-node-id'))
    }

    /*
     * When dropping to route
     * set parentNodeId to NULL
     */
    if (isNaN(parentNodeId)) {
        parentNodeId = null
    }

    /*
     * User dragged node inside itself
     * It will destroy the Internet !
     */
    if (nodeId === parentNodeId) {
        console.log('You cannot move a node inside itself!')
        window.location.reload()
        return false
    }

    let postData = {
        _token: Rozier.ajaxToken,
        _action: 'updatePosition',
        nodeId: nodeId,
        newParent: parentNodeId
    }

    /*
     * Get node siblings id to compute new position
     */
    if (element.next().length && typeof element.next().attr('data-node-id') !== 'undefined') {
        postData.nextNodeId = parseInt(element.next().attr('data-node-id'))
    } else if (element.prev().length && typeof element.prev().attr('data-node-id') !== 'undefined') {
        postData.prevNodeId = parseInt(element.prev().attr('data-node-id'))
    }

    console.log(postData)

    $.ajax({
        url: Rozier.routes.nodeAjaxEdit.replace('%nodeId%', nodeId),
        type: 'POST',
        dataType: 'json',
        data: postData
    })
        .done(function (data) {
            window.UIkit.notify({
                message: data.responseText,
                status: data.status,
                timeout: 3000,
                pos: 'top-center'
            })
        })
        .fail(function (data) {
            console.err(data)
        })
}

/**
 *
 * @param event
 * @param rootEl
 * @param el
 * @param status
 * @returns {boolean}
 */
Rozier.onNestableTagTreeChange = function (event, rootEl, el, status) {
    let element = $(el)
    /*
     * If tag removed, do not do anything, the other tagTree will be triggered
     */
    if (status === 'removed') {
        return false
    }

    let tagId = parseInt(element.attr('data-tag-id'))
    let parentTagId = null
    if (element.parents('.tagtree-element').length) {
        parentTagId = parseInt(element.parents('.tagtree-element').eq(0).attr('data-tag-id'))
    } else if (element.parents('.root-tree').length) {
        parentTagId = parseInt(element.parents('.root-tree').eq(0).attr('data-parent-tag-id'))
    }
    /*
     * When dropping to route
     * set parentTagId to NULL
     */
    if (isNaN(parentTagId)) {
        parentTagId = null
    }

    /*
     * User dragged tag inside itself
     * It will destroy the Internet !
     */
    if (tagId === parentTagId) {
        console.log('You cannot move a tag inside itself!')
        alert('You cannot move a tag inside itself!')
        window.location.reload()
        return false
    }

    let postData = {
        _token: Rozier.ajaxToken,
        _action: 'updatePosition',
        tagId: tagId,
        newParent: parentTagId
    }

    /*
     * Get tag siblings id to compute new position
     */
    if (element.next().length && typeof element.next().attr('data-tag-id') !== 'undefined') {
        postData.nextTagId = parseInt(element.next().attr('data-tag-id'))
    } else if (element.prev().length && typeof element.prev().attr('data-tag-id') !== 'undefined') {
        postData.prevTagId = parseInt(element.prev().attr('data-tag-id'))
    }

    console.log(postData)

    $.ajax({
        url: Rozier.routes.tagAjaxEdit.replace('%tagId%', tagId),
        type: 'POST',
        dataType: 'json',
        data: postData
    })
        .done(function (data) {
            window.UIkit.notify({
                message: data.responseText,
                status: data.status,
                timeout: 3000,
                pos: 'top-center'
            })
        })
        .fail(function (data) {
            console.err(data)
        })
}

/**
 *
 * @param event
 * @param element
 * @param status
 * @returns {boolean}
 */
Rozier.onNestableFolderTreeChange = function (event, rootEl, el, status) {
    let element = $(el)
    /*
     * If folder removed, do not do anything, the other folderTree will be triggered
     */
    if (status === 'removed') {
        return false
    }

    let folderId = parseInt(element.attr('data-folder-id'))
    let parentFolderId = null

    if (element.parents('.foldertree-element').length) {
        parentFolderId = parseInt(element.parents('.foldertree-element').eq(0).attr('data-folder-id'))
    } else if (element.parents('.root-tree').length) {
        parentFolderId = parseInt(element.parents('.root-tree').eq(0).attr('data-parent-folder-id'))
    }

    /*
     * When dropping to route
     * set parentFolderId to NULL
     */
    if (isNaN(parentFolderId)) {
        parentFolderId = null
    }

    /*
     * User dragged folder inside itself
     * It will destroy the Internet !
     */
    if (folderId === parentFolderId) {
        console.log('You cannot move a folder inside itself!')
        alert('You cannot move a folder inside itself!')
        window.location.reload()
        return false
    }

    let postData = {
        _token: Rozier.ajaxToken,
        _action: 'updatePosition',
        folderId: folderId,
        newParent: parentFolderId
    }

    /*
     * Get folder siblings id to compute new position
     */
    if (element.next().length && typeof element.next().attr('data-folder-id') !== 'undefined') {
        postData.nextFolderId = parseInt(element.next().attr('data-folder-id'))
    } else if (element.prev().length && typeof element.prev().attr('data-folder-id') !== 'undefined') {
        postData.prevFolderId = parseInt(element.prev().attr('data-folder-id'))
    }

    $.ajax({
        url: Rozier.routes.folderAjaxEdit.replace('%folderId%', folderId),
        type: 'POST',
        dataType: 'json',
        data: postData
    })
        .done(function (data) {
            window.UIkit.notify({
                message: data.responseText,
                status: data.status,
                timeout: 3000,
                pos: 'top-center'
            })
        })
        .fail(function (data) {
            console.err(data)
        })
}

/**
 * Back top click
 * @return {boolean} [description]
 */
Rozier.backTopBtnClick = function () {
    let _this = this

    TweenLite.to(_this.$mainContentScrollable, 0.6, {scrollTo: {y: 0}, ease: Expo.easeOut})

    return false
}

/**
 * Resize
 * @return {[type]} [description]
 */
Rozier.resize = function () {
    let _this = this

    _this.windowWidth = _this.$window.width()
    _this.windowHeight = _this.$window.height()

    // Close tree panel if small screen & first resize
    if (_this.windowWidth >= 768 &&
        _this.windowWidth <= 1200 &&
        _this.resizeFirst) {
        _this.$mainTrees[0].style.display = 'none'
        _this.$minifyTreePanelButton.trigger('click')
        setTimeout(function () {
            _this.$mainTrees[0].style.display = 'table-cell'
        }, 1000)
    }

    // Check if mobile
    if (_this.windowWidth <= 768 && _this.resizeFirst) _this.mobile = new RozierMobile() // && isMobile.any() !== null

    if (_this.windowWidth >= 768) {
        _this.$mainContentScrollable.height(_this.windowHeight)
        _this.$mainTreesContainer[0].style.height = ''
    } else {
        _this.$mainContentScrollable[0].style.height = ''
        _this.$mainTreesContainer.height(_this.windowHeight)
    }

    // Tree scroll height
    _this.$nodeTreeHead = _this.$mainTrees.find('.nodetree-head')
    _this.$treeScrollCont = _this.$mainTrees.find('.tree-scroll-cont')
    _this.$treeScroll = _this.$mainTrees.find('.tree-scroll')

    /*
     * need actual to get tree height even when they are hidden.
     */
    _this.nodesSourcesSearchHeight = _this.$nodesSourcesSearch.actual('outerHeight')
    _this.nodeTreeHeadHeight = _this.$nodeTreeHead.actual('outerHeight')
    _this.treeScrollHeight = _this.windowHeight - (_this.nodesSourcesSearchHeight + _this.nodeTreeHeadHeight)

    if (isMobile.any() !== null) _this.treeScrollHeight = _this.windowHeight - (50 + 50 + _this.nodeTreeHeadHeight) // Menu + tree menu + tree head

    for (let i = 0; i < _this.$treeScrollCont.length; i++) {
        _this.$treeScrollCont[i].style.height = _this.treeScrollHeight + 'px'
    }

    // Main content
    _this.mainContentScrollableWidth = _this.$mainContentScrollable.width()
    _this.mainContentScrollableOffsetLeft = _this.windowWidth - _this.mainContentScrollableWidth

    _this.lazyload.resize()
    _this.entriesPanel.replaceSubNavs()

    // Documents list
    // if(_this.lazyload !== null && !_this.resizeFirst) _this.lazyload.documentsList.resize();

    // Set resize first to false
    if (_this.resizeFirst) _this.resizeFirst = false
}

/*
 * ============================================================================
 * Plug into jQuery standard events
 * ============================================================================
 */
$(document).ready(Rozier.onDocumentReady)
