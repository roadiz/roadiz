import $ from 'jquery'
import NodesBulk from '../components/bulk-edits/NodesBulk'
import NodeTreeContextActions from '../components/trees/NodeTreeContextActions'

export default class StackNodeTree {
    constructor () {
        this.$page = $('.stack-tree').eq(0)
        this.currentRequest = null
        this.$quickAddNodeButtons = this.$page.find('.stack-tree-quick-creation a')
        this.$switchLangButtons = this.$page.find('.nodetree-langs a')
        this.$nodeTree = this.$page.find('.root-tree').eq(0)

        this.onQuickAddClick = this.onQuickAddClick.bind(this)
        this.onChangeLangClick = this.onChangeLangClick.bind(this)

        this.init()
    }

    /**
     * @return {Number}
     */
    getCurrentPage () {
        this.$nodeTree = this.$page.find('.root-tree').eq(0)
        let currentPage = parseInt(this.$nodeTree.attr('data-page'))
        if (isNaN(currentPage)) {
            return 1
        }

        return currentPage
    }

    /**
     * @return {Number|null}
     */
    getTranslationId () {
        this.$nodeTree = this.$page.find('.root-tree').eq(0)
        let currentTranslationId = parseInt(this.$nodeTree.attr('data-translation-id'))
        if (isNaN(currentTranslationId)) {
            return null
        }

        return currentTranslationId
    }

    init () {
        if (this.$quickAddNodeButtons.length) {
            this.$quickAddNodeButtons.on('click', this.onQuickAddClick)
        }

        if (this.$switchLangButtons.length) {
            this.$switchLangButtons.on('click', this.onChangeLangClick)
        }
    }

    unbind () {
        if (this.$quickAddNodeButtons.length) {
            this.$quickAddNodeButtons.off('click', this.onQuickAddClick)
        }

        if (this.$switchLangButtons.length) {
            this.$switchLangButtons.off('click', this.onChangeLangClick)
        }
    }

    onChangeLangClick (event) {
        event.preventDefault()

        let $link = $(event.currentTarget)
        let parentNodeId = parseInt($link.attr('data-children-parent-node'))
        let translationId = parseInt($link.attr('data-translation-id'))
        let tagId = $link.attr('data-filter-tag')
        this.refreshNodeTree(parentNodeId, translationId, tagId)
        return false
    }

    /**
     * @param {Event} event
     * @returns {boolean}
     */
    onQuickAddClick (event) {
        if (this.currentRequest && this.currentRequest.readyState !== 4) {
            this.currentRequest.abort()
        }

        let $link = $(event.currentTarget)
        let nodeTypeId = parseInt($link.attr('data-children-node-type'))
        let parentNodeId = parseInt($link.attr('data-children-parent-node'))

        if (nodeTypeId > 0 && parentNodeId > 0) {
            let postData = {
                '_token': window.Rozier.ajaxToken,
                '_action': 'quickAddNode',
                'nodeTypeId': nodeTypeId,
                'parentNodeId': parentNodeId,
                'pushTop': 1
            }

            if ($link.attr('data-filter-tag')) {
                postData.tagId = parseInt($link.attr('data-filter-tag'))
            }

            this.currentRequest = $.ajax({
                url: window.Rozier.routes.nodesQuickAddAjax,
                type: 'post',
                dataType: 'json',
                data: postData
            })
                .done(data => {
                    window.Rozier.refreshMainNodeTree()
                    this.refreshNodeTree(parentNodeId, null, postData.tagId, 1)
                    window.Rozier.getMessages()
                })
                .fail(data => {
                    data = JSON.parse(data.responseText)
                    window.UIkit.notify({
                        message: data.error_message,
                        status: 'danger',
                        timeout: 3000,
                        pos: 'top-center'
                    })
                })
                .always(() => {
                    console.debug('complete')
                })
        }

        return false
    }

    treeAvailable () {
        let $nodeTree = this.$page.find('.nodetree-widget')
        return !!$nodeTree.length
    }

    /**
     *
     * @param rootNodeId
     * @param translationId
     * @param tagId
     * @param page
     */
    refreshNodeTree (rootNodeId, translationId, tagId, page) {
        if (this.currentRequest && this.currentRequest.readyState !== 4) {
            this.currentRequest.abort()
        }

        let $nodeTree = this.$page.find('.nodetree-widget')

        if ($nodeTree.length) {
            let $rootTree = $nodeTree.find('.root-tree').eq(0)

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
            let postData = {
                '_token': window.Rozier.ajaxToken,
                '_action': 'requestNodeTree',
                'stackTree': true,
                'parentNodeId': rootNodeId,
                'page': this.getCurrentPage(),
                'translationId': this.getTranslationId()
            }

            let url = window.Rozier.routes.nodesTreeAjax
            if (translationId && translationId > 0) {
                postData.translationId = parseInt(translationId)
            }

            /*
             * Add translation id route param manually
             */
            if (postData.translationId) {
                url += '/' + postData.translationId
            }

            if (page) {
                postData.page = parseInt(page)
            }

            // data-filter-tag
            if (tagId) {
                postData.tagId = parseInt(tagId)
            }

            this.currentRequest = $.ajax({
                url: url,
                type: 'get',
                cache: false,
                dataType: 'json',
                data: postData
            })
                .done(data => {
                    if ($nodeTree.length && typeof data.nodeTree !== 'undefined') {
                        $nodeTree.fadeOut('slow', () => {
                            $nodeTree.replaceWith(data.nodeTree)
                            $nodeTree = this.$page.find('.nodetree-widget')

                            window.Rozier.initNestables()
                            window.Rozier.bindMainTrees()
                            window.Rozier.lazyload.bindAjaxLink()
                            $nodeTree.fadeIn()
                            window.Rozier.resize()

                            /* eslint-disable no-new */
                            new NodesBulk()

                            this.$switchLangButtons = this.$page.find('.nodetree-langs a')
                            this.$nodeTree = this.$page.find('.root-tree').eq(0)

                            if (this.$switchLangButtons.length) {
                                this.$switchLangButtons.off('click', this.onChangeLangClick)
                                this.$switchLangButtons.on('click', this.onChangeLangClick)
                            }

                            window.Rozier.lazyload.canvasLoader.hide()

                            if (window.Rozier.lazyload.nodeTreeContextActions) {
                                window.Rozier.lazyload.nodeTreeContextActions.unbind()
                            }

                            window.Rozier.lazyload.nodeTreeContextActions = new NodeTreeContextActions()
                        })
                    }
                })
        } else {
            console.error('No node-tree available.')
        }
    }
}
