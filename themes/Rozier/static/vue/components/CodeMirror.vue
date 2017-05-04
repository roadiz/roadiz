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
                default: function () {
                    return {
                        mode: 'yaml',
                        lineNumbers: true,
                        theme: "mbo",
                        tabSize: 2,
                        lineWrapping: true,
                        dragDrop: false
                    }
                }
            },
        },
        ready: function () {
            this.editor = CodeMirror.fromTextArea(this.$el, this.options)
            this.editor.setValue(this.initialValue)
            this.editor.on('change', (cm) => {
                this.value = cm.getValue()
                if (!!this.$emit) {
                    this.$emit('change', cm.getValue())
                }
            })
        },
        mounted: function () {
            this.editor = CodeMirror.fromTextArea(this.$el, this.options)
            this.editor.setValue(this.initialValue)
            this.editor.on('change', (cm) => {
                if (!!this.$emit) {
                    this.$emit('change', cm.getValue())
                    this.$emit('input', cm.getValue())
                }
            })
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
        }
    }
</script>
