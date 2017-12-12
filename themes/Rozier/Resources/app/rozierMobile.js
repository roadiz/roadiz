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
export default function RozierMobile () {
    var _this = this

    // Selectors
    _this.$menu = $('#menu-mobile')
    _this.$adminMenu = $('#admin-menu')
    _this.$adminMenuLink = _this.$adminMenu.find('a')
    _this.$adminMenuNavParent = _this.$adminMenu.find('.uk-parent')

    _this.$searchButton = $('#search-button')
    _this.$searchPanel = $('#nodes-sources-search')

    _this.$treeButton = $('#tree-button')
    _this.$treeWrapper = $('#tree-wrapper')
    _this.$treeWrapperLink = _this.$treeWrapper.find('a')

    _this.$userPicture = $('#user-picture')
    _this.$userActions = $('.user-actions')
    _this.$userActionsLink = _this.$userActions.find('a')

    _this.$mainContentOverlay = $('#main-content-overlay')

    _this.menuOpen = false
    _this.searchOpen = false
    _this.treeOpen = false
    _this.adminOpen = false

    // Methods
    _this.init()
}

/**
 * Init
 * @return {[type]} [description]
 */
RozierMobile.prototype.init = function () {
    var _this = this

    if (_this.$userPicture.length) {
        // Add class on user picture link to unbind default event
        addClass(_this.$userPicture[0], 'rz-no-ajax-link')
    }

    // Events
    _this.$menu.on('click', $.proxy(_this.menuClick, _this))
    _this.$adminMenuLink.on('click', $.proxy(_this.adminMenuLinkClick, _this))
    _this.$adminMenuNavParent.on('click', $.proxy(_this.adminMenuNavParentClick, _this))

    _this.$searchButton.on('click', $.proxy(_this.searchButtonClick, _this))

    _this.$treeButton.on('click', $.proxy(_this.treeButtonClick, _this))
    _this.$treeWrapperLink.on('click', $.proxy(_this.treeWrapperLinkClick, _this))

    _this.$userPicture.on('click', $.proxy(_this.userPictureClick, _this))
    _this.$userActionsLink.on('click', $.proxy(_this.userActionsLinkClick, _this))

    _this.$mainContentOverlay.on('click', $.proxy(_this.mainContentOverlayClick, _this))
}

/**
 * Menu click
 * @return {[type]} [description]
 */
RozierMobile.prototype.menuClick = function (e) {
    var _this = this

    if (!_this.menuOpen)_this.openMenu()
    else _this.closeMenu()
}

/**
 * Admin menu nav parent click
 * @return {[type]} [description]
 */
RozierMobile.prototype.adminMenuNavParentClick = function (e) {
    var $target = $(e.currentTarget)
    var $ukNavSub = $(e.currentTarget).find('.uk-nav-sub')

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
RozierMobile.prototype.adminMenuLinkClick = function (e) {
    var _this = this

    if (_this.menuOpen) _this.closeMenu()
}

/**
 * Open menu
 * @return {[type]} [description]
 */
RozierMobile.prototype.openMenu = function () {
    var _this = this

    // Close panel if open
    if (_this.searchOpen) _this.closeSearch()
    else if (_this.treeOpen) _this.closeTree()
    else if (_this.userOpen) _this.closeUser()

    // Translate menu panel
    TweenLite.to(_this.$adminMenu, 0.6, {x: 0, ease: Expo.easeOut})

    _this.$mainContentOverlay[0].style.display = 'block'
    TweenLite.to(_this.$mainContentOverlay, 0.6, {opacity: 0.5, ease: Expo.easeOut})

    _this.menuOpen = true
}

/**
 * Close menu
 * @return {[type]} [description]
 */
RozierMobile.prototype.closeMenu = function () {
    var _this = this

    var adminMenuX = -window.Rozier.windowWidth * 0.8

    TweenLite.to(_this.$adminMenu, 0.6, {x: adminMenuX, ease: Expo.easeOut})

    TweenLite.to(_this.$mainContentOverlay, 0.6, {opacity: 0,
        ease: Expo.easeOut,
        onComplete: function () {
            _this.$mainContentOverlay[0].style.display = 'none'
        }})

    _this.menuOpen = false
}

/**
 * Search button click
 * @return {[type]} [description]
 */
RozierMobile.prototype.searchButtonClick = function (e) {
    var _this = this

    if (!_this.searchOpen)_this.openSearch()
    else _this.closeSearch()
}

/**
 * Open search
 * @return {[type]} [description]
 */
RozierMobile.prototype.openSearch = function () {
    var _this = this

    // Close panel if open
    if (_this.menuOpen) _this.closeMenu()
    else if (_this.treeOpen) _this.closeTree()
    else if (_this.userOpen) _this.closeUser()

    // Translate search panel
    TweenLite.to(_this.$searchPanel, 0.6, {x: 0, ease: Expo.easeOut})

    _this.$mainContentOverlay[0].style.display = 'block'
    TweenLite.to(_this.$mainContentOverlay, 0.6, {opacity: 0.5, ease: Expo.easeOut})

    // Add active class
    _this.$searchButton.addClass('active')
    _this.searchOpen = true
}

/**
 * Close search
 * @return {[type]} [description]
 */
RozierMobile.prototype.closeSearch = function () {
    var _this = this
    var searchPanelX = -window.Rozier.windowWidth * 0.8
    TweenLite.to(_this.$searchPanel, 0.6, {x: searchPanelX, ease: Expo.easeOut})
    TweenLite.to(_this.$mainContentOverlay, 0.6, {opacity: 0,
        ease: Expo.easeOut,
        onComplete: function () {
            _this.$mainContentOverlay[0].style.display = 'none'
        }})

    // Remove active class
    _this.$searchButton.removeClass('active')
    _this.searchOpen = false
}

/**
 * Tree button click
 * @return {[type]} [description]
 */
RozierMobile.prototype.treeButtonClick = function (e) {
    var _this = this

    if (!_this.treeOpen)_this.openTree()
    else _this.closeTree()
}

/**
 * Tree wrapper link click
 * @return {[type]} [description]
 */
RozierMobile.prototype.treeWrapperLinkClick = function (e) {
    var _this = this

    if (e.currentTarget.className.indexOf('tab-link') === -1 && _this.treeOpen) {
        _this.closeTree()
    }
}

/**
 * Open tree
 * @return {[type]} [description]
 */
RozierMobile.prototype.openTree = function () {
    var _this = this

    // Close panel if open
    if (_this.menuOpen) _this.closeMenu()
    else if (_this.searchOpen) _this.closeSearch()
    else if (_this.userOpen) _this.closeUser()

    // Translate tree panel
    TweenLite.to(_this.$treeWrapper, 0.6, {x: 0, ease: Expo.easeOut})

    _this.$mainContentOverlay[0].style.display = 'block'
    TweenLite.to(_this.$mainContentOverlay, 0.6, {opacity: 0.5, ease: Expo.easeOut})

    // Add active class
    _this.$treeButton.addClass('active')

    _this.treeOpen = true
}

/**
 * Close tree
 * @return {[type]} [description]
 */
RozierMobile.prototype.closeTree = function () {
    var _this = this

    var treeWrapperX = -window.Rozier.windowWidth * 0.8

    TweenLite.to(_this.$treeWrapper, 0.6, {x: treeWrapperX, ease: Expo.easeOut})

    TweenLite.to(_this.$mainContentOverlay, 0.6, {opacity: 0,
        ease: Expo.easeOut,
        onComplete: function () {
            _this.$mainContentOverlay[0].style.display = 'none'
        }})

    // Remove active class
    removeClass(_this.$treeButton[0], 'active')

    _this.treeOpen = false
}

/**
 * User picture click
 * @return {[type]} [description]
 */
RozierMobile.prototype.userPictureClick = function (e) {
    var _this = this

    if (!_this.userOpen)_this.openUser()
    else _this.closeUser()

    return false
}

/**
 * User actions link click
 * @return {[type]} [description]
 */
RozierMobile.prototype.userActionsLinkClick = function (e) {
    var _this = this

    if (_this.userOpen) {
        _this.closeUser()
    }
}

/**
 * Open user
 * @return {[type]} [description]
 */
RozierMobile.prototype.openUser = function () {
    var _this = this

    // Close panel if open
    if (_this.menuOpen) _this.closeMenu()
    else if (_this.searchOpen) _this.closeSearch()
    else if (_this.treeOpen) _this.closeTree()

    // Translate user panel
    TweenLite.to(_this.$userActions, 0.6, {x: 0, ease: Expo.easeOut})

    if (_this.$mainContentOverlay.length) {
        _this.$mainContentOverlay[0].style.display = 'block'
        TweenLite.to(_this.$mainContentOverlay, 0.6, {opacity: 0.5, ease: Expo.easeOut})
    }

    // Add active class
    _this.$userPicture.addClass('active')
    _this.userOpen = true
}

/**
 * Close user
 * @return {[type]} [description]
 */
RozierMobile.prototype.closeUser = function () {
    var _this = this
    var userActionsX = window.Rozier.windowWidth * 0.8

    TweenLite.to(_this.$userActions, 0.6, {x: userActionsX, ease: Expo.easeOut})
    TweenLite.to(_this.$mainContentOverlay, 0.6, {opacity: 0,
        ease: Expo.easeOut,
        onComplete: function () {
            _this.$mainContentOverlay[0].style.display = 'none'
        }})

    // Remove active class
    _this.$userPicture.removeClass('active')
    _this.userOpen = false
}

/**
 * Main content overlay click
 * @return {[type]} [description]
 */
RozierMobile.prototype.mainContentOverlayClick = function (e) {
    var _this = this

    if (_this.menuOpen) _this.closeMenu()
    else if (_this.treeOpen) _this.closeTree()
    else if (_this.userOpen) _this.closeUser()
}

/**
 * Window resize callback
 * @return {[type]} [description]
 */
RozierMobile.prototype.resize = function () {}
