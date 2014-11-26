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
    _this.$mainContentOverlay = $('#main-content-overlay');

    // Methods
    _this.init();

};


RozierMobile.prototype.$menu = null;
RozierMobile.prototype.menuOpen = false;
RozierMobile.prototype.$adminMenu = null;
RozierMobile.prototype.$adminMenuNavParent = null;
RozierMobile.prototype.$mainContentOverlay = null;


/**
 * Init
 * @return {[type]} [description]
 */
RozierMobile.prototype.init = function(){
    var _this = this;

    // Events
    _this.$menu.on('click', $.proxy(_this.menuClick, _this));
    _this.$adminMenuLink.on('click', $.proxy(_this.adminMenuLinkClick, _this));
    _this.$adminMenuNavParent.on('click', $.proxy(_this.adminMenuNavParentClick, _this));
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
 * Admin menu link click
 * @return {[type]} [description]
 */
RozierMobile.prototype.adminMenuLinkClick = function(e){
    var _this = this;

    _this.closeMenu();

};


/**
 * Main content overlay click
 * @return {[type]} [description]
 */
RozierMobile.prototype.mainContentOverlayClick = function(e){
    var _this = this;

    console.log('main content overlay click');

     _this.closeMenu();

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