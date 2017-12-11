/**
 * Documents list
 */

DocumentsList = function(){
    var _this = this;

    // Selectors
    _this.$cont = $('.documents-list');
    if(_this.$cont.length) _this.$item = _this.$cont.find('.document-item');

    _this.contWidth = null;
    _this.itemWidth = 144; // (w : 128 + mr : 16)
    _this.itemsPerLine = 4;
    _this.itemsWidth = 576;
    _this.contMarginLeft = 0;

    //_this.resize();
};

/**
 * Window resize callback
 * @return {[type]} [description]
 */
DocumentsList.prototype.resize = function(){
    var _this = this;

    /*if(_this.$cont.length){
        _this.contWidth = _this.$cont.actual('width');
        _this.itemsPerLine = Math.floor(_this.contWidth / _this.itemWidth);
        _this.itemsWidth = (_this.itemWidth * _this.itemsPerLine) - 16;
        _this.contMarginLeft = Math.floor((_this.contWidth - _this.itemsWidth)/2);

        _this.$cont[0].style.marginLeft = _this.contMarginLeft+'px';
    }*/
};
