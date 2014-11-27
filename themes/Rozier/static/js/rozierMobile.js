/**
 * Rozier Mobile
 */

RozierMobile = function(){
    var _this = this;

    // Selectors
    _this.$menu = $('#menu-mobile');
    _this.$adminMenu = $('#admin-menu');
    _this.$adminMenuLink = _this.$adminMenu.find('a');
    _this.$adminMenuNavParent = _this.$adminMenu.find('.uk-parent');

    _this.$treeButton = $('#tree-button');
    _this.$treeWrapper = $('#tree-wrapper');
    _this.$treeWrapperLink = _this.$treeWrapper.find('a');

    _this.$userPicture = $('#user-picture');
    _this.$userActions = $('.user-actions');

    _this.$mainContentOverlay = $('#main-content-overlay');

    // Methods
    _this.init();

};


RozierMobile.prototype.$menu = null;
RozierMobile.prototype.menuOpen = false;
RozierMobile.prototype.$adminMenu = null;
RozierMobile.prototype.$adminMenuNavParent = null;
RozierMobile.prototype.$treeButton = null;
RozierMobile.prototype.$treeWrapper = null;
RozierMobile.prototype.$treeWrapperLink = null;
RozierMobile.prototype.$userPicture = null;
RozierMobile.prototype.$userActions = null;
RozierMobile.prototype.$mainContentOverlay = null;


/**
 * Init
 * @return {[type]} [description]
 */
RozierMobile.prototype.init = function(){
    var _this = this;

    // Add class on user picture link to unbind default event
    addClass(_this.$userPicture[0],'rz-no-ajax-link');

    // Events
    _this.$menu.on('click', $.proxy(_this.menuClick, _this));
    _this.$adminMenuLink.on('click', $.proxy(_this.adminMenuLinkClick, _this));
    _this.$adminMenuNavParent.on('click', $.proxy(_this.adminMenuNavParentClick, _this));

    _this.$treeButton.on('click', $.proxy(_this.treeButtonClick, _this));
    _this.$treeWrapperLink.on('click', $.proxy(_this.treeWrapperLinkClick, _this));
    
    _this.$userPicture.on('click', $.proxy(_this.userPictureClick, _this));
    // _this.$userActionsLink.on('click', $.proxy(_this.userActionsLinkClick, _this));

    _this.$mainContentOverlay.on('click', $.proxy(_this.mainContentOverlayClick, _this));

};


/**
 * Menu click
 * @return {[type]} [description]
 */
RozierMobile.prototype.menuClick = function(e){
    var _this = this;

    if(!_this.menuOpen)_this.openMenu();
    else _this.closeMenu();

};


/**
 * Admin menu link click
 * @return {[type]} [description]
 */
RozierMobile.prototype.adminMenuLinkClick = function(e){
    var _this = this;

    if(_this.menuOpen) _this.closeMenu();

};


/**
 * Open menu
 * @return {[type]} [description]
 */
RozierMobile.prototype.openMenu = function(){
    var _this = this;

    TweenLite.to(_this.$adminMenu, 0.6, {x:0, ease:Expo.easeOut});

    _this.$mainContentOverlay[0].style.display = 'block';
    TweenLite.to(_this.$mainContentOverlay, 0.6, {opacity:0.5, ease:Expo.easeOut});
     
    _this.menuOpen = true;
};


/**
 * Close menu
 * @return {[type]} [description]
 */
RozierMobile.prototype.closeMenu = function(){
    var _this = this;

    var adminMenuX = -Rozier.windowWidth*0.8;

    TweenLite.to(_this.$adminMenu, 0.6, {x:adminMenuX, ease:Expo.easeOut});

    TweenLite.to(_this.$mainContentOverlay, 0.6, {opacity:0, ease:Expo.easeOut, onComplete:function(){
        _this.$mainContentOverlay[0].style.display = 'none';
    }});
    
    _this.menuOpen = false;  
};


/**
 * Tree button click
 * @return {[type]} [description]
 */
RozierMobile.prototype.treeButtonClick = function(e){
    var _this = this;

    if(!_this.treeOpen)_this.openTree();
    else _this.closeTree();

};


/**
 * Tree wrapper link click
 * @return {[type]} [description]
 */
RozierMobile.prototype.treeWrapperLinkClick = function(e){
    var _this = this;

    if(e.currentTarget.className.indexOf('tab-link') == -1 && _this.treeOpen){
        _this.closeTree();
    }
};


/**
 * Open tree
 * @return {[type]} [description]
 */
RozierMobile.prototype.openTree = function(){
    var _this = this;

    TweenLite.to(_this.$treeWrapper, 0.6, {x:0, ease:Expo.easeOut});

    _this.$mainContentOverlay[0].style.display = 'block';
    TweenLite.to(_this.$mainContentOverlay, 0.6, {opacity:0.5, ease:Expo.easeOut});
     
    _this.treeOpen = true;
};


/**
 * Close tree
 * @return {[type]} [description]
 */
RozierMobile.prototype.closeTree = function(){
    var _this = this;

    var treeWrapperX = Rozier.windowWidth*0.8;

    TweenLite.to(_this.$treeWrapper, 0.6, {x:treeWrapperX, ease:Expo.easeOut});

    TweenLite.to(_this.$mainContentOverlay, 0.6, {opacity:0, ease:Expo.easeOut, onComplete:function(){
        _this.$mainContentOverlay[0].style.display = 'none';
    }});
    
    _this.treeOpen = false;  
};


/**
 * User picture click
 * @return {[type]} [description]
 */
RozierMobile.prototype.userPictureClick = function(e){
    var _this = this;

    if(!_this.userOpen)_this.openUser();
    else _this.closeUser();

    return false;
};


/**
 * User actions link click
 * @return {[type]} [description]
 */
// RozierMobile.prototype.userActionsLinkClick = function(e){
//     var _this = this;

//     if(e.currentTarget.className.indexOf('tab-link') == -1 && _this.userOpen){
//         _this.closeUser();
//     }
// };


/**
 * Open user
 * @return {[type]} [description]
 */
RozierMobile.prototype.openUser = function(){
    var _this = this;

    TweenLite.to(_this.$userActions, 0.6, {x:0, ease:Expo.easeOut});

    _this.$mainContentOverlay[0].style.display = 'block';
    TweenLite.to(_this.$mainContentOverlay, 0.6, {opacity:0.5, ease:Expo.easeOut});
     
    _this.userOpen = true;
};


/**
 * Close user
 * @return {[type]} [description]
 */
RozierMobile.prototype.closeUser = function(){
    var _this = this;

    var userActionsX = Rozier.windowWidth*0.8;

    TweenLite.to(_this.$userActions, 0.6, {x:userActionsX, ease:Expo.easeOut});

    TweenLite.to(_this.$mainContentOverlay, 0.6, {opacity:0, ease:Expo.easeOut, onComplete:function(){
        _this.$mainContentOverlay[0].style.display = 'none';
    }});
    
    _this.userOpen = false;  
};


/**
 * Main content overlay click
 * @return {[type]} [description]
 */
RozierMobile.prototype.mainContentOverlayClick = function(e){
    var _this = this;

     if(_this.menuOpen) _this.closeMenu();
     else if(_this.treeOpen) _this.closeTree();
     else if(_this.userOpen) _this.closeUser();
};


/**
 * Admin menu nav parent click
 * @return {[type]} [description]
 */
RozierMobile.prototype.adminMenuNavParentClick = function(e){
    var _this = this;

    var $ukNavSub = $(e.currentTarget).find('.uk-nav-sub');

    // Open
    if(e.currentTarget.className.indexOf('nav-open') == -1) {
        // console.log('open');
        var $ukNavSubItem = $ukNavSub.find('.uk-nav-sub-item'),
            ukNavSubHeight = $ukNavSubItem.length * 44;

        $ukNavSub[0].style.display = 'block';
        TweenLite.to($ukNavSub, 0.6, {height:ukNavSubHeight, ease:Expo.easeOut, onComplete:function(){
            addClass(e.currentTarget, 'nav-open');
        }});        

    }
    // Close
    else{
        // console.log('close');
        TweenLite.to($ukNavSub, 0.6, {height:0, ease:Expo.easeOut, onComplete:function(){
            removeClass(e.currentTarget, 'nav-open');
            $ukNavSub[0].style.display = 'none';
        }});
    }

};


/**
 * Window resize callback
 * @return {[type]} [description]
 */
RozierMobile.prototype.resize = function(){
    var _this = this;

};