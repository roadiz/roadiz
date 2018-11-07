<template>
    <div :action="url" class="vue-dropzone dropzone-container" :id="id"></div>
</template>

<script>
import $ from 'jquery'

export default {
    props: {
        id: {
            type: String,
            required: true
        },
        url: {
            type: String,
            required: true
        },
        clickable: {
            type: Boolean,
            default: true
        },
        acceptedFileTypes: {
            type: String
        },
        thumbnailHeight: {
            type: Number,
            default: 80
        },
        thumbnailWidth: {
            type: Number,
            default: 80
        },
        showRemoveLink: {
            type: Boolean,
            default: true
        },
        maxFileSizeInMB: {
            type: Number,
            default: 64
        },
        maxNumberOfFiles: {
            type: Number,
            default: 5
        },
        autoProcessQueue: {
            type: Boolean,
            default: true
        },
        useFontAwesome: {
            type: Boolean,
            default: false
        },
        headers: {
            type: Object
        },
        language: {
            type: Object
        },
        useCustomDropzoneOptions: {
            type: Boolean,
            default: false
        },
        dropzoneOptions: {
            type: Object
        }
    },
    methods: {
        setOption: function (option, value) {
            this.dropzone.options[option] = value
        },
        removeAllFiles: function () {
            this.dropzone.removeAllFiles(true)
        },
        processQueue: function () {
            this.dropzone.processQueue()
        },
        removeFile: function (file) {
            this.dropzone.removeFile(file)
        }
    },
    computed: {
        doneIcon: function () {
            if (this.useFontAwesome) {
                return '<i class="fa fa-check"></i>'
            } else {
                return  ' <i class="material-icons">done</i>' // eslint-disable-line
            }
        },
        errorIcon: function () {
            if (this.useFontAwesome) {
                return '<i class="fa fa-close"></i>'
            } else {
                return  ' <i class="material-icons">error</i>' // eslint-disable-line
            }
        }
    },
    mounted () {
        if (this.$isServer) {
            return
        }

        let Dropzone = require('dropzone')
        let element = document.getElementById(this.id)

        Dropzone.autoDiscover = false

        if (!this.useCustomDropzoneOptions) {
            this.dropzone = new Dropzone(element, {
                clickable: this.clickable,
                method: 'post',
                thumbnailWidth: this.thumbnailWidth,
                thumbnailHeight: this.thumbnailHeight,
                maxFiles: this.maxNumberOfFiles,
                maxFilesize: this.maxFileSizeInMB,
                uploadMultiple: false,
                acceptedFiles: this.acceptedFileTypes,
                timeout: 0, // no timeout
                autoProcessQueue: this.autoProcessQueue,
                paramName: 'form[attachment]',
                headers: { _token: window.Rozier.ajaxToken, ...this.headers },
                dictDefaultMessage: this.language.dictDefaultMessage,
                dictCancelUpload: this.language.dictCancelUpload,
                dictCancelUploadConfirmation: this.language.dictCancelUploadConfirmation,
                dictFallbackMessage: this.language.dictFallbackMessage,
                dictFallbackText: this.language.dictFallbackText,
                dictFileTooBig: this.language.dictFileTooBig,
                dictInvalidFileType: this.language.dictInvalidFileType,
                dictMaxFilesExceeded: this.language.dictMaxFilesExceeded,
                dictRemoveFile: this.language.dictRemoveFile,
                dictRemoveFileConfirmation: this.language.dictRemoveFileConfirmation,
                dictResponseError: this.language.dictResponseError,
                error: (file, response) => {
                    let errorMessage

                    if (response.error) {
                        errorMessage = response.error
                    } else {
                        errorMessage = 'Error'
                    }

                    file.previewElement.classList.add('dz-error')
                    let ref = file.previewElement.querySelectorAll('[data-dz-errormessage]')
                    let results = []

                    for (let i = 0, len = ref.length; i < len; i++) {
                        let node = ref[i]
                        results.push(node.textContent = errorMessage)
                    }

                    return results
                }
            })

            let $dropzone = $(element)
            $dropzone.append(`<div class="dz-default dz-message"><span>${this.language.dictDefaultMessage}</span></div>`)
            let $dzMessage = $dropzone.find('.dz-message')
            $dzMessage.append(`<div class="circles-icons"><div class="circle circle-1"></div><div class="circle circle-2"></div><div class="circle circle-3"></div><div class="circle circle-4"></div><div class="circle circle-5"></div><i class="uk-icon-rz-file"></i></div>`)
        } else {
            this.dropzone = new Dropzone(element, this.dropzoneOptions)
        }
        // Handle the dropzone events
        const vm = this

        this.dropzone.on('thumbnail', function (file) {
            vm.$emit('vdropzone-thumbnail', file)
        })

        this.dropzone.on('addedfile', function (file) {
            vm.$emit('vdropzone-file-added', file)
        })

        this.dropzone.on('removedfile', function (file) {
            vm.$emit('vdropzone-removed-file', file)
        })

        this.dropzone.on('success', function (file, response) {
            vm.$emit('vdropzone-success', file, response)

            /*
             * Remove previews after 3 sec not
             * to bloat the dropzone when dragging more than
             * 20 files…
             */
            if (file.previewElement) {
                let $preview = $(file.previewElement)
                setTimeout(function () {
                    $preview.fadeOut(500)
                }, 3000)
            }
        })

        this.dropzone.on('successmultiple', function (file, response) {
            vm.$emit('vdropzone-success-multiple', file, response)
        })

        this.dropzone.on('error', function (file, error, xhr) {
            vm.$emit('vdropzone-error', file, error, xhr)
        })

        this.dropzone.on('sending', function (file, xhr, formData) {
            vm.$emit('vdropzone-sending', file, xhr, formData)

            xhr.ontimeout = () => {
                console.error('Server Timeout')
            }
        })

        this.dropzone.on('sendingmultiple', function (file, xhr, formData) {
            vm.$emit('vdropzone-sending-multiple', file, xhr, formData)
        })

        this.dropzone.on('queuecomplete', function (file, xhr, formData) {
            vm.$emit('vdropzone-queue-complete', file, xhr, formData)
        })
    },

    beforeDestroy () {
        this.dropzone.destroy()
    }
}
</script>
