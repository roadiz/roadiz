import $ from 'jquery'

/**
 * Css Editor
 */
export default class CssEditor {
    /**
     * @param $textarea
     * @param index
     */
    constructor ($textarea, index) {
        this.$textarea = $textarea
        this.textarea = this.$textarea[0]
        this.$cont = this.$textarea.parents('.uk-form-row').eq(0)
        this.$settingRow = this.$textarea.parents('.setting-row').eq(0)

        let options = {
            lineNumbers: true,
            mode: 'css',
            theme: 'mbo',
            tabSize: 2,
            lineWrapping: true,
            dragDrop: false
        }

        if (this.$settingRow.length) {
            options.lineNumbers = false
        }

        this.editor = window.CodeMirror.fromTextArea(this.textarea, options)

        this.forceEditorUpdate = this.forceEditorUpdate.bind(this)
        this.textareaChange = this.textareaChange.bind(this)
        this.textareaFocus = this.textareaFocus.bind(this)
        this.textareaBlur = this.textareaBlur.bind(this)

        // Methods
        this.init()
    }

    /**
     * Init
     */
    init () {
        if (this.$textarea.length) {
            this.editor.on('change', this.textareaChange)
            this.editor.on('focus', this.textareaFocus)
            this.editor.on('blur', this.textareaBlur)

            setTimeout(() => {
                $('[data-uk-switcher]').on('show.uk.switcher', this.forceEditorUpdate)
                this.forceEditorUpdate()
            }, 300)
        }
    }

    forceEditorUpdate () {
        this.editor.refresh()
    }

    /**
     * Textarea change
     */
    textareaChange () {
        this.editor.save()

        // if (this.limit) {
        //     setTimeout(function () {
        //         let textareaVal = this.editor.getValue()
        //         let textareaValStripped = stripTags(textareaVal)
        //         let textareaValLength = textareaValStripped.length
        //     }, 100)
        // }
    }

    /**
     * Textarea focus
     */
    textareaFocus () {
        this.$cont.addClass('form-col-focus')
    }

    /**
     * Textarea focus out
     */
    textareaBlur () {
        this.$cont.removeClass('form-col-focus')
    }

    /**
     * Window resize callback
     */
    resize () {}
}
