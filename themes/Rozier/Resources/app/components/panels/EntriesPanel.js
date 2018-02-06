import $ from 'jquery'

/**
 * Entries panel
 */
export default class EntriesPanel {
    constructor () {
        this.$adminMenuNav = $('#admin-menu-nav')
        this.replaceSubNavs()
    }

    replaceSubNavs () {
        this.$adminMenuNav.find('.uk-nav-sub').each((index, element) => {
            let subMenu = $(element)

            subMenu.attr('style', 'display:block;')

            const top = subMenu.offset().top
            const height = subMenu.height()

            subMenu.removeAttr('style')

            if ((top + height + 20) > $(window).height()) {
                subMenu.parent().addClass('reversed-nav')
            }
        })
    }
}
