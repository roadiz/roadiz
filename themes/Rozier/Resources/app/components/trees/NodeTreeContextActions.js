import $ from 'jquery'

export default class NodeTreeContextActions {
    constructor () {
        this.$contextualMenus = $('.tree-contextualmenu')
        this.$links = this.$contextualMenus.find('.node-actions a')
        this.$nodeMoveFirstLinks = this.$contextualMenus.find('a.move-node-first-position')
        this.$nodeMoveLastLinks = this.$contextualMenus.find('a.move-node-last-position')

        this.onClick = this.onClick.bind(this)
        this.moveNodeToPosition = this.moveNodeToPosition.bind(this)

        if (this.$links.length) {
            this.bind()
        }
    }

    bind () {
        this.$links.on('click', this.onClick)
        this.$nodeMoveFirstLinks.on('click', (e) => this.moveNodeToPosition('first', e))
        this.$nodeMoveLastLinks.on('click', (e) => this.moveNodeToPosition('last', e))
    }

    unbind () {
        this.$links.off('click', this.onClick)
        this.$nodeMoveFirstLinks.off('click')
        this.$nodeMoveLastLinks.off('click')
    }

    onClick (event) {
        event.preventDefault()

        let $link = $(event.currentTarget)
        let $element = $($link.parents('.nodetree-element')[0])
        let nodeId = parseInt($element.data('node-id'))
        let statusName = $link.attr('data-status')
        let statusValue = $link.attr('data-value')
        let action = $link.attr('data-action')

        if (typeof action !== 'undefined') {
            window.Rozier.lazyload.canvasLoader.show()

            if (typeof statusName !== 'undefined' &&
                typeof statusValue !== 'undefined') {
                // Change node status
                this.changeStatus(nodeId, statusName, statusValue)
            } else {
                // Other actions
                if (action === 'duplicate') {
                    this.duplicateNode(nodeId)
                }
            }
        }
    }

    changeStatus (nodeId, statusName, statusValue) {
        if (this.ajaxTimeout) {
            window.clearTimeout(this.ajaxTimeout)
        }

        this.ajaxTimeout = window.setTimeout(() => {
            let postData = {
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
            .done(() => {
                window.Rozier.refreshAllNodeTrees()
                window.Rozier.getMessages()
            })
            .fail(data => {
                data = JSON.parse(data.responseText)
                window.UIkit.notify({
                    message: data.message,
                    status: 'danger',
                    timeout: 3000,
                    pos: 'top-center'
                })
            })
            .always(() => {
                window.Rozier.lazyload.canvasLoader.hide()
            })
        }, 100)
    }

    /**
     * Move a node to the position.
     *
     * @param nodeId
     */
    duplicateNode (nodeId) {
        if (this.ajaxTimeout) {
            window.clearTimeout(this.ajaxTimeout)
        }

        this.ajaxTimeout = window.setTimeout(() => {
            let postData = {
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
            .done(() => {
                window.Rozier.refreshAllNodeTrees()
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
                window.Rozier.lazyload.canvasLoader.hide()
            })
        }, 100)
    }

    /**
     * Move a node to the position.
     *
     * @param {String} position
     * @param {Event} event
     */
    moveNodeToPosition (position, event) {
        window.Rozier.lazyload.canvasLoader.show()

        let element = $($(event.currentTarget).parents('.nodetree-element')[0])
        let nodeId = parseInt(element.data('node-id'))
        let parentNodeId = parseInt(element.parents('ul').first().data('parent-node-id'))
        let postData = {
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
        .done(() => {
            window.Rozier.refreshAllNodeTrees()
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
            window.Rozier.lazyload.canvasLoader.hide()
        })
    }
}
