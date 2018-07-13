import $ from 'jquery'

/**
 * Documents Bulk
 * @class
 */
export default class DocumentsBulk {
    /**
     * Create a documents bulk
     */
    constructor () {
        this.$documentsCheckboxes = $('input.document-checkbox')
        this.$documentsIdBulkFolders = $('input.document-id-bulk-folder')
        this.$actionsMenu = $('.documents-bulk-actions')
        this.$documentsFolderButton = $('.uk-button-bulk-folder-documents')
        this.$documentsFolderCont = $('.documents-bulk-folder-cont')
        this.$documentsSelectAll = $('.uk-button-select-all')
        this.$documentsDeselectAll = $('.uk-button-bulk-deselect')
        this.$bulkDeleteButton = this.$actionsMenu.find('.uk-button-bulk-delete-documents')
        this.$bulkDownloadButton = this.$actionsMenu.find('.uk-button-bulk-download-documents')
        this.documentsFolderOpen = false
        this.documentsIds = null

        this.onCheckboxChange = this.onCheckboxChange.bind(this)
        this.onBulkDelete = this.onBulkDelete.bind(this)
        this.documentsFolderButtonClick = this.documentsFolderButtonClick.bind(this)
        this.onBulkDownload = this.onBulkDownload.bind(this)
        this.onSelectAll = this.onSelectAll.bind(this)
        this.onDeselectAll = this.onDeselectAll.bind(this)

        if (this.$documentsCheckboxes.length) {
            this.init()
        }
    }

    init () {
        this.$documentsCheckboxes.on('change', this.onCheckboxChange)
        this.$bulkDeleteButton.on('click', this.onBulkDelete)
        this.$documentsFolderButton.on('click', this.documentsFolderButtonClick)
        this.$bulkDownloadButton.on('click', this.onBulkDownload)
        this.$documentsSelectAll.on('click', this.onSelectAll)
        this.$documentsDeselectAll.on('click', this.onDeselectAll)
    }

    unbind () {
        if (this.$documentsCheckboxes.length) {
            this.$documentsCheckboxes.off('change', this.onCheckboxChange)
            this.$bulkDeleteButton.off('click', this.onBulkDelete)
            this.$documentsFolderButton.off('click', this.documentsFolderButtonClick)
            this.$bulkDownloadButton.off('click', this.onBulkDownload)
            this.$documentsSelectAll.off('click', this.onSelectAll)
            this.$documentsDeselectAll.off('click', this.onDeselectAll)
        }
    }

    onSelectAll () {
        this.$documentsCheckboxes.prop('checked', true)
        this.onCheckboxChange(null)
        return false
    }

    onDeselectAll () {
        this.$documentsCheckboxes.prop('checked', false)
        this.onCheckboxChange(null)
        return false
    }

    /**
     * On checkbox change
     */
    onCheckboxChange () {
        this.documentsIds = []

        $('input.document-checkbox:checked').each((index, domElement) => {
            this.documentsIds.push($(domElement).val())
        })

        if (this.$documentsIdBulkFolders.length) {
            this.$documentsIdBulkFolders.val(this.documentsIds.join(','))
        }

        if (this.documentsIds.length > 0) {
            this.showActions()
        } else {
            this.hideActions()
        }
    }

    /**
     * On bulk delete
     * @returns {boolean}
     */
    onBulkDelete () {
        if (this.documentsIds.length > 0) {
            history.pushState({
                'headerData': {
                    'documents': this.documentsIds
                }
            }, null, window.Rozier.routes.documentsBulkDeletePage)

            window.Rozier.lazyload.onPopState(null)
        }

        return false
    }

    /**
     * On bulk Download
     * @returns {boolean}
     */
    onBulkDownload () {
        if (this.documentsIds.length > 0) {
            history.pushState({
                'headerData': {
                    'documents': this.documentsIds
                }
            }, null, window.Rozier.routes.documentsBulkDownloadPage)

            window.Rozier.lazyload.onPopState(null)
        }

        return false
    }

    /**
     * Show actions
     */
    showActions () {
        this.$actionsMenu.stop()
        this.$actionsMenu.slideDown()
    }

    /**
     * Hide actions
     */
    hideActions () {
        this.$actionsMenu.stop()
        this.$actionsMenu.slideUp()
    }

    /**
     * Documents folder button click
     * @returns {boolean}
     */
    documentsFolderButtonClick () {
        if (!this.documentsFolderOpen) {
            this.$documentsFolderCont.slideDown()
            this.documentsFolderOpen = true
        } else {
            this.$documentsFolderCont.slideUp()
            this.documentsFolderOpen = false
        }

        return false
    }
}
