
/**
 * Avoid `console` errors in browsers that lack a console.
 * @return {[type]} [description]
 */
(function() {
    var method;
    var noop = function () {};
    var methods = [
        'assert', 'clear', 'count', 'debug', 'dir', 'dirxml', 'error',
        'exception', 'group', 'groupCollapsed', 'groupEnd', 'info', 'log',
        'markTimeline', 'profile', 'profileEnd', 'table', 'time', 'timeEnd',
        'timeStamp', 'trace', 'warn'
    ];
    var length = methods.length;
    var console = (window.console = window.console || {});

    while (length--) {
        method = methods[length];

        // Only stub undefined methods.
        if (!console[method]) {
            console[method] = noop;
        }
    }
}());


/**
 * Add class custom.
 * @param  {[object]} el                [dom element]
 * @param  {[string]} classToAdd        [class to add]
 * @return {[type]}                     [description]
 */
var addClass = function(el, classToAdd){

    if (el.classList) el.classList.add(classToAdd);
    else el.className += ' ' + classToAdd;
};


/**
 * Remove class custom.
 * @param  {[object]} el                [dom element]
 * @param  {[string]} classToRemove     [class to remove]
 * @return {[type]}                     [description]
 */
var removeClass = function(el, classToRemove){

    if(el.classList) el.classList.remove(classToRemove);
    else{
        el.className = el.className.replace(new RegExp('(^|\\b)' + classToRemove.split(' ').join('|') + '(\\b|$)', 'gi'), '');
    
        var posLastCar = el.className.length-1;
        if(el.className[posLastCar] == ' ') el.className = el.className.substring(0, posLastCar);
    }    
};


// Place any jQuery/helper plugins in here.
;/*
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
