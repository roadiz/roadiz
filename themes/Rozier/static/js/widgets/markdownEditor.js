/**
 * Markdown Editor
 */

MarkdownEditor = function($textarea, index){
    var _this = this;

    _this.$textarea = $textarea;
    _this.textarea = _this.$textarea[0];

    _this.htmlEditor = CodeMirror.fromTextArea(_this.textarea, {
        mode: 'gfm',
        lineNumbers: false,
        theme: "default",
        tabSize: 4,
        styleActiveLine: true,
        indentWithTabs: false,
        lineWrapping: true,
        dragDrop: false
    });
    // Selectors
    _this.$cont = _this.$textarea.parents('.uk-htmleditor');
    _this.index = index;
    _this.$buttonCode = null;
    _this.$buttonPreview = null;
    _this.$buttonFullscreen = null;
    _this.$count = null;
    _this.$countCurrent = null;
    _this.limit = 0;
    _this.countMinLimit = 0;
    _this.countMaxLimit = 0;
    _this.$countMaxLimitText = null;
    _this.countAlertActive = false;
    _this.fullscreenActive = false;

    _this.$parentForm = _this.$textarea.parents('form');

    // Methods
    _this.changeNavToBottom();
    _this.init();
};


MarkdownEditor.prototype.changeNavToBottom = function() {
    var _this = this;

    var $HTMLeditorNav = _this.$cont.find('.uk-htmleditor-navbar');
    if ($HTMLeditorNav.length) {
        var HTMLeditorNavInner = '<div class="uk-htmleditor-navbar bottom">'+ $HTMLeditorNav[0].innerHTML+'</div>';
        _this.$cont.append(HTMLeditorNavInner);
        var $HTMLeditorNavToRemove = _this.$cont.find('.uk-htmleditor-navbar:not(.bottom)');
        $HTMLeditorNavToRemove.remove();
    }
};


/**
 * Init
 * @return {[type]} [description]
 */
MarkdownEditor.prototype.init = function(){
    var _this = this;

    _this.htmlEditor.on('change', $.proxy(_this.textareaChange, _this));

    if(_this.$cont.length &&
       _this.$textarea.length) {

        // Selectors
        _this.$content = _this.$cont.find('.uk-htmleditor-content');
        _this.$buttonCode = _this.$cont.find('.uk-htmleditor-button-code');
        _this.$buttonPreview = _this.$cont.find('.uk-htmleditor-button-preview');
        _this.$buttonFullscreen = _this.$cont.find('.uk-htmleditor-button-fullscreen');
        _this.$count = _this.$cont.find('.uk-htmleditor-count');
        _this.$countCurrent = _this.$cont.find('.count-current');
        _this.$countMaxLimitText = _this.$cont.find('.count-limit');

        // Store markdown index into datas
        _this.$cont.find('.uk-htmleditor-button-code').attr('data-index', _this.index);
        _this.$cont.find('.uk-htmleditor-button-preview').attr('data-index', _this.index);
        _this.$cont.find('.uk-htmleditor-button-fullscreen').attr('data-index', _this.index);
        _this.$cont.find('.markdown_textarea').attr('data-index', _this.index);
        _this.$cont.find('.CodeMirror').attr('data-index', _this.index);

        // Check if a desc is defined
        if(_this.textarea.hasAttribute('data-desc') &&
            _this.textarea.getAttribute('data-desc') !== ''){
            _this.$cont.after('<div class="form-help uk-alert uk-alert-large">'+_this.textarea.getAttribute('data-desc')+'</div>');
        }

        // Check if a max length is defined
        if(_this.textarea.hasAttribute('data-max-length') &&
            _this.textarea.getAttribute('data-max-length') !== ''){
            _this.limit = true;
            _this.countMaxLimit = parseInt(_this.textarea.getAttribute('data-max-length'));

            if (_this.$countCurrent.length &&
                _this.$countMaxLimitText.length &&
                _this.$count.length) {

                _this.$countCurrent[0].innerHTML = stripTags(_this.htmlEditor.getValue()).length;
                _this.$countMaxLimitText[0].innerHTML = _this.textarea.getAttribute('data-max-length');
                _this.$count[0].style.display = 'block';
            }

        }

        if(_this.textarea.hasAttribute('data-min-length') &&
            _this.textarea.getAttribute('data-min-length') !== ''){
            _this.limit = true;
            _this.countMinLimit = parseInt(_this.textarea.getAttribute('data-min-length'));
        }

        if(_this.textarea.hasAttribute('data-max-length') &&
           _this.textarea.hasAttribute('data-min-length') &&
           _this.textarea.getAttribute('data-min-length') === '' &&
           _this.textarea.getAttribute('data-max-length') === ''){

            _this.limit = false;
            _this.countMaxLimit = null;
            _this.countAlertActive = null;
        }

        _this.fullscreenActive = false;

        if(_this.limit){

             // Check if current length is over limit
            if(stripTags(_this.htmlEditor.getValue()).length > _this.countMaxLimit){
                _this.countAlertActive = true;
                addClass(_this.$cont[0], 'content-limit');
            }
            else if(stripTags(_this.htmlEditor.getValue()).length < _this.countMinLimit){
                _this.countAlertActive = true;
                addClass(_this.$cont[0], 'content-limit');
            }
            else _this.countAlertActive = false;
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

    _this.htmlEditor.save();

    if(_this.limit){
        setTimeout(function(){
            var textareaVal = _this.htmlEditor.getValue();
            var textareaValStripped = stripTags(textareaVal);
            var textareaValLength = textareaValStripped.length;

            _this.$countCurrent.html(textareaValLength);

            if(textareaValLength > _this.countMaxLimit){
                if(!_this.countAlertActive){
                    _this.$cont.addClass('content-limit');
                    _this.countAlertActive = true;
                }
            }
            else if(textareaValLength < _this.countMinLimit){
                if(!_this.countAlertActive){
                    _this.$cont.addClass('content-limit');
                    _this.countAlertActive = true;
                }
            }
            else{
                if(_this.countAlertActive){
                    _this.$cont.removeClass('content-limit');
                    _this.countAlertActive = false;
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

    _this.$buttonCode[0].style.display = 'block';
    TweenLite.to(_this.$buttonCode, 0.5, {opacity:1, ease:Expo.easeOut});

    TweenLite.to(_this.$buttonPreview[0], 0.5, {opacity:0, ease:Expo.easeOut, onComplete:function(){
        _this.$buttonPreview[0].style.display = 'none';
    }});

};


/**
 * Button code click
 * @return {[type]} [description]
 */
MarkdownEditor.prototype.buttonCodeClick = function(e){
    var _this = this;

    var index = parseInt(e.currentTarget.getAttribute('data-index'));

    _this.$buttonPreview[0].style.display = 'block';
    TweenLite.to(_this.$buttonPreview[0], 0.5, {opacity:1, ease:Expo.easeOut});

    TweenLite.to(_this.$buttonCode[0], 0.5, {opacity:0, ease:Expo.easeOut, onComplete:function(){
        _this.$buttonCode[0].style.display = 'none';
    }});

};


/**
 * Button fullscreen click
 * @return {[type]} [description]
 */
MarkdownEditor.prototype.buttonFullscreenClick = function(e){
    var _this = this;

    var index = parseInt(e.currentTarget.getAttribute('data-index')),
        $fullscreenIcon =  $(_this.$buttonFullscreen).find('i');

    if(!_this.fullscreenActive){
        $fullscreenIcon[0].className = 'uk-icon-rz-fullscreen-off';
        _this.fullscreenActive = true;
    }
    else{
        $fullscreenIcon[0].className = 'uk-icon-rz-fullscreen';
        _this.fullscreenActive = false;
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
            if(_this.fullscreenActive){
                $(_this.$buttonFullscreen).find('a').trigger('click');
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

