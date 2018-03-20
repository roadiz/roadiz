import $ from 'jquery'
import Dropzone from 'dropzone'

/**
 * Document uploader
 */
export default class DocumentUploader {
    /**
     * Constructor
     * @param {Object} options
     */
    constructor (options) {
        this.options = {
            'onSuccess': (data) => {},
            'onError': (data) => {},
            'onAdded': (file) => {},
            'url': window.Rozier.routes.documentsUploadPage,
            'selector': '#upload-dropzone-document',
            'paramName': 'form[attachment]',
            'uploadMultiple': false,
            'maxFilesize': 64,
            'autoDiscover': false,
            'headers': {'_token': window.Rozier.ajaxToken},
            'dictDefaultMessage': 'Drop files here to upload or click to open your explorer',
            'dictFallbackMessage': "Your browser does not support drag'n'drop file uploads.",
            'dictFallbackText': 'Please use the fallback form below to upload your files like in the olden days.',
            'dictFileTooBig': 'File is too big ({{filesize}}MiB). Max filesize: {{maxFilesize}}MiB.',
            'dictInvalidFileType': "You can't upload files of this type.",
            'dictResponseError': 'Server responded with {{statusCode}} code.',
            'dictCancelUpload': 'Cancel upload',
            'dictCancelUploadConfirmation': 'Are you sure you want to cancel this upload?',
            'dictRemoveFile': 'Remove file',
            'dictRemoveFileConfirmation': null,
            'dictMaxFilesExceeded': 'You can not upload any more files.'
        }

        if (typeof options !== 'undefined') {
            $.extend(this.options, options)
        }

        if ($(this.options.selector).length) {
            this.init()
        }
    }

    init () {
        // Get folder id
        let form = $('#upload-dropzone-document')

        if (form.attr('data-folder-id') && form.attr('data-folder-id') > 0) {
            this.options.headers.folderId = parseInt(form.attr('data-folder-id'))
            this.options.url = window.Rozier.routes.documentsUploadPage + '/' + parseInt(form.attr('data-folder-id'))
        }

        Dropzone.options.uploadDropzoneDocument = {
            url: this.options.url,
            method: 'post',
            headers: this.options.headers,
            paramName: this.options.paramName,
            uploadMultiple: this.options.uploadMultiple,
            maxFilesize: this.options.maxFilesize,
            dictDefaultMessage: this.options.dictDefaultMessage,
            dictFallbackMessage: this.options.dictFallbackMessage,
            dictFallbackText: this.options.dictFallbackText,
            dictFileTooBig: this.options.dictFileTooBig,
            dictInvalidFileType: this.options.dictInvalidFileType,
            dictResponseError: this.options.dictResponseError,
            dictCancelUpload: this.options.dictCancelUpload,
            dictCancelUploadConfirmation: this.options.dictCancelUploadConfirmation,
            dictRemoveFile: this.options.dictRemoveFile,
            dictRemoveFileConfirmation: this.options.dictRemoveFileConfirmation,
            dictMaxFilesExceeded: this.options.dictMaxFilesExceeded,
            init: function () {
                this.on('addedfile', function (file, data) {
                    this.options.onAdded(file)
                })

                this.on('success', function (file, data) {
                    /*
                     * Remove previews after 3 sec not
                     * to bloat the dropzone when dragging more than
                     * 20 files…
                     */
                    if (file.previewElement) {
                        let $preview = $(file.previewElement)
                        window.setTimeout(function () {
                            $preview.fadeOut(500, function () {
                                $preview.remove()
                            })
                        }, 3000)
                    }
                    this.options.onSuccess(data)
                    window.Rozier.getMessages()
                })

                this.on('canceled', function (file, data) {
                    this.options.onError(JSON.parse(data))
                    window.Rozier.getMessages()
                })

                this.on('error', (file, errorMessage, xhr) => {
                    console.log(errorMessage)
                })
            }
        }

        Dropzone.autoDiscover = this.options.autoDiscover

        /* eslint-disable no-new */
        new Dropzone(this.options.selector, Dropzone.options.uploadDropzoneDocument)

        let $dzMessage = $(this.options.selector).find('.dz-message')

        $dzMessage.append(`
        <div class="circles-icons">
            <div class="circle circle-1"></div>
            <div class="circle circle-2"></div>
            <div class="circle circle-3"></div>
            <div class="circle circle-4"></div>
            <div class="circle circle-5"></div>
            <i class="uk-icon-rz-file"></i>
        </div>`)
    }

    unbind () {

    }
}
