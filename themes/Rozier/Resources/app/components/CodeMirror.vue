<template>
    <textarea :name="name"></textarea>
</template>
<script>
    import CodeMirror from 'codemirror'
    import 'codemirror/lib/codemirror.css'
    import 'codemirror/mode/yaml/yaml.js'

    export default {
        props: {
            name: {
                type: String,
                required: true
            },
            initialValue: {
                type: String,
                default: ''
            },
            options: {
                type: Object,
                default () {
                    return {
                        mode: 'yaml',
                        lineNumbers: true,
                        theme: 'mbo',
                        tabSize: 4,
                        indentUnit: 4,
                        indentWithTabs: false,
                        lineWrapping: true,
                        dragDrop: false,
                        readOnly: false
                    }
                }
            }
        },
        ready () {
            this.build()
        },
        mounted () {
            this.build()
        },
        watch: {
            'value': function (newVal, oldVal) {
                let editorValue = this.editor.getValue()
                if (newVal !== editorValue) {
                    let scrollInfo = this.editor.getScrollInfo()
                    this.editor.setValue(newVal)
                    this.editor.scrollTo(scrollInfo.left, scrollInfo.top)
                }
            },
            'options': function (newOptions, oldVal) {
                if (typeof newOptions === 'object') {
                    for (let optionName in newOptions) {
                        if (newOptions.hasOwnProperty(optionName)) {
                            this.editor.setOption(optionName, newOptions[optionName])
                        }
                    }
                }
            }
        },
        beforeDestroy: function () {
            if (this.editor) {
                this.editor.doc.cm.getWrapperElement().remove()
            }
        },
        methods: {
            build () {
                this.editor = CodeMirror.fromTextArea(this.$el, this.options)
                this.editor.on('change', (cm) => {
                    this.value = cm.getValue()
                    if (!!this.$emit) { // eslint-disable-line
                        this.$emit('change', cm.getValue())
                        this.$emit('input', cm.getValue())
                    }
                })
                if (this.$el.hasAttribute('disabled')) {
                    // disabled codemirror.
                    this.editor.setOption('readOnly', true)
                }
                this.editor.setValue(this.initialValue)
                this.editor.refresh()
            }
        }
    }
</script>
