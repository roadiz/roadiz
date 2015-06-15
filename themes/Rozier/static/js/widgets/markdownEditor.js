/**
 * Markdown Editor
 */

MarkdownEditor = function(){
    var _this = this;

    // Selectors
    _this.$cont = $('.uk-htmleditor');
    _this.$textarea = _this.$cont.find('.markdown_textarea');
    _this.$buttonCode = null;
    _this.$buttonPreview = null;
    _this.$buttonFullscreen = null;
    _this.$count = null;
    _this.$countCurrent = null;
    _this.limit = [];
    _this.countMinLimit = [];
    _this.countMaxLimit = [];
    _this.$countMaxLimitText = null;
    _this.countAlertActive = [];
    _this.fullscreenActive = [];

    // Methods
    setTimeout(function(){
        _this.init();
    }, 0);

};


/**
 * Init
 * @return {[type]} [description]
 */
MarkdownEditor.prototype.init = function(){
    var _this = this;

    if(_this.$cont.length &&
       _this.$textarea.length) {

        for(var i = _this.$cont.length - 1; i >= 0; i--) {

            // Store markdown index into datas
            $(_this.$cont[i]).find('.uk-htmleditor-button-code').attr('data-index',i);
            $(_this.$cont[i]).find('.uk-htmleditor-button-preview').attr('data-index',i);
            $(_this.$cont[i]).find('.uk-htmleditor-button-fullscreen').attr('data-index',i);
            $(_this.$cont[i]).find('.markdown_textarea').attr('data-index',i);
            $(_this.$cont[i]).find('.CodeMirror').attr('data-index',i);

            // Check if a desc is defined
            if(_this.$textarea[i].hasAttribute('data-desc') &&
                _this.$textarea[i].getAttribute('data-desc') !== ''){
                $(_this.$cont[i]).after('<div class="form-help uk-alert uk-alert-large">'+_this.$textarea[i].getAttribute('data-desc')+'</div>');
            }

            // Check if a max length is defined
            if(_this.$textarea[i].hasAttribute('data-max-length') &&
                _this.$textarea[i].getAttribute('data-max-length') !== ''){
                _this.limit[i] = true;
                _this.countMaxLimit[i] = parseInt(_this.$textarea[i].getAttribute('data-max-length'));
                $(_this.$cont[i]).find('.count-current')[0].innerHTML = stripTags(Rozier.lazyload.htmlEditor[i].currentvalue).length;
                $(_this.$cont[i]).find('.count-limit')[0].innerHTML = _this.$textarea[i].getAttribute('data-max-length');
                $(_this.$cont[i]).find('.uk-htmleditor-count')[0].style.display = 'block';

            }

            if(_this.$textarea[i].hasAttribute('data-min-length') &&
                _this.$textarea[i].getAttribute('data-min-length') !== ''){
                _this.limit[i] = true;
                _this.countMinLimit[i] = parseInt(_this.$textarea[i].getAttribute('data-min-length'));
            }

            if(_this.$textarea[i].hasAttribute('data-max-length') &&
               _this.$textarea[i].hasAttribute('data-min-length') &&
               _this.$textarea[i].getAttribute('data-min-length') === '' &&
               _this.$textarea[i].getAttribute('data-max-length') === ''){

                _this.limit[i] = false;
                _this.countMaxLimit[i] = null;
                _this.countAlertActive[i] = null;
            }

            _this.fullscreenActive[i] = false;

            if(_this.limit[i]){

                 // Check if current length is over limit
                if(stripTags(Rozier.lazyload.htmlEditor[i].currentvalue).length > _this.countMaxLimit[i]){
                    _this.countAlertActive[i] = true;
                    addClass(_this.$cont[i], 'content-limit');
                }
                else if(stripTags(Rozier.lazyload.htmlEditor[i].currentvalue).length < _this.countMinLimit[i]){
                    _this.countAlertActive[i] = true;
                    addClass(_this.$cont[i], 'content-limit');
                }
                else _this.countAlertActive[i] = false;
            }

            $(_this.$cont[i]).find('.CodeMirror').on('keyup', $.proxy(_this.textareaChange, _this));
        }


        // Selectors
        _this.$content = _this.$cont.find('.uk-htmleditor-content');
        _this.$buttonCode = _this.$cont.find('.uk-htmleditor-button-code');
        _this.$buttonPreview = _this.$cont.find('.uk-htmleditor-button-preview');
        _this.$buttonFullscreen = _this.$cont.find('.uk-htmleditor-button-fullscreen');
        _this.$count = _this.$cont.find('.uk-htmleditor-count');
        _this.$countCurrent = _this.$cont.find('.count-current');
        _this.$countMaxLimitText = _this.$cont.find('.count-limit');


        // Events
        for(var j = Rozier.lazyload.$textAreaHTMLeditor.length - 1; j >= 0; j--) {
            Rozier.lazyload.htmlEditor[j].editor.on('focus', $.proxy(_this.textareaFocus, _this));
            Rozier.lazyload.htmlEditor[j].editor.on('blur', $.proxy(_this.textareaFocusOut, _this));
        }

        _this.$buttonPreview.on('click', $.proxy(_this.buttonPreviewClick, _this));
        _this.$buttonCode.on('click', $.proxy(_this.buttonCodeClick, _this));
        _this.$buttonFullscreen.on('click', $.proxy(_this.buttonFullscreenClick, _this));
        Rozier.$window.on('keyup', $.proxy(_this.echapKey, _this));
    }

};


/**
 * Textarea change
 * @return {[type]} [description]
 */
MarkdownEditor.prototype.textareaChange = function(e){
    var _this = this;

    var index = parseInt(e.currentTarget.getAttribute('data-index'));

    if(_this.limit[index]){
        setTimeout(function(){

            var textareaVal = Rozier.lazyload.htmlEditor[index].currentvalue,
                textareaValStripped = stripTags(textareaVal),
                textareaValLength = textareaValStripped.length;

            _this.$countCurrent[index].innerHTML = textareaValLength;

            if(textareaValLength > _this.countMaxLimit[index]){
                if(!_this.countAlertActive[index]){
                    addClass(_this.$cont[index], 'content-limit');
                    _this.countAlertActive[index] = true;
                }
            }
            else if(textareaValLength < _this.countMinLimit[index]){
                console.log('inf limit ');
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
    }

};


/**
 * Textarea focus
 * @return {[type]} [description]
 */
MarkdownEditor.prototype.textareaFocus = function(e){
    var _this = this;

   $(e.display.wrapper).parent().parent().parent().parent().addClass('form-col-focus');

};


/**
 * Textarea focus out
 * @return {[type]} [description]
 */
MarkdownEditor.prototype.textareaFocusOut = function(e){
    var _this = this;

    $(e.display.wrapper).parent().parent().parent().parent().removeClass('form-col-focus');

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
 * Echap key to close explorer
 * @return {[type]} [description]
 */
MarkdownEditor.prototype.echapKey = function(e){
    var _this = this;

    if(e.keyCode == 27){

        for(var i = 0; i < _this.$cont.length; i++) {

            if(_this.fullscreenActive[i]){
                $(_this.$buttonFullscreen[i]).find('a').trigger('click');
                break;
            }
        }


    }

    return false;
};


/**
 * Window resize callback
 * @return {[type]} [description]
 */
MarkdownEditor.prototype.resize = function(){
    var _this = this;

};
