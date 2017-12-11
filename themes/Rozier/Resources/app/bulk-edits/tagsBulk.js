import $ from 'jquery'

/**
 * Tags bulk
 */
export default function TagsBulk () {
    var _this = this

    _this.$tagsCheckboxes = $('input.tag-checkbox')
    _this.$tagsIdBulkTags = $('input.tags-id-bulk-tags')
    _this.$tagsIdBulkStatus = $('input.tags-id-bulk-status')
    _this.$actionsMenu = $('.tags-bulk-actions')

    _this.$tagsFolderButton = $('.uk-button-bulk-folder-tags')
    _this.$tagsFolderCont = $('.tags-bulk-folder-cont')

    _this.$tagsStatusButton = $('.uk-button-bulk-status-tags')
    _this.$tagsStatusCont = $('.tags-bulk-status-cont')

    _this.$tagsSelectAll = $('.uk-button-select-all')
    _this.$tagsDeselectAll = $('.uk-button-bulk-deselect')

    _this.tagsFolderOpen = false
    _this.tagsStatusOpen = false
    _this.tagsIds = null

    if (_this.$tagsCheckboxes.length) {
        _this.init()
    }
};

/**
 * Init
 * @return {[type]} [description]
 */
TagsBulk.prototype.init = function () {
    var _this = this

    var proxy = $.proxy(_this.onCheckboxChange, _this)
    _this.$tagsCheckboxes.off('change', proxy)
    _this.$tagsCheckboxes.on('change', proxy)

    _this.$tagsFolderButton.on('click', $.proxy(_this.tagsFolderButtonClick, _this))
    _this.$tagsStatusButton.on('click', $.proxy(_this.tagsStatusButtonClick, _this))

    var selectAllProxy = $.proxy(_this.onSelectAll, _this)
    var deselectAllProxy = $.proxy(_this.onDeselectAll, _this)

    _this.$tagsSelectAll.off('click', selectAllProxy)
    _this.$tagsSelectAll.on('click', selectAllProxy)
    _this.$tagsDeselectAll.off('click', deselectAllProxy)
    _this.$tagsDeselectAll.on('click', deselectAllProxy)
}

TagsBulk.prototype.onSelectAll = function (event) {
    var _this = this

    _this.$tagsCheckboxes.prop('checked', true)
    _this.onCheckboxChange(null)

    return false
}

TagsBulk.prototype.onDeselectAll = function (event) {
    var _this = this

    _this.$tagsCheckboxes.prop('checked', false)
    _this.onCheckboxChange(null)

    return false
}

/**
 * On checkbox change
 * @param  {[type]} event [description]
 * @return {[type]}       [description]
 */
TagsBulk.prototype.onCheckboxChange = function (event) {
    var _this = this

    _this.tagsIds = []
    $('input.tag-checkbox:checked').each(function (index, domElement) {
        _this.tagsIds.push($(domElement).val())
    })

    if (_this.$tagsIdBulkTags.length) {
        _this.$tagsIdBulkTags.val(_this.tagsIds.join(','))
    }
    if (_this.$tagsIdBulkStatus.length) {
        _this.$tagsIdBulkStatus.val(_this.tagsIds.join(','))
    }

    // console.log(_this.tagsIds);

    if (_this.tagsIds.length > 0) {
        _this.showActions()
    } else {
        _this.hideActions()
    }

    return false
}

/**
 * On bulk delete
 * @param  {[type]} event [description]
 * @return {[type]}       [description]
 */
TagsBulk.prototype.onBulkDelete = function (event) {
    var _this = this

    if (_this.tagsIds.length > 0) {
        history.pushState({
            'headerData': {
                'tags': _this.tagsIds
            }
        }, null, window.Rozier.routes.tagsBulkDeletePage)

        window.Rozier.lazyload.onPopState(null)
    }

    return false
}

/**
 * Show actions
 * @return {[type]} [description]
 */
TagsBulk.prototype.showActions = function () {
    var _this = this

    _this.$actionsMenu.slideDown()
    // _this.$actionsMenu.addClass('visible');
}

/**
 * Hide actions
 * @return {[type]} [description]
 */
TagsBulk.prototype.hideActions = function () {
    var _this = this

    _this.$actionsMenu.slideUp()
    // _this.$actionsMenu.removeClass('visible');
}

/**
 * Tags folder button click
 * @return {[type]} [description]
 */
TagsBulk.prototype.tagsFolderButtonClick = function (e) {
    var _this = this

    if (!_this.tagsFolderOpen) {
        _this.$tagsStatusCont.slideUp()
        _this.tagsStatusOpen = false

        _this.$tagsFolderCont.slideDown()
        _this.tagsFolderOpen = true
    } else {
        _this.$tagsFolderCont.slideUp()
        _this.tagsFolderOpen = false
    }

    return false
}
/**
 * Tags status button click
 * @return {[type]} [description]
 */
TagsBulk.prototype.tagsStatusButtonClick = function (e) {
    var _this = this

    if (!_this.tagsStatusOpen) {
        _this.$tagsFolderCont.slideUp()
        _this.tagsFolderOpen = false

        _this.$tagsStatusCont.slideDown()
        _this.tagsStatusOpen = true
    } else {
        _this.$tagsStatusCont.slideUp()
        _this.tagsStatusOpen = false
    }

    return false
}
