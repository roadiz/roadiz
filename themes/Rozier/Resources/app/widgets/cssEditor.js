import $ from 'jquery'

/**
 * Css Editor
 */
export default function CssEditor ($textarea, index) {
    var _this = this

    _this.$textarea = $textarea
    _this.textarea = _this.$textarea[0]
    _this.$cont = _this.$textarea.parents('.uk-form-row').eq(0)
    _this.$settingRow = _this.$textarea.parents('.setting-row').eq(0)

    var options = {
        lineNumbers: true,
        mode: 'css',
        theme: 'mbo',
        tabSize: 2,
        lineWrapping: true,
        dragDrop: false
    }

    if (_this.$settingRow.length) {
        options.lineNumbers = false
    }

    _this.editor = window.CodeMirror.fromTextArea(_this.textarea, options)

    // Methods
    _this.init()
};

/**
 * Init
 * @return {[type]} [description]
 */
CssEditor.prototype.init = function () {
    var _this = this

    if (_this.$textarea.length) {
        _this.editor.on('change', $.proxy(_this.textareaChange, _this))
        _this.editor.on('focus', $.proxy(_this.textareaFocus, _this))
        _this.editor.on('blur', $.proxy(_this.textareaBlur, _this))

        var forceEditorUpdateProxy = $.proxy(_this.forceEditorUpdate, _this)
        setTimeout(function () {
            $('[data-uk-switcher]').on('show.uk.switcher', forceEditorUpdateProxy)
            _this.forceEditorUpdate()
        }, 300)
    }
}

CssEditor.prototype.forceEditorUpdate = function (event) {
    var _this = this
    // console.log('Refresh Css editor');
    _this.editor.refresh()
}

/**
 * Textarea change
 * @return {[type]} [description]
 */
CssEditor.prototype.textareaChange = function (e) {
    var _this = this

    _this.editor.save()

    // if (_this.limit) {
    //     setTimeout(function () {
    //         var textareaVal = _this.editor.getValue()
    //         var textareaValStripped = stripTags(textareaVal)
    //         var textareaValLength = textareaValStripped.length
    //     }, 100)
    // }
}

/**
 * Textarea focus
 * @return {[type]} [description]
 */
CssEditor.prototype.textareaFocus = function () {
    var _this = this

    _this.$cont.addClass('form-col-focus')
}

/**
 * Textarea focus out
 * @return {[type]} [description]
 */
CssEditor.prototype.textareaBlur = function () {
    var _this = this

    _this.$cont.removeClass('form-col-focus')
}

/**
 * Window resize callback
 * @return {[type]} [description]
 */
CssEditor.prototype.resize = function () {}
