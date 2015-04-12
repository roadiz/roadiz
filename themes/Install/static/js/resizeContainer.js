/**
 * Resize container
 */

var resizeContainer = function() {
    var _this = this;

    _this.$window = $(window);
    _this.$mainContainer = $('#main-container');

    _this.$window.on('resize', $.proxy(_this.init, _this));
    _this.$window.trigger('resize');

};

resizeContainer.prototype.$window = null;
resizeContainer.prototype.windowHeight = null;
resizeContainer.prototype.windowHeightLimit = null;
resizeContainer.prototype.$mainContainer = null;
resizeContainer.prototype.mainContainerHeight = 0;
resizeContainer.prototype.margin = 50;


/**
 * Init
 * @return void
 */
resizeContainer.prototype.init = function() {
    var _this = this;

    _this.windowHeight = _this.$window.outerHeight();
    _this.mainContainerHeight = _this.$mainContainer.outerHeight();
    _this.windowHeightLimit = _this.windowHeight-(_this.margin*2);

    // Check if we have enough size to center container
    if((_this.mainContainerHeight + _this.margin) < _this.windowHeightLimit) {
        $('body').css('height', _this.windowHeight);
        _this.$mainContainer[0].className = 'absolute';
        _this.$mainContainer[0].style.marginTop = -_this.mainContainerHeight/2 +'px';
    }
    else {
        $('body').css('position', 'relative');
        _this.$mainContainer[0].className = 'relative';
        _this.$mainContainer[0].style.marginTop = '';
    }
};
