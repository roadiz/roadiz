/*
 * Copyright (c) 2017. Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * Except as contained in this notice, the name of the ROADIZ shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from Ambroise Maupate and Julien Blanchet.
 *
 * @file lazyload.js
 * @author Adrien Scholaert <adrien@rezo-zero.com>
 */

import $ from 'jquery'
import {
    TweenLite,
    Expo
} from 'gsap'
import DocumentsBulk from './components/bulk-edits/documentsBulk'
import NodesBulk from './components/bulk-edits/nodesBulk'
import TagsBulk from './components/bulk-edits/tagsBulk'
import AutoUpdate from './components/auto-update/auto-update'
import DocumentUploader from './components/documents/documentUploader'
import NodeTypeFieldsPosition from './components/node-type-fields/nodeTypeFieldsPosition'
import NodeTypeFieldEdit from './components/node-type-fields/nodeTypeFieldEdit'
import CustomFormFieldsPosition from './components/custom-form-fields/customFormFieldsPosition'
import CustomFormFieldEdit from './components/custom-form-fields/customFormFieldEdit'
import NodeTreeContextActions from './components/trees/nodeTreeContextActions'
import Import from './components/import/import'
import NodeEditSource from './components/node/nodeEditSource'
import InputLengthWatcher from './widgets/inputLengthWatcher'
import ChildrenNodesField from './widgets/childrenNodesField'
import GeotagField from './widgets/geotagField'
import MultiGeotagField from './widgets/multiGeotagField'
import StackNodeTree from './widgets/stackNodeTree'
import SaveButtons from './widgets/saveButtons'
import TagAutocomplete from './widgets/tagAutocomplete'
import FolderAutocomplete from './widgets/folderAutocomplete'
import SettingsSaveButtons from './widgets/settingsSaveButtons'
import NodeTree from './widgets/nodeTree'
import NodeStatuses from './widgets/nodeStatuses'
import YamlEditor from './widgets/yamlEditor'
import MarkdownEditor from './widgets/markdownEditor'
import JsonEditor from './widgets/jsonEditor'
import CssEditor from './widgets/cssEditor'
import {
    isMobile
} from './plugins'

/**
 * Lazyload
 */
export default function Lazyload () {
    var _this = this

    _this.$linksSelector = null
    _this.$textareasMarkdown = null
    _this.documentsList = null
    _this.mainColor = null
    _this.$canvasLoaderContainer = null
    _this.currentRequest = null

    var onStateChangeProxy = $.proxy(_this.onPopState, _this)

    _this.parseLinks()

    // this hack resolves safari triggering popstate
    // at initial load.
    window.addEventListener('load', function () {
        setTimeout(function () {
            $(window).off('popstate', onStateChangeProxy)
            $(window).on('popstate', onStateChangeProxy)
        }, 0)
    })

    _this.$canvasLoaderContainer = $('#canvasloader-container')
    _this.mainColor = window.Rozier.mainColor ? window.Rozier.mainColor : '#ffffff'
    _this.initLoader()

    /*
     * Start history with first hard loaded page
     */
    history.pushState({}, null, window.location.href)
}

/**
 * Init loader
 * @return {[type]} [description]
 */
Lazyload.prototype.initLoader = function () {
    var _this = this

    _this.canvasLoader = new window.CanvasLoader('canvasloader-container')
    _this.canvasLoader.setColor(_this.mainColor)
    _this.canvasLoader.setShape('square')
    _this.canvasLoader.setDensity(90)
    _this.canvasLoader.setRange(0.8)
    _this.canvasLoader.setSpeed(4)
    _this.canvasLoader.setFPS(30)
}

Lazyload.prototype.parseLinks = function () {
    var _this = this
    _this.$linksSelector = $("a:not('[target=_blank]')").not('.rz-no-ajax-link')
}

/**
 * Bind links to load pages
 * @param  {[type]} event [description]
 * @return {[type]}       [description]
 */
Lazyload.prototype.onClick = function (event) {
    let _this = this
    let $link = $(event.currentTarget)
    let href = $link.attr('href')

    if (typeof href !== 'undefined' &&
        !$link.hasClass('rz-no-ajax-link') &&
        href !== '' &&
        href !== '#' &&
        (href.indexOf(window.Rozier.baseUrl) >= 0 || href.charAt(0) === '/' || href.charAt(0) === '?')) {
        event.preventDefault()

        if (_this.clickTimeout) {
            clearTimeout(_this.clickTimeout)
        }
        _this.clickTimeout = window.setTimeout(function () {
            history.pushState({}, null, $link.attr('href'))
            _this.onPopState(null)
        }, 50)

        return false
    }
}

/**
 * On pop state
 * @param  {[type]} event [description]
 * @return {[type]}       [description]
 */
Lazyload.prototype.onPopState = function (event) {
    var _this = this
    var state = null

    if (event !== null) {
        state = event.originalEvent.state
    }

    if (typeof state === 'undefined' || state === null) {
        state = window.history.state
    }

    if (state !== null) {
        _this.canvasLoader.show()
        _this.loadContent(state, window.location)
    }
}

/**
 * Load content (ajax)
 * @param  {[type]} state    [description]
 * @param  {[type]} location [description]
 * @return {[type]}          [description]
 */
Lazyload.prototype.loadContent = function (state, location) {
    var _this = this

    /*
     * Delay loading if user is click like devil
     */
    if (_this.currentTimeout) {
        clearTimeout(_this.currentTimeout)
    }

    _this.currentTimeout = window.setTimeout(function () {
        /*
         * Trigger event on window to notify open
         * widgets to close.
         */
        var pageChangeEvent = new CustomEvent('pagechange')
        window.dispatchEvent(pageChangeEvent)

        _this.currentRequest = $.ajax({
            url: location.href,
            type: 'get',
            dataType: 'html',
            cache: false,
            data: state.headerData
        })
            .done(function (data) {
                _this.applyContent(data)
                _this.canvasLoader.hide()
                var pageLoadEvent = new CustomEvent('pageload', { 'detail': data })
                window.dispatchEvent(pageLoadEvent)
            })
            .fail(function (data) {
                console.log(data)
                if (typeof data.responseText !== 'undefined') {
                    try {
                        var exception = JSON.parse(data.responseText)
                        window.UIkit.notify({
                            message: exception.message,
                            status: 'danger',
                            timeout: 3000,
                            pos: 'top-center'
                        })
                    } catch (e) {
                        // No valid JsonResponse, need to refresh page
                        window.location.href = location.href
                    }
                } else {
                    window.UIkit.notify({
                        message: window.Rozier.messages.forbiddenPage,
                        status: 'danger',
                        timeout: 3000,
                        pos: 'top-center'
                    })
                }

                _this.canvasLoader.hide()
            })
    }, 100)
}

/**
 * Apply content to main content
 * @param  {[type]} data [description]
 * @return {[type]}      [description]
 */
Lazyload.prototype.applyContent = function (data) {
    var _this = this

    var $container = $('#main-content-scrollable')
    var $old = $container.find('.content-global')

    var $tempData = $(data)
    $tempData.addClass('new-content-global')
    $container.append($tempData)
    $tempData = $container.find('.new-content-global')

    $old.fadeOut(100, function () {
        $old.remove()

        _this.generalBind()
        $tempData.fadeIn(200, function () {
            $tempData.removeClass('new-content-global')
        })
    })
}

Lazyload.prototype.bindAjaxLink = function () {
    var _this = this
    _this.parseLinks()

    var onClickProxy = $.proxy(_this.onClick, _this)
    _this.$linksSelector.off('click', onClickProxy)
    _this.$linksSelector.on('click', onClickProxy)
}

/**
 * General bind on page load
 * @return {[type]} [description]
 */
Lazyload.prototype.generalBind = function () {
    var _this = this

    _this.bindAjaxLink()

    /* eslint-disable no-new */
    new DocumentsBulk()
    new AutoUpdate()
    new NodesBulk()
    new TagsBulk()
    new InputLengthWatcher()
    new DocumentUploader(window.Rozier.messages.dropzone)
    _this.childrenNodesFields = new ChildrenNodesField()
    new GeotagField()
    new MultiGeotagField()
    _this.stackNodeTrees = new StackNodeTree()
    if (isMobile.any() === null) new SaveButtons()
    new TagAutocomplete()
    new FolderAutocomplete()
    new NodeTypeFieldsPosition()
    new CustomFormFieldsPosition()
    _this.nodeTreeContextActions = new NodeTreeContextActions()

    // _this.documentsList = new DocumentsList();
    _this.settingsSaveButtons = new SettingsSaveButtons()
    _this.nodeTypeFieldEdit = new NodeTypeFieldEdit()
    _this.nodeEditSource = new NodeEditSource()
    _this.nodeTree = new NodeTree()
    _this.customFormFieldEdit = new CustomFormFieldEdit()

    /*
     * Codemirror
     */
    _this.initMarkdownEditors()
    _this.initJsonEditors()
    _this.initCssEditors()
    _this.initYamlEditors()

    _this.initFilterBars()

    const $colorPickerInput = $('.colorpicker-input')

    // Init colorpicker
    if ($colorPickerInput.length) {
        $colorPickerInput.minicolors()
    }

    // Animate actions menu
    if ($('.actions-menu').length && isMobile.any() === null) {
        TweenLite.to('.actions-menu', 0.5, {right: 0, delay: 0.4, ease: Expo.easeOut})
    }

    window.Rozier.initNestables()
    window.Rozier.bindMainTrees()
    window.Rozier.nodeStatuses = new NodeStatuses()

    // Switch checkboxes
    _this.initBootstrapSwitches()

    window.Rozier.getMessages()

    if (typeof window.Rozier.importRoutes !== 'undefined' &&
        window.Rozier.importRoutes !== null) {
        window.Rozier.import = new Import(window.Rozier.importRoutes)
        window.Rozier.importRoutes = null
    }
}

Lazyload.prototype.initBootstrapSwitches = function () {
    var $checkboxes = $('.rz-boolean-checkbox')

    // Switch checkboxes
    $checkboxes.bootstrapSwitch({
        size: 'small'
    })
}

Lazyload.prototype.initMarkdownEditors = function () {
    var _this = this

    // Init markdown-preview
    _this.$textareasMarkdown = $('textarea[data-rz-markdowneditor]')
    var editorCount = _this.$textareasMarkdown.length

    if (editorCount) {
        setTimeout(function () {
            for (var i = 0; i < editorCount; i++) {
                new MarkdownEditor(_this.$textareasMarkdown.eq(i), i)
            }
        }, 100)
    }
}

Lazyload.prototype.initJsonEditors = function () {
    var _this = this

    // Init markdown-preview
    _this.$textareasJson = $('textarea[data-rz-jsoneditor]')
    var editorCount = _this.$textareasJson.length

    if (editorCount) {
        setTimeout(function () {
            for (var i = 0; i < editorCount; i++) {
                new JsonEditor(_this.$textareasJson.eq(i), i)
            }
        }, 100)
    }
}

Lazyload.prototype.initCssEditors = function () {
    var _this = this

    // Init markdown-preview
    _this.$textareasCss = $('textarea[data-rz-csseditor]')
    var editorCount = _this.$textareasCss.length

    if (editorCount) {
        setTimeout(function () {
            for (var i = 0; i < editorCount; i++) {
                new CssEditor(_this.$textareasCss.eq(i), i)
            }
        }, 100)
    }
}

Lazyload.prototype.initYamlEditors = function () {
    var _this = this

    // Init markdown-preview
    _this.$textareasYaml = $('textarea[data-rz-yamleditor]')
    var editorCount = _this.$textareasYaml.length

    if (editorCount) {
        setTimeout(function () {
            for (var i = 0; i < editorCount; i++) {
                new YamlEditor(_this.$textareasYaml.eq(i), i)
            }
        }, 100)
    }
}

Lazyload.prototype.initFilterBars = function () {
    const $selectItemPerPage = $('select.item-per-page')

    if ($selectItemPerPage.length) {
        $selectItemPerPage.off('change')
        $selectItemPerPage.on('change', function (event) {
            $(event.currentTarget).parents('form').submit()
        })
    }
}

/**
 * Resize
 * @return {[type]} [description]
 */
Lazyload.prototype.resize = function () {
    var _this = this

    _this.$canvasLoaderContainer[0].style.left = window.Rozier.mainContentScrollableOffsetLeft + (window.Rozier.mainContentScrollableWidth / 2) + 'px'
}
