/**
 * Documents list
 */

DocumentsList = function(){
    var _this = this;

    // Selectors
    _this.$cont = $('.documents-list');
    if(_this.$cont.length) _this.$item = _this.$cont.find('.document-item');

    _this.resize();
};


DocumentsList.prototype.$cont = null;
DocumentsList.prototype.contWidth = null;
DocumentsList.prototype.$item = null;
DocumentsList.prototype.itemWidth = 144; // (w : 128 + mr : 16)
DocumentsList.prototype.itemsPerLine = 4;
DocumentsList.prototype.itemsWidth = 576;
DocumentsList.prototype.contMarginLeft = 0;


/**
 * Window resize callback
 * @return {[type]} [description]
 */
DocumentsList.prototype.resize = function(){
    var _this = this;

    // console.log('documents list resize');

    if(_this.$cont.length){
        _this.contWidth = _this.$cont.actual('width');
        _this.itemsPerLine = Math.floor(_this.contWidth / _this.itemWidth);
        _this.itemsWidth = (_this.itemWidth * _this.itemsPerLine) - 16;
        _this.contMarginLeft = Math.floor((_this.contWidth - _this.itemsWidth)/2);

        _this.$cont[0].style.marginLeft = _this.contMarginLeft+'px'; 

        // console.log('cont width  : '+_this.contWidth);
        // console.log('item width  : '+_this.itemWidth);
        // console.log('items /line : '+_this.itemsPerLine);
        // console.log('items width : '+_this.itemsWidth);
        // console.log('cont ml     : '+_this.contMarginLeft);
        // console.log('-----------------------');
    }

};
