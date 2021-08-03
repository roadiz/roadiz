import $ from 'jquery'
import NodeTreeContextActions from '../components/trees/NodeTreeContextActions'

/**
 * Children nodes field
 */
export default class ChildrenNodesField {
    constructor () {
        // Bind methods
        this.onQuickAddClick = this.onQuickAddClick.bind(this)

        this.init()
    }

    init () {
        this.$fields = $('[data-children-nodes-widget]')
        this.$nodeTrees = this.$fields.find('.nodetree-widget')
        this.$quickAddNodeButtons = this.$fields.find('.children-nodes-quick-creation a')

        if (this.$quickAddNodeButtons.length) {
            this.$quickAddNodeButtons.on('click', this.onQuickAddClick)
        }

        this.$fields.find('.nodetree-langs').remove()
    }

    unbind () {
        if (this.$quickAddNodeButtons.length) {
            this.$quickAddNodeButtons.off('click', this.onQuickAddClick)
        }
    }

    treeAvailable () {
        let $nodeTree = this.$fields.find('.nodetree-widget')

        if ($nodeTree.length) {
            return true
        } else {
            return false
        }
    }

    onQuickAddClick (event) {
        if (this.ajaxTimeout) {
            clearTimeout(this.ajaxTimeout)
        }

        this.ajaxTimeout = window.setTimeout(() => {
            let $link = $(event.currentTarget)
            let nodeTypeId = parseInt($link.attr('data-children-node-type'))
            let parentNodeId = parseInt($link.attr('data-children-parent-node'))
            let translationId = parseInt($link.attr('data-translation-id'))

            if (nodeTypeId > 0 && parentNodeId > 0) {
                let postData = {
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
                    .done(() => {
                        window.Rozier.refreshMainNodeTree()
                        window.Rozier.getMessages()
                        let $nodeTree = $link.parents('.children-nodes-widget').find('.nodetree-widget').eq(0)
                        this.refreshNodeTree($nodeTree)
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
                        // console.log("complete");
                    })
            }
        }, 200)

        return false
    }

    /**
     * @param $nodeTree
     */
    refreshNodeTree ($nodeTree) {
        if ($nodeTree.length) {
            let linkedTypes = []
            let $rootTree = $nodeTree.find('.root-tree').eq(0)
            if (!$rootTree) {
                return
            }
            const rootNodeId = parseInt($rootTree.attr('data-parent-node-id'))
            const linkedTypesRaw = $rootTree.attr('data-linked-types')
            let translationId = parseInt($rootTree.attr('data-translation-id'))
            if (linkedTypesRaw) {
                linkedTypes = JSON.parse(linkedTypesRaw)
            }

            window.Rozier.lazyload.canvasLoader.show()
            let postData = {
                '_token': window.Rozier.ajaxToken,
                '_action': 'requestNodeTree',
                'parentNodeId': rootNodeId,
                'linkedTypes': linkedTypes
            }

            let url = window.Rozier.routes.nodesTreeAjax
            if (translationId && translationId > 0) {
                url += '/' + translationId
            }

            // Do not abort request for nodes which have multiple
            // children node widgets.
            $.ajax({
                url: url,
                type: 'get',
                dataType: 'json',
                cache: false,
                data: postData
            })
                .done(data => {
                    if ($nodeTree.length &&
                        typeof data.nodeTree !== 'undefined') {
                        $nodeTree.fadeOut('slow', () => {
                            let $tempContainer = $nodeTree.parents('.children-nodes-widget')
                            $nodeTree.replaceWith(data.nodeTree)

                            $nodeTree = $tempContainer.find('.nodetree-widget')
                            window.Rozier.initNestables()
                            window.Rozier.bindMainTrees()
                            window.Rozier.lazyload.bindAjaxLink()
                            $nodeTree.fadeIn()

                            this.init()

                            window.Rozier.lazyload.canvasLoader.hide()

                            if (window.Rozier.lazyload.nodeTreeContextActions) {
                                window.Rozier.lazyload.nodeTreeContextActions.unbind()
                            }

                            window.Rozier.lazyload.nodeTreeContextActions = new NodeTreeContextActions()
                        })
                    }
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
        } else {
            console.error('No node-tree available.')
        }
    }
}
