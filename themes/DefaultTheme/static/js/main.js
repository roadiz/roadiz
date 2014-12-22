/*
 * ============================================================================
 * DefaultTheme entry point
 * ============================================================================
 */
var DefaultTheme = {};

DefaultTheme.$window = null;
DefaultTheme.$body = null;

DefaultTheme.$footer = null;
DefaultTheme.footerFixed = false;

DefaultTheme.bodyHeight = null;
DefaultTheme.windowWidth = null;
DefaultTheme.windowHeight = null;


/**
 * Init
 * @return {[type]} [description]
 */
DefaultTheme.init = function(){
    var _this = this;

    // Selectors
    _this.$window = $(window);
    _this.$body = $('body');

    _this.$footer = $('#footer');

    // Events
    _this.$window.on('resize', $.proxy(_this.resize, _this));
    _this.$window.trigger('resize');

};


/**
 * Resize
 * @return {[type]} [description]
 */
DefaultTheme.resize = function(){
    var _this = this;

    _this.bodyHeight = _this.$body.height();

    _this.windowWidth = _this.$window.width();
    _this.windowHeight = _this.$window.height();

    if(_this.bodyHeight <= _this.windowHeight && !_this.footerFixed){
        addClass(_this.$footer[0],'fixed');
        _this.footerFixed = true;
    }
    else if(_this.footerFixed){
        removeClass(_this.$footer[0],'fixed');
        _this.footerFixed = false;
    }

};


/*
 * ============================================================================
 * Plug into jQuery standard events
 * ============================================================================
 */
$(document).ready($.proxy(DefaultTheme.init, DefaultTheme));
