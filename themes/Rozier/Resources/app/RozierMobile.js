import $ from 'jquery'
import {
    TweenLite,
    Expo
} from 'gsap'
import {
    addClass,
    removeClass
} from './utils/plugins'

/**
 * Rozier Mobile
 */
export default class RozierMobile {
    constructor () {
        // Selectors
        this.$menu = $('#menu-mobile')
        this.$adminMenu = $('#admin-menu')
        this.$adminMenuLink = this.$adminMenu.find('a')
        this.$adminMenuNavParent = this.$adminMenu.find('.uk-parent')

        this.$searchButton = $('#search-button')
        this.$searchPanel = $('#nodes-sources-search')
        this.$treeButton = $('#tree-button')
        this.$treeWrapper = $('#tree-wrapper')
        this.$treeWrapperLink = this.$treeWrapper.find('a')
        this.$userPicture = $('#user-picture')
        this.$userActions = $('.user-actions')
        this.$userActionsLink = this.$userActions.find('a')
        this.$mainContentOverlay = $('#main-content-overlay')

        this.menuOpen = false
        this.searchOpen = false
        this.treeOpen = false
        this.adminOpen = false

        this.menuClick = this.menuClick.bind(this)
        this.adminMenuLinkClick = this.adminMenuLinkClick.bind(this)
        this.adminMenuNavParentClick = this.adminMenuNavParentClick.bind(this)
        this.searchButtonClick = this.searchButtonClick.bind(this)
        this.treeButtonClick = this.treeButtonClick.bind(this)
        this.treeWrapperLinkClick = this.treeWrapperLinkClick.bind(this)
        this.userPictureClick = this.userPictureClick.bind(this)
        this.userActionsLinkClick = this.userActionsLinkClick.bind(this)
        this.mainContentOverlayClick = this.mainContentOverlayClick.bind(this)

        // Methods
        this.init()
    }
    /**
     * Init
     * @return {[type]} [description]
     */
    init () {
        if (this.$userPicture.length) {
            // Add class on user picture link to unbind default event
            addClass(this.$userPicture[0], 'rz-no-ajax-link')
        }
        // Events
        this.$menu.on('click', this.menuClick)
        this.$adminMenuLink.on('click', this.adminMenuLinkClick)
        this.$adminMenuNavParent.on('click', this.adminMenuNavParentClick)
        this.$searchButton.on('click', this.searchButtonClick)
        this.$treeButton.on('click', this.treeButtonClick)
        this.$treeWrapperLink.on('click', this.treeWrapperLinkClick)
        this.$userPicture.on('click', this.userPictureClick)
        this.$userActionsLink.on('click', this.userActionsLinkClick)
        this.$mainContentOverlay.on('click', this.mainContentOverlayClick)

        window.addEventListener('pageload', this.mainContentOverlayClick)
    }

    /**
     * Menu click
     * @return {[type]} [description]
     */
    menuClick (e) {
        if (!this.menuOpen) this.openMenu()
        else this.closeMenu()
    }

    /**
     * Admin menu nav parent click
     * @return {[type]} [description]
     */
    adminMenuNavParentClick (e) {
        let $target = $(e.currentTarget)
        let $ukNavSub = $(e.currentTarget).find('.uk-nav-sub')

        // Open
        if (!$target.hasClass('nav-open')) {
            let $ukNavSubItem = $ukNavSub.find('.uk-nav-sub-item')
            let ukNavSubHeight = ($ukNavSubItem.length * 41) - 3

            $ukNavSub[0].style.display = 'block'
            TweenLite.to($ukNavSub, 0.6, {height: ukNavSubHeight,
                ease: Expo.easeOut,
                onComplete: function () {
                }})

            $target.addClass('nav-open')
        } else { // Close
            TweenLite.to($ukNavSub, 0.6, {height: 0,
                ease: Expo.easeOut,
                onComplete: function () {
                    $ukNavSub[0].style.display = 'none'
                }})

            $target.removeClass('nav-open')
        }
    }

    /**
     * Admin menu link click
     * @return {[type]} [description]
     */
    adminMenuLinkClick (e) {
        if (this.menuOpen) this.closeMenu()
    }

    /**
     * Open menu
     * @return {[type]} [description]
     */
    openMenu () {
        // Close panel if open
        this.closeSearch()
        this.closeTree()
        this.closeUser()

        // Translate menu panel
        TweenLite.to(this.$adminMenu, 0.6, {x: 0, ease: Expo.easeOut})
        if (this.$mainContentOverlay.length) {
            this.$mainContentOverlay[0].style.display = 'block'
            TweenLite.to(this.$mainContentOverlay, 0.6, {opacity: 0.5, ease: Expo.easeOut})
        }
        this.menuOpen = true
    }

    /**
     * Close menu
     * @return {[type]} [description]
     */
    closeMenu () {
        let adminMenuX = -window.Rozier.windowWidth * 0.8
        TweenLite.to(this.$adminMenu, 0.6, {x: adminMenuX, ease: Expo.easeOut})
        TweenLite.to(this.$mainContentOverlay, 0.6, {opacity: 0,
            ease: Expo.easeOut,
            onComplete: () => {
                if (this.$mainContentOverlay.length) {
                    this.$mainContentOverlay[0].style.display = 'none'
                }
            }})

        this.menuOpen = false
    }

    /**
     * Search button click
     * @return {[type]} [description]
     */
    searchButtonClick (e) {
        if (!this.searchOpen) this.openSearch()
        else this.closeSearch()
    }

    /**
     * Open search
     * @return {[type]} [description]
     */
    openSearch () {
        // Close panel if open
        this.closeMenu()
        this.closeTree()
        this.closeUser()

        // Translate search panel
        TweenLite.to(this.$searchPanel, 0.6, {x: 0, ease: Expo.easeOut})

        if (this.$mainContentOverlay.length) {
            this.$mainContentOverlay[0].style.display = 'block'
            TweenLite.to(this.$mainContentOverlay, 0.6, {opacity: 0.5, ease: Expo.easeOut})
        }

        // Add active class
        this.$searchButton.addClass('active')
        this.searchOpen = true
    }

    /**
     * Close search
     * @return {[type]} [description]
     */
    closeSearch () {
        let searchPanelX = -window.Rozier.windowWidth * 0.8
        TweenLite.to(this.$searchPanel, 0.6, {x: searchPanelX, ease: Expo.easeOut})
        TweenLite.to(this.$mainContentOverlay, 0.6, {opacity: 0,
            ease: Expo.easeOut,
            onComplete: () => {
                this.$mainContentOverlay[0].style.display = 'none'
            }})

        // Remove active class
        this.$searchButton.removeClass('active')
        this.searchOpen = false
    }

    /**
     * Tree button click
     * @return {[type]} [description]
     */
    treeButtonClick (e) {
        if (!this.treeOpen) this.openTree()
        else this.closeTree()
    }

    /**
     * Tree wrapper link click
     * @return {[type]} [description]
     */
    treeWrapperLinkClick (e) {
        if (e.currentTarget.className.indexOf('tab-link') === -1 && this.treeOpen) {
            this.closeTree()
        }
    }

    /**
     * Open tree
     * @return {[type]} [description]
     */
    openTree () {
        // Close panel if open
        this.closeMenu()
        this.closeSearch()
        this.closeUser()

        // Translate tree panel
        TweenLite.to(this.$treeWrapper, 0.6, {x: 0, ease: Expo.easeOut})

        this.$mainContentOverlay[0].style.display = 'block'
        TweenLite.to(this.$mainContentOverlay, 0.6, {opacity: 0.5, ease: Expo.easeOut})

        // Add active class
        this.$treeButton.addClass('active')
        this.treeOpen = true
    }

    /**
     * Close tree
     * @return {[type]} [description]
     */
    closeTree () {
        let treeWrapperX = -window.Rozier.windowWidth * 0.8

        TweenLite.to(this.$treeWrapper, 0.6, {x: treeWrapperX, ease: Expo.easeOut})
        TweenLite.to(this.$mainContentOverlay, 0.6, {opacity: 0,
            ease: Expo.easeOut,
            onComplete: () => {
                this.$mainContentOverlay[0].style.display = 'none'
            }})

        // Remove active class
        removeClass(this.$treeButton[0], 'active')

        this.treeOpen = false
    }

    /**
     * User picture click
     * @return {[type]} [description]
     */
    userPictureClick (e) {
        if (!this.userOpen) this.openUser()
        else this.closeUser()
        return false
    }

    /**
     * User actions link click
     * @return {[type]} [description]
     */
    userActionsLinkClick (e) {
        if (this.userOpen) {
            this.closeUser()
        }
    }

    /**
     * Open user
     * @return {[type]} [description]
     */
    openUser () {
        // Close panel if open
        this.closeMenu()
        this.closeSearch()
        this.closeTree()

        // Translate user panel
        TweenLite.to(this.$userActions, 0.6, {x: 0, ease: Expo.easeOut})

        if (this.$mainContentOverlay.length) {
            this.$mainContentOverlay[0].style.display = 'block'
            TweenLite.to(this.$mainContentOverlay, 0.6, {opacity: 0.5, ease: Expo.easeOut})
        }

        // Add active class
        this.$userPicture.addClass('active')
        this.userOpen = true
    }

    /**
     * Close user
     * @return {[type]} [description]
     */
    closeUser () {
        let userActionsX = window.Rozier.windowWidth * 0.8
        TweenLite.to(this.$userActions, 0.6, {x: userActionsX, ease: Expo.easeOut})
        TweenLite.to(this.$mainContentOverlay, 0.6, {opacity: 0,
            ease: Expo.easeOut,
            onComplete: () => {
                this.$mainContentOverlay[0].style.display = 'none'
            }})

        // Remove active class
        this.$userPicture.removeClass('active')
        this.userOpen = false
    }

    /**
     * Main content overlay click
     * @return {[type]} [description]
     */
    mainContentOverlayClick (e) {
        this.closeMenu()
        this.closeTree()
        this.closeUser()
        this.closeSearch()
    }

    /**
     * Window resize callback
     * @return {[type]} [description]
     */
    resize () {
    }
}
