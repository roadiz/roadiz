import $ from 'jquery'
import {
    stripTags,
    addClass
} from '../utils/plugins'
import {
    TweenLite,
    Expo
} from 'gsap'
import Markdownit from '../../../bower_components/markdown-it/dist/markdown-it'
import markdownItFootnote from '../../../bower_components/markdown-it-footnote/dist/markdown-it-footnote'

/**
 * Markdown Editor
 */
export default class MarkdownEditor {
    constructor ($textarea, index) {
        this.markdownit = new Markdownit()
        this.markdownit.use(markdownItFootnote)

        this.$textarea = $textarea
        this.textarea = this.$textarea[0]
        this.usePreview = false

        this.editor = window.CodeMirror.fromTextArea(this.textarea, {
            mode: 'gfm',
            lineNumbers: false,
            tabSize: 4,
            styleActiveLine: true,
            indentWithTabs: false,
            lineWrapping: true,
            viewportMargin: Infinity,
            enterMode: 'keep',
            direction: (this.textarea.hasAttribute('dir') && this.textarea.getAttribute('dir') === 'rtl') ? ('rtl') : ('ltr'),
            readOnly: (this.textarea.hasAttribute('disabled') && this.textarea.getAttribute('disabled') === 'disabled')
        })

        this.editor.addKeyMap({
            'Ctrl-B': (cm) => {
                cm.replaceSelections(this.boldSelections())
            },
            'Ctrl-I': (cm) => {
                cm.replaceSelections(this.italicSelections())
            },
            'Cmd-B': (cm) => {
                cm.replaceSelections(this.boldSelections())
            },
            'Cmd-I': (cm) => {
                cm.replaceSelections(this.italicSelections())
            }
        })

        // Selectors
        this.$cont = this.$textarea.parents('.uk-form-row').eq(0)
        this.$parentForm = this.$textarea.parents('form').eq(0)
        this.index = index
        this.$buttonCode = null
        this.$buttonPreview = null
        this.$buttonFullscreen = null
        this.$count = null
        this.$countCurrent = null
        this.limit = 0
        this.countMinLimit = 0
        this.countMaxLimit = 0
        this.$countMaxLimitText = null
        this.countAlertActive = false
        this.fullscreenActive = false

        this.closePreview = this.closePreview.bind(this)
        this.textareaChange = this.textareaChange.bind(this)
        this.textareaFocus = this.textareaFocus.bind(this)
        this.textareaBlur = this.textareaBlur.bind(this)
        this.onDropFile = this.onDropFile.bind(this)
        this.buttonPreviewClick = this.buttonPreviewClick.bind(this)
        this.buttonClick = this.buttonClick.bind(this)
        this.forceEditorUpdate = this.forceEditorUpdate.bind(this)

        // Methods
        this.init()
    }

    /**
     * Init
     * @return {[type]} [description]
     */
    init () {
        this.editor.on('change', this.textareaChange)

        if (this.$cont.length &&
            this.$textarea.length) {
            this.$editor = this.$cont.find('.CodeMirror').eq(0)

            this.$cont.addClass('markdown-editor')
            if (this.editor.getOption('readOnly') === true) {
                this.$cont.addClass('markdown-editor__disabled')
            }
            this.$buttons = this.$cont.find('[data-markdowneditor-button]')
            // Selectors
            this.$content = this.$cont.find('.markdown-editor-content')
            this.$buttonCode = this.$cont.find('.markdown-editor-button-code')
            this.$buttonPreview = this.$cont.find('.markdown-editor-button-preview')
            this.$buttonFullscreen = this.$cont.find('.markdown-editor-button-fullscreen')
            this.$count = this.$cont.find('.markdown-editor-count')
            this.$countCurrent = this.$cont.find('.count-current')
            this.$countMaxLimitText = this.$cont.find('.count-limit')

            // Store markdown index into datas
            this.$cont.find('.markdown-editor-button-code').attr('data-index', this.index)
            this.$cont.find('.markdown-editor-button-preview').attr('data-index', this.index)
            this.$cont.find('.markdown-editor-button-fullscreen').attr('data-index', this.index)
            this.$cont.find('.markdown_textarea').attr('data-index', this.index)
            this.$editor.attr('data-index', this.index)

            /*
             * Create preview tab.
             */
            this.$editor.before('<div class="markdown-editor-tabs">')
            this.$tabs = this.$cont.find('.markdown-editor-tabs').eq(0)

            this.$editor.after('<div class="markdown-editor-preview">')
            this.$preview = this.$cont.find('.markdown-editor-preview').eq(0)

            this.$tabs.append(this.$editor)
            this.$tabs.append(this.$preview)
            this.editor.refresh()

            // Check if a max length is defined
            if (this.textarea.hasAttribute('data-max-length') &&
                this.textarea.getAttribute('data-max-length') !== '') {
                this.limit = true
                this.countMaxLimit = parseInt(this.textarea.getAttribute('data-max-length'))

                if (this.$countCurrent.length &&
                    this.$countMaxLimitText.length &&
                    this.$count.length) {
                    this.$countCurrent[0].innerHTML = stripTags(this.editor.getValue()).length
                    this.$countMaxLimitText[0].innerHTML = this.textarea.getAttribute('data-max-length')
                    this.$count[0].style.display = 'block'
                }
            }

            if (this.textarea.hasAttribute('data-min-length') &&
                this.textarea.getAttribute('data-min-length') !== '') {
                this.limit = true
                this.countMinLimit = parseInt(this.textarea.getAttribute('data-min-length'))
            }

            if (this.textarea.hasAttribute('data-max-length') &&
                this.textarea.hasAttribute('data-min-length') &&
                this.textarea.getAttribute('data-min-length') === '' &&
                this.textarea.getAttribute('data-max-length') === '') {
                this.limit = false
                this.countMaxLimit = null
                this.countAlertActive = null
            }

            this.fullscreenActive = false

            if (this.limit) {
                // Check if current length is over limit
                if (stripTags(this.editor.getValue()).length > this.countMaxLimit) {
                    this.countAlertActive = true
                    addClass(this.$cont[0], 'content-limit')
                } else if (stripTags(this.editor.getValue()).length < this.countMinLimit) {
                    this.countAlertActive = true
                    addClass(this.$cont[0], 'content-limit')
                } else this.countAlertActive = false
            }

            this.editor.on('change', this.textareaChange)
            this.editor.on('focus', this.textareaFocus)
            this.editor.on('blur', this.textareaBlur)

            this.editor.on('drop', this.onDropFile)
            this.$buttonPreview.on('click', this.buttonPreviewClick)

            this.$buttons.on('click', this.buttonClick)

            window.setTimeout(() => {
                $('[data-uk-switcher]').on('show.uk.switcher', this.forceEditorUpdate)
                this.forceEditorUpdate()
            }, 300)
        }
    }

    onDropFile (editor, event) {
        let _this = this

        event.preventDefault(event)

        for (let i = 0; i < event.dataTransfer.files.length; i++) {
            window.Rozier.lazyload.canvasLoader.show()
            let file = event.dataTransfer.files[i]
            let formData = new FormData()
            formData.append('_token', window.Rozier.ajaxToken)
            formData.append('form[attachment]', file)

            $.ajax({
                url: window.Rozier.routes.documentsUploadPage,
                type: 'post',
                dataType: 'json',
                cache: false,
                data: formData,
                processData: false,
                contentType: false
            })
                .always($.proxy(this.onDropFileUploaded, _this, editor))
        }
    }

    onDropFileUploaded (editor, data) {
        window.Rozier.lazyload.canvasLoader.hide()

        if (data.success === true) {
            let mark = '![' + data.thumbnail.filename + '](' + data.thumbnail.large + ')'

            editor.replaceSelection(mark)
        }
    }

    forceEditorUpdate () {
        this.editor.refresh()

        if (this.usePreview) {
            this.$preview.html(this.markdownit.render(this.editor.getValue()))
        }
    }

    /**
     * @param {Event} event
     */
    buttonClick (event) {
        if (this.editor.getOption('readOnly') === true) {
            return false
        }
        let $button = $(event.currentTarget)
        let sel = this.editor.getSelections()

        if (sel.length > 0) {
            switch ($button.attr('data-markdowneditor-button')) {
            case 'nbsp':
                this.editor.replaceSelections(this.nbspSelections(sel))
                break
            case 'nb-hyphen':
                this.editor.replaceSelections(this.nbHyphenSelections(sel))
                break
            case 'listUl':
                this.editor.replaceSelections(this.listUlSelections(sel))
                break
            case 'link':
                this.editor.replaceSelections(this.linkSelections(sel))
                break
            case 'image':
                this.editor.replaceSelections(this.imageSelections(sel))
                break
            case 'bold':
                this.editor.replaceSelections(this.boldSelections(sel))
                break
            case 'italic':
                this.editor.replaceSelections(this.italicSelections(sel))
                break
            case 'blockquote':
                this.editor.replaceSelections(this.blockquoteSelections(sel))
                break
            case 'h2':
                this.editor.replaceSelections(this.h2Selections(sel))
                break
            case 'h3':
                this.editor.replaceSelections(this.h3Selections(sel))
                break
            case 'h4':
                this.editor.replaceSelections(this.h4Selections(sel))
                break
            case 'h5':
                this.editor.replaceSelections(this.h5Selections(sel))
                break
            case 'h6':
                this.editor.replaceSelections(this.h6Selections(sel))
                break
            case 'back':
                this.editor.replaceSelections(this.backSelections(sel))
                break
            case 'hr':
                this.editor.replaceSelections(this.hrSelections(sel))
                break
            }

            /*
             * Pos cursor after last selection
             */
            this.editor.focus()
        }
    }

    backSelections (selections) {
        for (let i in selections) {
            selections[i] = '   \n'
        }
        return selections
    }

    hrSelections (selections) {
        for (let i in selections) {
            selections[i] = '\n\n---\n\n'
        }
        return selections
    }

    nbspSelections (selections) {
        for (let i in selections) {
            selections[i] = ' '
        }
        return selections
    }

    nbHyphenSelections (selections) {
        for (let i in selections) {
            selections[i] = '‑'
        }
        return selections
    }

    listUlSelections (selections) {
        for (let i in selections) {
            selections[i] = '\n\n* ' + selections[i] + '\n\n'
        }
        return selections
    }

    linkSelections (selections) {
        for (let i in selections) {
            selections[i] = '[' + selections[i] + '](http://)'
        }
        return selections
    }

    imageSelections (selections) {
        if (!selections) {
            selections = this.editor.getSelections()
        }
        for (let i in selections) {
            selections[i] = '![' + selections[i] + '](/files/)'
        }
        return selections
    }

    boldSelections (selections) {
        if (!selections) {
            selections = this.editor.getSelections()
        }

        for (let i in selections) {
            selections[i] = '**' + selections[i] + '**'
        }

        return selections
    }

    italicSelections (selections) {
        if (!selections) {
            selections = this.editor.getSelections()
        }

        for (let i in selections) {
            selections[i] = '*' + selections[i] + '*'
        }

        return selections
    }

    h2Selections (selections) {
        for (let i in selections) {
            selections[i] = '\n## ' + selections[i] + '\n'
        }

        return selections
    }

    h3Selections (selections) {
        for (let i in selections) {
            selections[i] = '\n### ' + selections[i] + '\n'
        }

        return selections
    }

    h4Selections (selections) {
        for (let i in selections) {
            selections[i] = '\n#### ' + selections[i] + '\n'
        }

        return selections
    }

    h5Selections (selections) {
        for (let i in selections) {
            selections[i] = '\n##### ' + selections[i] + '\n'
        }

        return selections
    }

    h6Selections (selections) {
        for (let i in selections) {
            selections[i] = '\n###### ' + selections[i] + '\n'
        }

        return selections
    }

    blockquoteSelections (selections) {
        for (let i in selections) {
            selections[i] = '\n> ' + selections[i] + '\n'
        }

        return selections
    }

    /**
     * Textarea change
     */
    textareaChange () {
        this.editor.save()

        if (this.usePreview) {
            clearTimeout(this.refreshPreviewTimeout)
            this.refreshPreviewTimeout = window.setTimeout(() => {
                this.$preview.html(this.markdownit.render(this.editor.getValue()))
            }, 100)
        }

        if (this.limit) {
            window.setTimeout(() => {
                let textareaVal = this.editor.getValue()
                let textareaValStripped = stripTags(textareaVal)
                let textareaValLength = textareaValStripped.length

                this.$countCurrent.html(textareaValLength)

                if (textareaValLength > this.countMaxLimit) {
                    if (!this.countAlertActive) {
                        this.$cont.addClass('content-limit')
                        this.countAlertActive = true
                    }
                } else if (textareaValLength < this.countMinLimit) {
                    if (!this.countAlertActive) {
                        this.$cont.addClass('content-limit')
                        this.countAlertActive = true
                    }
                } else {
                    if (this.countAlertActive) {
                        this.$cont.removeClass('content-limit')
                        this.countAlertActive = false
                    }
                }
            }, 100)
        }
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
     * Button preview click
     */
    buttonPreviewClick (e) {
        e.preventDefault()

        let width = this.$preview.outerWidth()

        if (this.usePreview) {
            this.closePreview()
        } else {
            this.usePreview = true
            this.$buttonPreview.addClass('uk-active active')
            this.$preview.addClass('active')
            this.forceEditorUpdate()
            TweenLite.fromTo(this.$preview, 1, {x: width * -1, opacity: 0}, {x: 0, ease: Expo.easeOut, opacity: 1})
            window.Rozier.$window.on('keyup', this.closePreview)

            let openPreview = new CustomEvent('markdownPreviewOpen', {
                'detail': this
            })

            document.body.dispatchEvent(openPreview)
        }
    }

    /**
     *
     */
    closePreview (e) {
        if (e) {
            if (e.keyCode === 27) {
                e.preventDefault()
            } else {
                return
            }
        }

        let width = this.$preview.outerWidth()
        window.Rozier.$window.off('keyup', this.closePreview)
        this.usePreview = false
        this.$buttonPreview.removeClass('uk-active active')
        TweenLite.fromTo(this.$preview, 1, {x: 0, opacity: 1}, {x: width * -1,
            opacity: 0,
            ease: Expo.easeOut,
            onComplete: () => {
                this.$preview.removeClass('active')
            }})
    }

    /**
     * Window resize callback
     * @return {[type]} [description]
     */
    resize () {}
}
