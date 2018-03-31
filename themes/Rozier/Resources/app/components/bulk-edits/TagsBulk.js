import $ from 'jquery'

/**
 * Tags bulk
 */
export default class TagsBulk {
    /**
     * Create Tags bulk
     */
    constructor () {
        this.$tagsCheckboxes = $('input.tag-checkbox')
        this.$tagsIdBulkTags = $('input.tags-id-bulk-tags')
        this.$tagsIdBulkStatus = $('input.tags-id-bulk-status')
        this.$actionsMenu = $('.tags-bulk-actions')

        this.$tagsFolderButton = $('.uk-button-bulk-folder-tags')
        this.$tagsFolderCont = $('.tags-bulk-folder-cont')

        this.$tagsStatusButton = $('.uk-button-bulk-status-tags')
        this.$tagsStatusCont = $('.tags-bulk-status-cont')

        this.$tagsSelectAll = $('.uk-button-select-all')
        this.$tagsDeselectAll = $('.uk-button-bulk-deselect')

        this.tagsFolderOpen = false
        this.tagsStatusOpen = false
        this.tagsIds = null

        this.onCheckboxChange = this.onCheckboxChange.bind(this)
        this.tagsFolderButtonClick = this.tagsFolderButtonClick.bind(this)
        this.tagsStatusButtonClick = this.tagsStatusButtonClick.bind(this)
        this.onSelectAll = this.onSelectAll.bind(this)
        this.onDeselectAll = this.onDeselectAll.bind(this)

        if (this.$tagsCheckboxes.length) {
            this.init()
        }
    }

    /**
     * Init
     */
    init () {
        this.$tagsCheckboxes.on('change', this.onCheckboxChange)
        this.$tagsStatusButton.on('click', this.tagsStatusButtonClick)
        this.$tagsFolderButton.on('click', this.tagsFolderButtonClick)
        this.$tagsSelectAll.on('click', this.onSelectAll)
        this.$tagsDeselectAll.on('click', this.onDeselectAll)
    }

    unbind () {
        if (this.$tagsCheckboxes.length) {
            this.$tagsCheckboxes.off('change', this.onCheckboxChange)
            this.$tagsStatusButton.off('click', this.tagsStatusButtonClick)
            this.$tagsFolderButton.off('click', this.tagsFolderButtonClick)
            this.$tagsSelectAll.off('click', this.onSelectAll)
            this.$tagsDeselectAll.off('click', this.onDeselectAll)
        }
    }

    onSelectAll () {
        this.$tagsCheckboxes.prop('checked', true)
        this.onCheckboxChange(null)

        return false
    }

    onDeselectAll () {
        this.$tagsCheckboxes.prop('checked', false)
        this.onCheckboxChange(null)

        return false
    }

    /**
     * On checkbox change
     */
    onCheckboxChange () {
        this.tagsIds = []

        $('input.tag-checkbox:checked').each((index, domElement) => {
            this.tagsIds.push($(domElement).val())
        })

        if (this.$tagsIdBulkTags.length) {
            this.$tagsIdBulkTags.val(this.tagsIds.join(','))
        }
        if (this.$tagsIdBulkStatus.length) {
            this.$tagsIdBulkStatus.val(this.tagsIds.join(','))
        }

        if (this.tagsIds.length > 0) {
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
        if (this.tagsIds.length > 0) {
            history.pushState({
                'headerData': {
                    'tags': this.tagsIds
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
     * Tags folder button click
     */
    tagsFolderButtonClick () {
        if (!this.tagsFolderOpen) {
            this.$tagsStatusCont.slideUp()
            this.tagsStatusOpen = false

            this.$tagsFolderCont.slideDown()
            this.tagsFolderOpen = true
        } else {
            this.$tagsFolderCont.slideUp()
            this.tagsFolderOpen = false
        }

        return false
    }

    /**
     * Tags status button click
     */
    tagsStatusButtonClick () {
        if (!this.tagsStatusOpen) {
            this.$tagsFolderCont.slideUp()
            this.tagsFolderOpen = false

            this.$tagsStatusCont.slideDown()
            this.tagsStatusOpen = true
        } else {
            this.$tagsStatusCont.slideUp()
            this.tagsStatusOpen = false
        }

        return false
    }
}
