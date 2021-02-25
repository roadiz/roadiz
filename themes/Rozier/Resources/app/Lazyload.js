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
import { Expo, TweenLite } from 'gsap'
import DocumentsBulk from './components/bulk-edits/DocumentsBulk'
import NodesBulk from './components/bulk-edits/NodesBulk'
import TagsBulk from './components/bulk-edits/TagsBulk'
import DocumentUploader from './components/documents/DocumentUploader'
import NodeTypeFieldsPosition from './components/node-type-fields/NodeTypeFieldsPosition'
import AttributeValuePosition from './components/attribute-values/AttributeValuePosition'
import NodeTypeFieldEdit from './components/node-type-fields/NodeTypeFieldEdit'
import CustomFormFieldsPosition from './components/custom-form-fields/CustomFormFieldsPosition'
import NodeTreeContextActions from './components/trees/NodeTreeContextActions'
import Import from './components/import/Import'
import NodeEditSource from './components/node/NodeEditSource'
import InputLengthWatcher from './widgets/InputLengthWatcher'
import ChildrenNodesField from './widgets/ChildrenNodesField'
import StackNodeTree from './widgets/StackNodeTree'
import SaveButtons from './widgets/SaveButtons'
import TagAutocomplete from './widgets/TagAutocomplete'
import FolderAutocomplete from './widgets/FolderAutocomplete'
import SettingsSaveButtons from './widgets/SettingsSaveButtons'
import NodeTree from './widgets/NodeTree'
import NodeStatuses from './widgets/NodeStatuses'
import YamlEditor from './widgets/YamlEditor'
import MarkdownEditor from './widgets/MarkdownEditor'
import JsonEditor from './widgets/JsonEditor'
import CssEditor from './widgets/CssEditor'
import LeafletGeotagField from './widgets/LeafletGeotagField'
import MultiLeafletGeotagField from './widgets/MultiLeafletGeotagField'
import TagEdit from './components/tag/TagEdit'
import MainTreeTabs from './components/tabs/MainTreeTabs'

/**
 * Lazyload
 */
export default class Lazyload {
    constructor () {
        this.$linksSelector = null
        this.$canvasLoaderContainer = null
        this.documentsList = null
        this.mainColor = null
        this.currentRequest = null
        this.nodeTreeContextActions = null
        this.documentsBulk = null
        this.tagsBulk = null
        this.inputLengthWatcher = null
        this.documentUploader = null
        this.childrenNodesFields = null
        this.geotagField = null
        this.multiGeotagField = null
        this.saveButtons = null
        this.tagAutocomplete = null
        this.folderAutocomplete = null
        this.nodeTypeFieldsPosition = null
        this.attributeValuesPosition = null
        this.customFormFieldsPosition = null
        this.settingsSaveButtons = null
        this.nodeTypeFieldEdit = null
        this.nodeEditSource = null
        this.tagEdit = null
        this.markdownEditors = []
        this.jsonEditors = []
        this.cssEditors = []
        this.yamlEditors = []
        this.$window = $(window)

        // Binded methods
        this.onPopState = this.onPopState.bind(this)
        this.onClick = this.onClick.bind(this)

        this.parseLinks()

        window.removeEventListener('popstate', this.onPopState)
        window.addEventListener('popstate', this.onPopState)

        this.$canvasLoaderContainer = $('#canvasloader-container')
        this.mainColor = window.Rozier.mainColor ? window.Rozier.mainColor : '#00deab'
        this.initLoader()

        /*
         * Start history with first hard loaded page
         */
        window.history.pushState({}, document.title, window.location.href)
    }

    /**
     * Init loader
     */
    initLoader () {
        this.canvasLoader = new window.CanvasLoader('canvasloader-container')
        this.canvasLoader.setColor(this.mainColor)
        this.canvasLoader.setShape('square')
        this.canvasLoader.setDensity(90)
        this.canvasLoader.setRange(0.8)
        this.canvasLoader.setSpeed(4)
        this.canvasLoader.setFPS(30)
    }

    parseLinks () {
        this.$linksSelector = $("a:not('[target=_blank]')").not('.rz-no-ajax-link').not('[download]').not('[href="#"]')
    }

    /**
     * Bind links to load pages
     * @param {Event} event
     */
    onClick (event) {
        let $link = $(event.currentTarget)
        let href = $link.attr('href')

        if (typeof href !== 'undefined' &&
            !$link.hasClass('rz-no-ajax-link') &&
            href !== '' &&
            href !== '#' &&
            (href.indexOf(window.Rozier.baseUrl) >= 0 || href.charAt(0) === '/' || href.charAt(0) === '?')) {
            event.preventDefault()

            if (this.clickTimeout) {
                clearTimeout(this.clickTimeout)
            }

            this.clickTimeout = window.setTimeout(() => {
                window.history.pushState({}, null, $link.attr('href'))
                this.onPopState(null)
            }, 0)

            return false
        }
    }

    /**
     * On pop state
     * @param {Event} event
     */
    onPopState (event) {
        let state = null

        if (event !== null && event.originalEvent) {
            state = event.originalEvent.state
        }

        if (typeof state === 'undefined' || state === null) {
            state = window.history.state
        }

        if (state !== null) {
            this.canvasLoader.show()
            this.loadContent(state, window.location)
        }
    }

    /**
     * Load content (ajax)
     * @param {Object} state
     * @param {Object} location
     */
    loadContent (state, location) {
        /*
         * Delay loading if user is click like devil
         */
        if (this.currentTimeout) {
            clearTimeout(this.currentTimeout)
        }

        this.currentTimeout = window.setTimeout(() => {
            /*
             * Trigger event on window to notify open
             * widgets to close.
             */
            let pageChangeEvent = new CustomEvent('pagechange')
            window.dispatchEvent(pageChangeEvent)

            this.currentRequest = $.ajax({
                url: location.href,
                type: 'get',
                dataType: 'html',
                cache: false,
                data: state.headerData,
                beforeSend: (xhr) => {
                    xhr.setRequestHeader('X-Partial', true)
                }
            })
            .done(data => {
                this.applyContent(data)
                this.canvasLoader.hide()
                let pageLoadEvent = new CustomEvent('pageload', { 'detail': data })
                window.dispatchEvent(pageLoadEvent)
            })
            .fail(data => {
                if (typeof data.responseText !== 'undefined') {
                    try {
                        let exception = JSON.parse(data.responseText)
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

                this.canvasLoader.hide()
            })
        }, 100)
    }

    refreshCodemirrorEditor () {
        for (let editor of this.markdownEditors) {
            editor.forceEditorUpdate()
        }
        for (let editor of this.yamlEditors) {
            editor.forceEditorUpdate()
        }
        for (let editor of this.cssEditors) {
            editor.forceEditorUpdate()
        }
        for (let editor of this.jsonEditors) {
            editor.forceEditorUpdate()
        }
    }

    /**
     * Apply content to main content
     * @param {[type]} data [description]
     * @return {[type]}      [description]
     */
    applyContent (data) {
        let $container = $('#main-content-scrollable')
        let $old = $container.find('.content-global')

        let $tempData = $(data)
        /*
         * If AJAX request data is an entire HTML page.
         */
        const $ajaxRoot = $tempData.find('[data-ajax-root]')
        if ($ajaxRoot.length) {
            $tempData = $($ajaxRoot.html())
        }

        $tempData.addClass('new-content-global')
        $container.append($tempData)
        $tempData = $container.find('.new-content-global')

        $old.fadeOut(100, () => {
            $old.remove()
            this.generalBind()
            $tempData.fadeIn(200, () => {
                $tempData.removeClass('new-content-global')
                let pageShowEndEvent = new CustomEvent('pageshowend')
                window.dispatchEvent(pageShowEndEvent)
            })
        })
    }

    bindAjaxLink () {
        this.parseLinks()
        this.$linksSelector.off('click', this.onClick)
        this.$linksSelector.on('click', this.onClick)
    }

    /**
     * General bind on page load
     * @return {[type]} [description]
     */
    generalBind () {
        this.generalUnbind([
            this.documentsBulk,
            this.mainTreeTabs,
            this.nodesBulk,
            this.tagsBulk,
            this.inputLengthWatcher,
            this.documentUploader,
            this.childrenNodesFields,
            this.geotagField,
            this.multiGeotagField,
            this.stackNodeTrees,
            this.nodeTreeContextActions,
            this.tagAutocomplete,
            this.folderAutocomplete,
            this.nodeTypeFieldsPosition,
            this.attributeValuesPosition,
            this.customFormFieldsPosition,
            this.settingsSaveButtons,
            this.nodeTypeFieldEdit,
            this.nodeEditSource,
            this.tagEdit,
            this.nodeTree
        ])
        this.bindAjaxLink()
        this.markdownEditors = []
        this.jsonEditors = []
        this.cssEditors = []
        this.yamlEditors = []

        this.documentsBulk = new DocumentsBulk()
        this.mainTreeTabs = new MainTreeTabs()
        this.nodesBulk = new NodesBulk()
        this.tagsBulk = new TagsBulk()
        this.inputLengthWatcher = new InputLengthWatcher()
        this.documentUploader = new DocumentUploader(window.Rozier.messages.dropzone)
        this.childrenNodesFields = new ChildrenNodesField()
        this.geotagField = new LeafletGeotagField()
        this.multiGeotagField = new MultiLeafletGeotagField()

        this.stackNodeTrees = new StackNodeTree()

        if (this.saveButtons) {
            this.saveButtons.unbind()
        }
        this.saveButtons = new SaveButtons()

        this.tagAutocomplete = new TagAutocomplete()
        this.folderAutocomplete = new FolderAutocomplete()
        this.nodeTypeFieldsPosition = new NodeTypeFieldsPosition()
        this.attributeValuesPosition = new AttributeValuePosition()
        this.customFormFieldsPosition = new CustomFormFieldsPosition()
        this.nodeTreeContextActions = new NodeTreeContextActions()
        this.settingsSaveButtons = new SettingsSaveButtons()
        this.nodeTypeFieldEdit = new NodeTypeFieldEdit()
        this.nodeEditSource = new NodeEditSource()
        this.tagEdit = new TagEdit()
        this.nodeTree = new NodeTree()

        // Codemirror
        this.initMarkdownEditors()
        this.initJsonEditors()
        this.initCssEditors()
        this.initYamlEditors()
        this.initFilterBars()
        this.initColorPickers()
        this.initCollectionsForms()

        // Animate actions menu
        if ($('.actions-menu').length) {
            TweenLite.to('.actions-menu', 0.5, {right: 0, delay: 0.4, ease: Expo.easeOut})
        }

        window.Rozier.initNestables()
        window.Rozier.bindMainTrees()
        window.Rozier.nodeStatuses = new NodeStatuses()

        // Switch checkboxes
        this.initBootstrapSwitches()

        window.Rozier.getMessages()

        if (typeof window.Rozier.importRoutes !== 'undefined' &&
            window.Rozier.importRoutes !== null) {
            window.Rozier.import = new Import(window.Rozier.importRoutes)
            window.Rozier.importRoutes = null
        }
    }

    generalUnbind (objects) {
        for (let object of objects) {
            if (object) {
                object.unbind()
            }
        }
    }

    initCollectionsForms ($scope = null) {
        const _this = this
        let $types = null
        if ($scope !== null) {
            $types = $scope.find('.rz-collection-form-type')
        } else {
            $types = $('.rz-collection-form-type')
        }
        if ($types.length) {
            $types.collection({
                up: '<a tabindex="-1" class="uk-button uk-button-small" href="#"><i tabindex="-1" class="uk-icon uk-icon-angle-up"></i></a>',
                down: '<a tabindex="-1" class="uk-button uk-button-small" href="#"><i tabindex="-1" class="uk-icon uk-icon-angle-down"></i></a>',
                add: '<a tabindex="-1" class="uk-button-primary uk-button uk-button-small" href="#"><i tabindex="-1" class="uk-icon uk-icon-plus"></i></a>',
                remove: '<a tabindex="-1" class="uk-button-danger uk-button uk-button-small" href="#"><i tabindex="-1" class="uk-icon uk-icon-minus"></i></a>',
                after_add: (collection, element) => {
                    _this.initMarkdownEditors(element)
                    _this.initJsonEditors(element)
                    _this.initCssEditors(element)
                    _this.initYamlEditors(element)
                    _this.initBootstrapSwitches(element)
                    _this.initColorPickers(element)
                    _this.initCollectionsForms(element)

                    let $vueComponents = element.find('[data-vuejs]')
                    // Create each component
                    $vueComponents.each((i, el) => {
                        window.Rozier.vueApp.mainContentComponents.push(window.Rozier.vueApp.buildComponent(el))
                    })
                    return true
                }
            })
        }
    }

    initColorPickers ($scope) {
        let $colorPickerInput = $('.colorpicker-input')

        if ($scope && $scope.length) {
            $colorPickerInput = $scope.find('.colorpicker-input')
        }

        // Init colorpicker
        if ($colorPickerInput.length) {
            $colorPickerInput.minicolors()
        }
    }

    initBootstrapSwitches ($scope) {
        let $checkboxes = $('.rz-boolean-checkbox')
        if ($scope && $scope.length) {
            $checkboxes = $scope.find('.rz-boolean-checkbox')
        }

        // Switch checkboxes
        $checkboxes.bootstrapSwitch({
            size: 'small'
        })
    }

    initMarkdownEditors ($scope) {
        // Init markdown-preview
        let $textareasMarkdown = []
        if ($scope && $scope.length) {
            $textareasMarkdown = $scope.find('textarea[data-rz-markdowneditor]')
        } else {
            $textareasMarkdown = $('textarea[data-rz-markdowneditor]')
        }
        let editorCount = $textareasMarkdown.length

        if (editorCount) {
            for (let i = 0; i < editorCount; i++) {
                this.markdownEditors.push(new MarkdownEditor($textareasMarkdown.eq(i), i))
            }
        }
    }

    initJsonEditors ($scope) {
        // Init json-preview
        let $textareasJson = []
        if ($scope && $scope.length) {
            $textareasJson = $scope.find('textarea[data-rz-jsoneditor]')
        } else {
            $textareasJson = $('textarea[data-rz-jsoneditor]')
        }
        let editorCount = $textareasJson.length
        if (editorCount) {
            for (let i = 0; i < editorCount; i++) {
                this.jsonEditors.push(new JsonEditor($textareasJson.eq(i), i))
            }
        }
    }

    initCssEditors ($scope) {
        // Init css-preview
        let $textareasCss = []
        if ($scope && $scope.length) {
            $textareasCss = $scope.find('textarea[data-rz-csseditor]')
        } else {
            $textareasCss = $('textarea[data-rz-csseditor]')
        }
        let editorCount = $textareasCss.length

        if (editorCount) {
            for (let i = 0; i < editorCount; i++) {
                this.cssEditors.push(new CssEditor($textareasCss.eq(i), i))
            }
        }
    }

    initYamlEditors ($scope) {
        // Init yaml-preview
        let $textareasYaml = []
        if ($scope && $scope.length) {
            $textareasYaml = $scope.find('textarea[data-rz-yamleditor]')
        } else {
            $textareasYaml = $('textarea[data-rz-yamleditor]')
        }
        let editorCount = $textareasYaml.length

        if (editorCount) {
            for (let i = 0; i < editorCount; i++) {
                this.yamlEditors.push(new YamlEditor($textareasYaml.eq(i), i))
            }
        }
    }

    initFilterBars () {
        const $selectItemPerPage = $('select.item-per-page')

        if ($selectItemPerPage.length) {
            $selectItemPerPage.off('change')
            $selectItemPerPage.on('change', event => {
                $(event.currentTarget).parents('form').submit()
            })
        }
    }

    /**
     * Resize
     */
    resize () {
        if (this.$canvasLoaderContainer.length) {
            this.$canvasLoaderContainer[0].style.left = window.Rozier.mainContentScrollableOffsetLeft + (window.Rozier.mainContentScrollableWidth / 2) + 'px'
        }
    }
}
