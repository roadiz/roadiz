/**
 * Markdown Editor
 */

MarkdownEditor = function(){
    var _this = this;

    // Selectors
    _this.$cont = $('.uk-htmleditor');

    // Methods
    _this.init();

};


MarkdownEditor.prototype.$cont = null;
MarkdownEditor.prototype.$buttonCode = null;
MarkdownEditor.prototype.$buttonPreview = null;


/**
 * Init
 * @return {[type]} [description]
 */
MarkdownEditor.prototype.init = function(){
    var _this = this;

    if(_this.$cont.length){ 

        for(var i = 0; i < _this.$cont.length; i++) {
            $(_this.$cont[i]).find('.uk-htmleditor-button-code').attr('data-index',0);
            $(_this.$cont[i]).find('.uk-htmleditor-button-preview').attr('data-index',0);
        }
        
        _this.$buttonCode = _this.$cont.find('.uk-htmleditor-button-code');
        _this.$buttonPreview = _this.$cont.find('.uk-htmleditor-button-preview');

        _this.$buttonPreview.on('click', $.proxy(_this.buttonPreviewClick, _this));
        _this.$buttonCode.on('click', $.proxy(_this.buttonCodeClick, _this));

    }

};


/**
 * Button preview click
 * @return {[type]} [description]
 */
MarkdownEditor.prototype.buttonPreviewClick = function(e){
    var _this = this;

    var index = parseInt(e.currentTarget.getAttribute('data-index'));

    // console.log(index);

    _this.$buttonCode[index].style.display = 'block';
    TweenLite.to(_this.$buttonCode[index], 0.5, {opacity:1, ease:Expo.easeOut});

    TweenLite.to(_this.$buttonPreview[index], 0.5, {opacity:0, ease:Expo.easeOut});
    setTimeout(function(){
        _this.$buttonPreview[index].style.display = 'none';
    }, 500);

};


/**
 * Button code click
 * @return {[type]} [description]
 */
MarkdownEditor.prototype.buttonCodeClick = function(e){
    var _this = this;

    var index = parseInt(e.currentTarget.getAttribute('data-index'));

    _this.$buttonPreview[index].style.display = 'block';
    TweenLite.to(_this.$buttonPreview[index], 0.5, {opacity:1, ease:Expo.easeOut});

    TweenLite.to(_this.$buttonCode[index], 0.5, {opacity:0, ease:Expo.easeOut});
    setTimeout(function(){
        _this.$buttonCode[index].style.display = 'none';
    }, 500);

};


/**
 * Window resize callback
 * @return {[type]} [description]
 */
MarkdownEditor.prototype.resize = function(){
    var _this = this;

};