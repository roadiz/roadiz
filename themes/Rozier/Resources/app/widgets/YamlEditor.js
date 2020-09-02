import $ from 'jquery'

/**
 * Yaml editor
 */
export default class YamlEditor {
    /**
     * Yaml editor constructor
     * @param $textarea
     * @param index
     */
    constructor ($textarea, index) {
        this.$textarea = $textarea
        this.textarea = this.$textarea[0]
        this.$cont = this.$textarea.parents('.uk-form-row').eq(0)
        this.$settingRow = this.$textarea.parents('.setting-row').eq(0)
        this.tabSize = 4

        let rulers = []
        for (let i = 1; i < 19; i++) {
            rulers.push({
                column: this.tabSize * i,
                lineStyle: 'dashed',
                width: '1px',
                color: 'rgba(0,255,255,0.1)'
            })
        }

        let options = {
            lineNumbers: true,
            mode: 'yaml',
            theme: 'mbo',
            tabSize: this.tabSize,
            indentUnit: this.tabSize,
            indentWithTabs: false,
            lineWrapping: true,
            rulers: rulers,
            smartIndent: true,
            dragDrop: false,
            readOnly: (this.textarea.hasAttribute('disabled') && this.textarea.getAttribute('disabled') === 'disabled'),
            extraKeys: {
                Tab: 'indentMore',
                'Shift-Tab': 'indentLess'
            }
        }

        if (this.$settingRow.length) {
            options.lineNumbers = false
        }

        this.editor = window.CodeMirror.fromTextArea(this.textarea, options)

        // Bind methods
        this.textareaChange = this.textareaChange.bind(this)
        this.textareaFocus = this.textareaFocus.bind(this)
        this.textareaBlur = this.textareaBlur.bind(this)
        this.forceEditorUpdate = this.forceEditorUpdate.bind(this)

        // Init
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

            window.setTimeout(() => {
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
