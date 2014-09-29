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
 * @return {[type]} [description]
 */
resizeContainer.prototype.init = function() {
    var _this = this;

    _this.windowHeight = _this.$window.height();  
    _this.mainContainerHeight = _this.$mainContainer.height();
    _this.windowHeightLimit = _this.windowHeight-(_this.margin*2);

    // Check if we have enough size to center container
    if(_this.mainContainerHeight < _this.windowHeightLimit){     
        _this.$mainContainer[0].className = 'absolute';
        _this.$mainContainer[0].style.marginTop = -_this.mainContainerHeight/2 +'px';
    }
    else{
        _this.$mainContainer[0].className = 'relative';
       _this.$mainContainer[0].style.marginTop = '50px';
    }
};
