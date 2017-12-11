import $ from 'jquery'

/**
 * Documents bulk
 */
export default function DocumentsBulk () {
    var _this = this

    _this.$documentsCheckboxes = $('input.document-checkbox')
    _this.$documentsIdBulkFolders = $('input.document-id-bulk-folder')
    _this.$actionsMenu = $('.documents-bulk-actions')
    _this.$documentsFolderButton = $('.uk-button-bulk-folder-documents')
    _this.$documentsFolderCont = $('.documents-bulk-folder-cont')
    _this.$documentsSelectAll = $('.uk-button-select-all')
    _this.$documentsDeselectAll = $('.uk-button-bulk-deselect')

    _this.documentsFolderOpen = false
    _this.documentsIds = null

    if (_this.$documentsCheckboxes.length) {
        _this.init()
    }
};

/**
 * Init
 * @return {[type]} [description]
 */
DocumentsBulk.prototype.init = function () {
    var _this = this

    var proxy = $.proxy(_this.onCheckboxChange, _this)
    _this.$documentsCheckboxes.off('change', proxy)
    _this.$documentsCheckboxes.on('change', proxy)

    var $bulkDeleteButton = _this.$actionsMenu.find('.uk-button-bulk-delete-documents')
    var deleteProxy = $.proxy(_this.onBulkDelete, _this)
    $bulkDeleteButton.off('click', deleteProxy)
    $bulkDeleteButton.on('click', deleteProxy)

    _this.$documentsFolderButton.on('click', $.proxy(_this.documentsFolderButtonClick, _this))

    var $bulkDownloadButton = _this.$actionsMenu.find('.uk-button-bulk-download-documents')
    var downloadProxy = $.proxy(_this.onBulkDownload, _this)
    $bulkDownloadButton.off('click', downloadProxy)
    $bulkDownloadButton.on('click', downloadProxy)

    var selectAllProxy = $.proxy(_this.onSelectAll, _this)
    var deselectAllProxy = $.proxy(_this.onDeselectAll, _this)

    _this.$documentsSelectAll.off('click', selectAllProxy)
    _this.$documentsSelectAll.on('click', selectAllProxy)
    _this.$documentsDeselectAll.off('click', deselectAllProxy)
    _this.$documentsDeselectAll.on('click', deselectAllProxy)
}

DocumentsBulk.prototype.onSelectAll = function (event) {
    var _this = this

    _this.$documentsCheckboxes.prop('checked', true)
    _this.onCheckboxChange(null)

    return false
}

DocumentsBulk.prototype.onDeselectAll = function (event) {
    var _this = this

    _this.$documentsCheckboxes.prop('checked', false)
    _this.onCheckboxChange(null)

    return false
}

/**
 * On checkbox change
 * @param  {[type]} event [description]
 * @return {[type]}       [description]
 */
DocumentsBulk.prototype.onCheckboxChange = function (event) {
    var _this = this

    _this.documentsIds = []
    $('input.document-checkbox:checked').each(function (index, domElement) {
        _this.documentsIds.push($(domElement).val())
    })

    if (_this.$documentsIdBulkFolders.length) {
        _this.$documentsIdBulkFolders.val(_this.documentsIds.join(','))
    }

    if (_this.documentsIds.length > 0) {
        _this.showActions()
    } else {
        _this.hideActions()
    }
}

/**
 * On bulk delete
 * @param  {[type]} event [description]
 * @return {[type]}       [description]
 */
DocumentsBulk.prototype.onBulkDelete = function (event) {
    var _this = this

    if (_this.documentsIds.length > 0) {
        history.pushState({
            'headerData': {
                'documents': _this.documentsIds
            }
        }, null, window.Rozier.routes.documentsBulkDeletePage)

        window.Rozier.lazyload.onPopState(null)
    }

    return false
}

/**
 * On bulk Download
 * @param  {[type]} event [description]
 * @return {[type]}       [description]
 */
DocumentsBulk.prototype.onBulkDownload = function (event) {
    var _this = this

    if (_this.documentsIds.length > 0) {
        history.pushState({
            'headerData': {
                'documents': _this.documentsIds
            }
        }, null, window.Rozier.routes.documentsBulkDownloadPage)

        window.Rozier.lazyload.onPopState(null)
    }

    return false
}

/**
 * Show actions
 * @return {[type]} [description]
 */
DocumentsBulk.prototype.showActions = function () {
    var _this = this

    _this.$actionsMenu.stop()
    _this.$actionsMenu.slideDown()
}

/**
 * Hide actions
 * @return {[type]} [description]
 */
DocumentsBulk.prototype.hideActions = function () {
    var _this = this

    _this.$actionsMenu.stop()
    _this.$actionsMenu.slideUp()
}

/**
 * Documents folder button click
 * @return {[type]} [description]
 */
DocumentsBulk.prototype.documentsFolderButtonClick = function (e) {
    var _this = this

    if (!_this.documentsFolderOpen) {
        _this.$documentsFolderCont.slideDown()
        _this.documentsFolderOpen = true
    } else {
        _this.$documentsFolderCont.slideUp()
        _this.documentsFolderOpen = false
    }

    return false
}
