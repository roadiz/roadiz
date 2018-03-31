import $ from 'jquery'

/**
 * Nodes bulk
 */
export default class NodesBulk {
    constructor () {
        this.$nodesCheckboxes = $('input.node-checkbox')
        this.$nodesIdBulkTags = $('input.nodes-id-bulk-tags')
        this.$nodesIdBulkStatus = $('input.nodes-id-bulk-status')
        this.$actionsMenu = $('.nodes-bulk-actions')

        this.$nodesFolderButton = $('.uk-button-bulk-folder-nodes')
        this.$nodesFolderCont = $('.nodes-bulk-folder-cont')

        this.$nodesStatusButton = $('.uk-button-bulk-status-nodes')
        this.$nodesStatusCont = $('.nodes-bulk-status-cont')

        this.$nodesSelectAll = $('.uk-button-select-all')
        this.$nodesDeselectAll = $('.uk-button-bulk-deselect')

        this.nodesFolderOpen = false
        this.nodesStatusOpen = false
        this.nodesIds = null

        this.onCheckboxChange = this.onCheckboxChange.bind(this)
        this.nodesFolderButtonClick = this.nodesFolderButtonClick.bind(this)
        this.nodesStatusButtonClick = this.nodesStatusButtonClick.bind(this)
        this.onSelectAll = this.onSelectAll.bind(this)
        this.onDeselectAll = this.onDeselectAll.bind(this)

        if (this.$nodesCheckboxes.length) {
            this.init()
        }
    }

    /**
     * Init
     */
    init () {
        this.$nodesCheckboxes.on('change', this.onCheckboxChange)
        this.$nodesFolderButton.on('click', this.nodesFolderButtonClick)
        this.$nodesStatusButton.on('click', this.nodesStatusButtonClick)
        this.$nodesSelectAll.on('click', this.onSelectAll)
        this.$nodesDeselectAll.on('click', this.onDeselectAll)
    }

    unbind () {
        if (this.$nodesCheckboxes.length) {
            this.$nodesCheckboxes.off('change', this.onCheckboxChange)
            this.$nodesFolderButton.off('click', this.nodesFolderButtonClick)
            this.$nodesStatusButton.off('click', this.nodesStatusButtonClick)
            this.$nodesSelectAll.off('click', this.onSelectAll)
            this.$nodesDeselectAll.off('click', this.onDeselectAll)
        }
    }

    onSelectAll () {
        this.$nodesCheckboxes.prop('checked', true)
        this.onCheckboxChange(null)
        return false
    }

    onDeselectAll () {
        this.$nodesCheckboxes.prop('checked', false)
        this.onCheckboxChange(null)
        return false
    }

    /**
     * On checkbox change
     */
    onCheckboxChange () {
        this.nodesIds = []

        $('input.node-checkbox:checked').each((index, domElement) => {
            this.nodesIds.push($(domElement).val())
        })

        if (this.$nodesIdBulkTags.length) {
            this.$nodesIdBulkTags.val(this.nodesIds.join(','))
        }

        if (this.$nodesIdBulkStatus.length) {
            this.$nodesIdBulkStatus.val(this.nodesIds.join(','))
        }

        if (this.nodesIds.length > 0) {
            this.showActions()
        } else {
            this.hideActions()
        }

        return false
    }

    /**
     * On bulk delete
     */
    onBulkDelete () {
        if (this.nodesIds.length > 0) {
            history.pushState({
                'headerData': {
                    'nodes': this.nodesIds
                }
            }, null, window.Rozier.routes.nodesBulkDeletePage)

            window.Rozier.lazyload.onPopState(null)
        }

        return false
    }

    /**
     * Show actions
     */
    showActions () {
        this.$actionsMenu.slideDown()
    }

    /**
     * Hide actions
     */
    hideActions () {
        this.$actionsMenu.slideUp()
    }

    /**
     * Nodes folder button click
     */
    nodesFolderButtonClick () {
        if (!this.nodesFolderOpen) {
            this.$nodesStatusCont.slideUp()
            this.nodesStatusOpen = false

            this.$nodesFolderCont.slideDown()
            this.nodesFolderOpen = true
        } else {
            this.$nodesFolderCont.slideUp()
            this.nodesFolderOpen = false
        }

        return false
    }

    /**
     * Nodes status button click
     */
    nodesStatusButtonClick () {
        if (!this.nodesStatusOpen) {
            this.$nodesFolderCont.slideUp()
            this.nodesFolderOpen = false

            this.$nodesStatusCont.slideDown()
            this.nodesStatusOpen = true
        } else {
            this.$nodesStatusCont.slideUp()
            this.nodesStatusOpen = false
        }

        return false
    }
}
