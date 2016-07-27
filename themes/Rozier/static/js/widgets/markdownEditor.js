/**
 * Markdown Editor
 */

MarkdownEditor = function($textarea, index){
    var _this = this;

    marked.setOptions({
        gfm: true,
        tables: true,
        breaks: false,
        pedantic: false,
        sanitize: true,
        smartLists: true,
        smartypants: false
    });

    _this.$textarea = $textarea;
    _this.textarea = _this.$textarea[0];
    _this.usePreview = false;

    _this.editor = CodeMirror.fromTextArea(_this.textarea, {
        mode: 'gfm',
        lineNumbers: false,
        tabSize: 4,
        styleActiveLine: true,
        indentWithTabs: false,
        lineWrapping: true,
        viewportMargin: Infinity,
        enterMode: "keep"
    });

    _this.editor.addKeyMap({
        "Ctrl-B": function(cm) {
            cm.replaceSelections(_this.boldSelections());
        },
        "Ctrl-I": function(cm) {
            cm.replaceSelections(_this.italicSelections());
        },
        "Cmd-B": function(cm) {
            cm.replaceSelections(_this.boldSelections());
        },
        "Cmd-I": function(cm) {
            cm.replaceSelections( _this.italicSelections());
        },
    });

    // Selectors
    _this.$cont = _this.$textarea.parents('.uk-form-row').eq(0);
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

    _this.$parentForm = _this.$textarea.parents('form').eq(0);
    _this.closePreviewProxy = $.proxy(_this.closePreview, _this);

    // Methods
    _this.init();
};


/**
 * Init
 * @return {[type]} [description]
 */
MarkdownEditor.prototype.init = function(){
    var _this = this;

    _this.editor.on('change', $.proxy(_this.textareaChange, _this));

    if(_this.$cont.length &&
       _this.$textarea.length) {

        _this.$editor = _this.$cont.find('.CodeMirror').eq(0);

        _this.$cont.addClass('markdown-editor');
        _this.$buttons = _this.$cont.find('[data-markdowneditor-button]');
        // Selectors
        _this.$content = _this.$cont.find('.markdown-editor-content');
        _this.$buttonCode = _this.$cont.find('.markdown-editor-button-code');
        _this.$buttonPreview = _this.$cont.find('.markdown-editor-button-preview');
        _this.$buttonFullscreen = _this.$cont.find('.markdown-editor-button-fullscreen');
        _this.$count = _this.$cont.find('.markdown-editor-count');
        _this.$countCurrent = _this.$cont.find('.count-current');
        _this.$countMaxLimitText = _this.$cont.find('.count-limit');

        // Store markdown index into datas
        _this.$cont.find('.markdown-editor-button-code').attr('data-index', _this.index);
        _this.$cont.find('.markdown-editor-button-preview').attr('data-index', _this.index);
        _this.$cont.find('.markdown-editor-button-fullscreen').attr('data-index', _this.index);
        _this.$cont.find('.markdown_textarea').attr('data-index', _this.index);
        _this.$editor.attr('data-index', _this.index);

        /*
         * Create preview tab.
         */
        _this.$editor.before('<div class="markdown-editor-tabs">');
        _this.$tabs = _this.$cont.find('.markdown-editor-tabs').eq(0);

        _this.$editor.after('<div class="markdown-editor-preview">');
        _this.$preview = _this.$cont.find('.markdown-editor-preview').eq(0);

        _this.$tabs.append(_this.$editor);
        _this.$tabs.append(_this.$preview);
        _this.editor.refresh();


        // Check if a max length is defined
        if(_this.textarea.hasAttribute('data-max-length') &&
            _this.textarea.getAttribute('data-max-length') !== ''){
            _this.limit = true;
            _this.countMaxLimit = parseInt(_this.textarea.getAttribute('data-max-length'));

            if (_this.$countCurrent.length &&
                _this.$countMaxLimitText.length &&
                _this.$count.length) {

                _this.$countCurrent[0].innerHTML = stripTags(_this.editor.getValue()).length;
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
            if(stripTags(_this.editor.getValue()).length > _this.countMaxLimit){
                _this.countAlertActive = true;
                addClass(_this.$cont[0], 'content-limit');
            }
            else if(stripTags(_this.editor.getValue()).length < _this.countMinLimit){
                _this.countAlertActive = true;
                addClass(_this.$cont[0], 'content-limit');
            }
            else _this.countAlertActive = false;
        }

        _this.editor.on('change', $.proxy(_this.textareaChange, _this));
        _this.editor.on('focus', $.proxy(_this.textareaFocus, _this));
        _this.editor.on('blur', $.proxy(_this.textareaBlur, _this));

        _this.editor.on('drop', $.proxy(_this.onDropFile, _this));
        _this.$buttonPreview.on('click', $.proxy(_this.buttonPreviewClick, _this));

        _this.$buttons.on('click', $.proxy(_this.buttonClick, _this));
        Rozier.$window.on('keyup', $.proxy(_this.echapKey, _this));

        var forceEditorUpdateProxy = $.proxy(_this.forceEditorUpdate, _this);
        setTimeout(function () {
            $('[data-uk-switcher]').on('show.uk.switcher', forceEditorUpdateProxy);
            _this.forceEditorUpdate();
        }, 300);
    }
};

MarkdownEditor.prototype.onDropFile = function(editor, event) {
    var _this = this;

    event.preventDefault(event);

    for (var i = 0; i < event.dataTransfer.files.length; i++) {
        Rozier.lazyload.canvasLoader.show();
        var file = event.dataTransfer.files[i];
        var formData = new FormData();
        formData.append('_token', Rozier.ajaxToken);
        formData.append('form[attachment]', file);

        $.ajax({
            url: Rozier.routes.documentsUploadPage,
            type: 'post',
            dataType: 'json',
            cache: false,
            data: formData,
            processData: false,
            contentType: false
        })
        .always($.proxy(_this.onDropFileUploaded, _this, editor));
    }
};

MarkdownEditor.prototype.onDropFileUploaded = function(editor, data) {
    var _this = this;

    Rozier.lazyload.canvasLoader.hide();

    if (data.success === true) {
        var mark = "![" + data.thumbnail.filename + "](" + data.thumbnail.large + ")";

        editor.replaceSelection(mark);
    }
};

MarkdownEditor.prototype.forceEditorUpdate = function(event) {
    var _this = this;
    _this.editor.refresh();

    if (_this.usePreview) {
        _this.$preview.html(marked(_this.editor.getValue()));
    }
};

MarkdownEditor.prototype.buttonClick = function(event) {
    var _this = this;
    var $button = $(event.currentTarget);
    var sel = _this.editor.getSelections();

    if (sel.length > 0) {
        switch($button.attr('data-markdowneditor-button')){
            case 'nbsp':
                _this.editor.replaceSelections(_this.nbspSelections(sel));
            break;
            case 'listUl':
                _this.editor.replaceSelections(_this.listUlSelections(sel));
            break;
            case 'link':
                _this.editor.replaceSelections(_this.linkSelections(sel));
            break;
            case 'image':
                _this.editor.replaceSelections(_this.imageSelections(sel));
            break;
            case 'bold':
                _this.editor.replaceSelections(_this.boldSelections(sel));
            break;
            case 'italic':
                _this.editor.replaceSelections(_this.italicSelections(sel));
            break;
            case 'blockquote':
                _this.editor.replaceSelections(_this.blockquoteSelections(sel));
            break;
            case 'h2':
                _this.editor.replaceSelections(_this.h2Selections(sel));
            break;
            case 'h3':
                _this.editor.replaceSelections(_this.h3Selections(sel));
            break;
            case 'h4':
                _this.editor.replaceSelections(_this.h4Selections(sel));
            break;
            case 'h5':
                _this.editor.replaceSelections(_this.h5Selections(sel));
            break;
            case 'h6':
                _this.editor.replaceSelections(_this.h6Selections(sel));
            break;
            case 'back':
                _this.editor.replaceSelections(_this.backSelections(sel));
            break;
            case 'hr':
                _this.editor.replaceSelections(_this.hrSelections(sel));
            break;
        }

        /*
         * Pos cursor after last selection
         */
        _this.editor.focus();
    }
};
MarkdownEditor.prototype.backSelections = function(selections) {
    var _this = this;
    for(var i in selections) {
        selections[i] = '   \n';
    }
    return selections;
};
MarkdownEditor.prototype.hrSelections = function(selections) {
    var _this = this;
    for(var i in selections) {
        selections[i] = '\n\n---\n\n';
    }
    return selections;
};
MarkdownEditor.prototype.nbspSelections = function(selections) {
    var _this = this;
    for(var i in selections) {
        selections[i] = ' ';
    }
    return selections;
};
MarkdownEditor.prototype.listUlSelections = function(selections) {
    var _this = this;
    for(var i in selections) {
        selections[i] = '\n\n* '+selections[i]+'\n\n';
    }
    return selections;
};
MarkdownEditor.prototype.linkSelections = function(selections) {
    var _this = this;
    for(var i in selections) {
        selections[i] = '['+selections[i]+'](http://)';
    }
    return selections;
};
MarkdownEditor.prototype.imageSelections = function(selections) {
    var _this = this;
    if (!selections) {
        selections = _this.editor.getSelections();
    }
    for(var i in selections) {
        selections[i] = '!['+selections[i]+'](/files/)';
    }
    return selections;
};
MarkdownEditor.prototype.boldSelections = function(selections) {
    var _this = this;
    if (!selections) {
        selections = _this.editor.getSelections();
    }
    for(var i in selections) {
        selections[i] = '**'+selections[i]+'**';
    }
    return selections;
};
MarkdownEditor.prototype.italicSelections = function(selections) {
    var _this = this;
    if (!selections) {
        selections = _this.editor.getSelections();
    }
    for(var i in selections) {
        selections[i] = '*'+selections[i]+'*';
    }
    return selections;
};
MarkdownEditor.prototype.h2Selections = function(selections) {
    var _this = this;
    for(var i in selections) {
        selections[i] = '\n## '+selections[i]+'\n';
    }
    return selections;
};
MarkdownEditor.prototype.h3Selections = function(selections) {
    var _this = this;
    for(var i in selections) {
        selections[i] = '\n### '+selections[i]+'\n';
    }
    return selections;
};
MarkdownEditor.prototype.h4Selections = function(selections) {
    var _this = this;
    for(var i in selections) {
        selections[i] = '\n#### '+selections[i]+'\n';
    }
    return selections;
};
MarkdownEditor.prototype.h5Selections = function(selections) {
    var _this = this;
    for(var i in selections) {
        selections[i] = '\n##### '+selections[i]+'\n';
    }
    return selections;
};
MarkdownEditor.prototype.h6Selections = function(selections) {
    var _this = this;
    for(var i in selections) {
        selections[i] = '\n###### '+selections[i]+'\n';
    }
    return selections;
};
MarkdownEditor.prototype.blockquoteSelections = function(selections) {
    var _this = this;
    for(var i in selections) {
        selections[i] = '\n> '+selections[i]+'\n';
    }
    return selections;
};

/**
 * Textarea change
 * @return {[type]} [description]
 */
MarkdownEditor.prototype.textareaChange = function(e){
    var _this = this;

    _this.editor.save();

    if (_this.usePreview) {
        clearTimeout(_this.refreshPreviewTimeout);
        _this.refreshPreviewTimeout = setTimeout(function () {
            _this.$preview.html(marked(_this.editor.getValue()));
        }, 100);
    }

    if(_this.limit){
        setTimeout(function(){
            var textareaVal = _this.editor.getValue();
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

   _this.$cont.addClass('form-col-focus');
};


/**
 * Textarea focus out
 * @return {[type]} [description]
 */
MarkdownEditor.prototype.textareaBlur = function(e){
    var _this = this;

    _this.$cont.removeClass('form-col-focus');
};


/**
 * Button preview click
 * @return {[type]} [description]
 */
MarkdownEditor.prototype.buttonPreviewClick = function(e){
    var _this = this;
    e.preventDefault();

    var width = _this.$preview.outerWidth();

    if (_this.usePreview) {
        _this.closePreview();
    } else {
        _this.usePreview = true;
        _this.$buttonPreview.addClass('uk-active active');
        _this.$preview.addClass('active');
        _this.forceEditorUpdate();

        TweenLite.fromTo(_this.$preview, 1, {x: width*-1, opacity: 0}, {x: 0, ease: Expo.easeOut, opacity: 1});

        Rozier.$window.on('keyup', _this.closePreviewProxy);
    }
};

/**
 *
 */
MarkdownEditor.prototype.closePreview = function(e) {
    var _this = this;

    if (e) {
        if (e.keyCode == 27) {
            e.preventDefault();
        } else {
            return;
        }
    }
    var width = _this.$preview.outerWidth();
    Rozier.$window.off('keyup', _this.closePreviewProxy);
    _this.usePreview = false;
    _this.$buttonPreview.removeClass('uk-active active');
    TweenLite.fromTo(_this.$preview, 1, {x: 0, opacity: 1}, {x: width*-1, opacity: 0, ease: Expo.easeOut, onComplete: function(){
        _this.$preview.removeClass('active');
    }});
};


/**
 * Window resize callback
 * @return {[type]} [description]
 */
MarkdownEditor.prototype.resize = function(){
    var _this = this;
};

