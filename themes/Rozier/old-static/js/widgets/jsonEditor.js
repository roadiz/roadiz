/**
 * Json Editor
 */

JsonEditor = function($textarea, index){
    var _this = this;

    _this.$textarea = $textarea;
    _this.textarea = _this.$textarea[0];
    _this.$cont = _this.$textarea.parents('.uk-form-row').eq(0);
    _this.$settingRow = _this.$textarea.parents('.setting-row').eq(0);

    var options = {
        lineNumbers: true,
        mode: {name: "javascript", json: true},
        theme: "mbo",
        tabSize: 2,
        lineWrapping: true,
        dragDrop: false
    };

    if (_this.$settingRow.length) {
        options.lineNumbers = false;
    }

    _this.editor = CodeMirror.fromTextArea(_this.textarea, options);

    // Methods
    _this.init();
};

/**
 * Init
 * @return {[type]} [description]
 */
JsonEditor.prototype.init = function(){
    var _this = this;

    if(_this.$textarea.length) {
        _this.editor.on('change', $.proxy(_this.textareaChange, _this));
        _this.editor.on('focus', $.proxy(_this.textareaFocus, _this));
        _this.editor.on('blur', $.proxy(_this.textareaBlur, _this));

        var forceEditorUpdateProxy = $.proxy(_this.forceEditorUpdate, _this);
        setTimeout(function () {
            $('[data-uk-switcher]').on('show.uk.switcher', forceEditorUpdateProxy);
            _this.forceEditorUpdate();
        }, 300);
    }
};


JsonEditor.prototype.forceEditorUpdate = function(event) {
    var _this = this;
    //console.log('Refresh Json editor');
    _this.editor.refresh();
};

/**
 * Textarea change
 * @return {[type]} [description]
 */
JsonEditor.prototype.textareaChange = function(e){
    var _this = this;

    _this.editor.save();

    if(_this.limit){
        setTimeout(function(){
            var textareaVal = _this.editor.getValue();
            var textareaValStripped = stripTags(textareaVal);
            var textareaValLength = textareaValStripped.length;
        }, 100);
    }

};

/**
 * Textarea focus
 * @return {[type]} [description]
 */
JsonEditor.prototype.textareaFocus = function(e){
    var _this = this;

   _this.$cont.addClass('form-col-focus');
};


/**
 * Textarea focus out
 * @return {[type]} [description]
 */
JsonEditor.prototype.textareaBlur = function(e){
    var _this = this;

    _this.$cont.removeClass('form-col-focus');
};


/**
 * Window resize callback
 * @return {[type]} [description]
 */
JsonEditor.prototype.resize = function(){
    var _this = this;
};

