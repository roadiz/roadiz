/**
 * Markdown Editor
 */

MarkdownEditor = function(){
    var _this = this;

    // Selectors
    _this.$cont = $('.uk-htmleditor');
    _this.$textarea = _this.$cont.find('textarea');

    // Methods
    _this.init();

};


MarkdownEditor.prototype.$cont = null;
MarkdownEditor.prototype.$textarea = null;
MarkdownEditor.prototype.$buttonCode = null;
MarkdownEditor.prototype.$buttonPreview = null;
MarkdownEditor.prototype.$buttonFullscreen = null;
MarkdownEditor.prototype.$count = null;
MarkdownEditor.prototype.$countCurrent = null;
MarkdownEditor.prototype.countLimit = [];
MarkdownEditor.prototype.$countLimitText = null;
MarkdownEditor.prototype.countAlertActive = [];
MarkdownEditor.prototype.fullscreenActive = [];


/**
 * Init
 * @return {[type]} [description]
 */
MarkdownEditor.prototype.init = function(){
    var _this = this;

    if(_this.$cont.length){ 

        for(var i = 0; i < _this.$cont.length; i++) {

            // Store markdown index into datas
            $(_this.$cont[i]).find('.uk-htmleditor-button-code').attr('data-index',i);
            $(_this.$cont[i]).find('.uk-htmleditor-button-preview').attr('data-index',i);
            $(_this.$cont[i]).find('.uk-htmleditor-button-fullscreen').attr('data-index',i);
            $(_this.$cont[i]).find('textarea').attr('data-index',i);

            // Check if a max length is defined
            if(_this.$textarea[i].getAttribute('data-max-length') !== ''){

                $(_this.$textarea[i]).on('keyup', $.proxy(_this.textareaChange, _this));
                
                _this.countLimit[i] = parseInt(_this.$textarea[i].getAttribute('data-max-length'));

                $(_this.$cont[i]).find('.count-current')[0].innerHTML = stripTags(Rozier.lazyload.htmlEditor[i].currentvalue).length;
                $(_this.$cont[i]).find('.count-limit')[0].innerHTML = _this.$textarea[i].getAttribute('data-max-length');
                $(_this.$cont[i]).find('.uk-htmleditor-count')[0].style.display = 'block';
                
                if(stripTags(Rozier.lazyload.htmlEditor[i].currentvalue).length > _this.countLimit[i]){
                    _this.countAlertActive[i] = true;
                    removeClass(_this.$cont[i], 'content-limit');
                }
                else _this.countAlertActive[i] = false;
            }
            else{
                _this.countLimit[i] = null;
                _this.countAlertActive[i] = null;
            }

            _this.fullscreenActive[i] = false;
        }
        
        // Selectors
        _this.$content = _this.$cont.find('.uk-htmleditor-content');
        _this.$buttonCode = _this.$cont.find('.uk-htmleditor-button-code');
        _this.$buttonPreview = _this.$cont.find('.uk-htmleditor-button-preview');
        _this.$buttonFullscreen = _this.$cont.find('.uk-htmleditor-button-fullscreen');
        _this.$count = _this.$cont.find('.uk-htmleditor-count');
        _this.$countCurrent = _this.$cont.find('.count-current');
        _this.$countLimitText = _this.$cont.find('.count-limit');

        // Events
        _this.$buttonPreview.on('click', $.proxy(_this.buttonPreviewClick, _this));
        _this.$buttonCode.on('click', $.proxy(_this.buttonCodeClick, _this));
        _this.$buttonFullscreen.on('click', $.proxy(_this.buttonFullscreenClick, _this));

    }

};


/**
 * Textarea change
 * @return {[type]} [description]
 */
MarkdownEditor.prototype.textareaChange = function(e){
    var _this = this;

    setTimeout(function(){
         var index = parseInt(e.currentTarget.getAttribute('data-index')),
            textareaVal = Rozier.lazyload.htmlEditor[index].currentvalue,
            textareaValStripped = stripTags(textareaVal),
            textareaValLength = textareaValStripped.length;

        _this.$countCurrent[index].innerHTML = textareaValLength;

        if(textareaValLength > _this.countLimit[index]){
            if(!_this.countAlertActive[index]){
                addClass(_this.$cont[index], 'content-limit');
                _this.countAlertActive[index] = true;
            }
        }
        else{
            if(_this.countAlertActive[index]){
                removeClass(_this.$cont[index], 'content-limit');
                _this.countAlertActive[index] = false;
            }
        }
    }, 100);

};


/**
 * Button preview click
 * @return {[type]} [description]
 */
MarkdownEditor.prototype.buttonPreviewClick = function(e){
    var _this = this;

    var index = parseInt(e.currentTarget.getAttribute('data-index'));

    _this.$buttonCode[index].style.display = 'block';
    TweenLite.to(_this.$buttonCode[index], 0.5, {opacity:1, ease:Expo.easeOut});

    TweenLite.to(_this.$buttonPreview[index], 0.5, {opacity:0, ease:Expo.easeOut, onComplete:function(){
        _this.$buttonPreview[index].style.display = 'none';
    }});

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

    TweenLite.to(_this.$buttonCode[index], 0.5, {opacity:0, ease:Expo.easeOut, onComplete:function(){
        _this.$buttonCode[index].style.display = 'none';
    }});

};


/**
 * Button fullscreen click
 * @return {[type]} [description]
 */
MarkdownEditor.prototype.buttonFullscreenClick = function(e){
    var _this = this;

    var index = parseInt(e.currentTarget.getAttribute('data-index')),
        $fullscreenIcon =  $(_this.$buttonFullscreen[index]).find('i');

    if(!_this.fullscreenActive[index]){
        $fullscreenIcon[0].className = 'uk-icon-rz-fullscreen-off';
        _this.fullscreenActive[index] = true;
    }
    else{
        $fullscreenIcon[0].className = 'uk-icon-rz-fullscreen';
        _this.fullscreenActive[index] = false;
    }

};


/**
 * Window resize callback
 * @return {[type]} [description]
 */
MarkdownEditor.prototype.resize = function(){
    var _this = this;

};