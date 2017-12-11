import $ from 'jquery'
import Dropzone from 'dropzone'
import {
    isset
} from '../plugins'

export default function DocumentUploader (options) {
    var _this = this

    _this.options = {
        'onSuccess': function (data) {},
        'onError': function (data) {},
        'onAdded': function (file) {},
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
        $.extend(_this.options, options)
    }
    if ($(_this.options.selector).length) {
        _this.init()
    }
};
DocumentUploader.prototype.init = function () {
    var _this = this

    /*
     * Get folder id
     */
    var form = $('#upload-dropzone-document')
    if (isset(form.attr('data-folder-id')) &&
        form.attr('data-folder-id') > 0) {
        _this.options.headers.folderId = parseInt(form.attr('data-folder-id'))
        _this.options.url = window.Rozier.routes.documentsUploadPage + '/' + parseInt(form.attr('data-folder-id'))
    }

    Dropzone.options.uploadDropzoneDocument = {
        url: _this.options.url,
        method: 'post',
        headers: _this.options.headers,
        paramName: _this.options.paramName,
        uploadMultiple: _this.options.uploadMultiple,
        maxFilesize: _this.options.maxFilesize,
        dictDefaultMessage: _this.options.dictDefaultMessage,
        dictFallbackMessage: _this.options.dictFallbackMessage,
        dictFallbackText: _this.options.dictFallbackText,
        dictFileTooBig: _this.options.dictFileTooBig,
        dictInvalidFileType: _this.options.dictInvalidFileType,
        dictResponseError: _this.options.dictResponseError,
        dictCancelUpload: _this.options.dictCancelUpload,
        dictCancelUploadConfirmation: _this.options.dictCancelUploadConfirmation,
        dictRemoveFile: _this.options.dictRemoveFile,
        dictRemoveFileConfirmation: _this.options.dictRemoveFileConfirmation,
        dictMaxFilesExceeded: _this.options.dictMaxFilesExceeded,
        init: function () {
            this.on('addedfile', function (file, data) {
                _this.options.onAdded(file)
            })
            this.on('success', function (file, data) {
                /*
                 * Remove previews after 3 sec not
                 * to bloat the dropzone when dragging more than
                 * 20 files…
                 */
                if (file.previewElement) {
                    var $preview = $(file.previewElement)
                    setTimeout(function () {
                        $preview.fadeOut(500, function () {
                            $preview.remove()
                        })
                    }, 3000)
                }
                _this.options.onSuccess(data)
                window.Rozier.getMessages()
            })
            this.on('canceled', function (file, data) {
                _this.options.onError(JSON.parse(data))
                window.Rozier.getMessages()
            })
            this.on('error', function (file, errorMessage, xhr) {
                console.log(errorMessage)
            })
        }
    }

    Dropzone.autoDiscover = _this.options.autoDiscover

    /* eslint-disable no-new */
    new Dropzone(_this.options.selector, Dropzone.options.uploadDropzoneDocument)

    var $dzMessage = $(_this.options.selector).find('.dz-message')

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
