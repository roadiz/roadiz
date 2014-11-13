/**
 * Rozier Mobile
 */

RozierMobile = function(){
    var _this = this;

    // Selectors
    _this.$menu = $('#menu-mobile');
    _this.$adminMenu = $('#admin-menu');

    // Methods
    _this.init();

};


RozierMobile.prototype.$menu = null;
RozierMobile.prototype.menuOpen = false;
RozierMobile.prototype.$adminMenu = null;


/**
 * Init
 * @return {[type]} [description]
 */
RozierMobile.prototype.init = function(){
    var _this = this;

    // Events
    _this.$menu.on('click', $.proxy(_this.menuClick, _this));

};


/**
 * Menu click
 * @return {[type]} [description]
 */
RozierMobile.prototype.menuClick = function(e){
    var _this = this;

    
    if(!_this.menuOpen){
        _this.openMenu();
    }
    else{
        _this.closeMenu();
    }

};


/**
 * Open menu
 * @return {[type]} [description]
 */
RozierMobile.prototype.openMenu = function(){
    var _this = this;

    _this.$adminMenu[0].style.display = 'block';
    _this.menuOpen = true;

};


/**
 * Close menu
 * @return {[type]} [description]
 */
RozierMobile.prototype.closeMenu = function(){
    var _this = this;

    _this.$adminMenu[0].style.display = 'none';  
    _this.menuOpen = false;  

};


/**
 * Window resize callback
 * @return {[type]} [description]
 */
RozierMobile.prototype.resize = function(){
    var _this = this;

};