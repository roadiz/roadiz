/**
 * NODE TREE
 */

NodeTree = function(){
    var _this = this;

    // Selectors
    _this.$content = $('.content-node-tree');

    // Methods
    if(_this.$content.length){
        _this.$dropdown = _this.$content.find('.uk-dropdown-small');
        _this.init();
    }

};


NodeTree.prototype.$content = null;
NodeTree.prototype.$elements = null;
NodeTree.prototype.$dropdown = null;


/**
 * Init
 * @return {[type]} [description]
 */
NodeTree.prototype.init = function(){
    var _this = this;

    _this.contentHeight = _this.$content.actual('outerHeight');

    if(_this.contentHeight >= (Rozier.windowHeight - 400)) _this.dropdownFlip();

};


/**
 * Flip dropdown
 * @return {[type]}       [description]
 */
NodeTree.prototype.dropdownFlip = function(){
    var _this = this;

    for (var i = _this.$dropdown.length - 1; i >= _this.$dropdown.length - 3; i--) {
        addClass(_this.$dropdown[i], 'uk-dropdown-up');
    }
};


/**
 * Window resize callback
 * @return {[type]} [description]
 */
NodeTree.prototype.resize = function(){
    var _this = this;

};
