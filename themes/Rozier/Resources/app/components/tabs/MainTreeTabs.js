import $ from 'jquery'

const STORAGE_KEY = 'roadiz.currentMainTreeTab'

export default class MainTreeTabs {
    constructor () {
        this.onTabChange = this.onTabChange.bind(this)
        this.tabsMenu = $('#tree-menu')
        const currentTabId = window.localStorage.getItem(STORAGE_KEY)

        if (this.tabsMenu) {
            window.UIkit.tab(this.tabsMenu, {
                connect: '#tree-container',
                swiping: false,
                active: currentTabId ? Number.parseInt(currentTabId) : 0
            })
            this.tabsMenu.on('change.uk.tab', this.onTabChange)
        }
    }

    unbind () {
        this.tabsMenu.off('change.uk.tab', this.onTabChange)
    }

    onTabChange (event, activeItem, previousItem) {
        activeItem = activeItem[0]
        const index = activeItem.getAttribute('data-index')
        if (index) {
            window.localStorage.setItem(STORAGE_KEY, index)
        }
    }
}
