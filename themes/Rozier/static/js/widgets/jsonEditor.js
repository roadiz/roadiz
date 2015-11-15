/**
 * Json Editor
 */

JsonEditor = function($textarea, index){
    var _this = this;

    _this.$textarea = $textarea;
    _this.textarea = _this.$textarea[0];
    _this.editor = CodeMirror.fromTextArea(_this.textarea, {
        lineNumbers: true,
        mode: {name: "javascript", json: true},
        theme: "monokai"
    });

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
    }
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
 * Window resize callback
 * @return {[type]} [description]
 */
JsonEditor.prototype.resize = function(){
    var _this = this;
};

