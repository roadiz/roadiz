var DocumentUploader = function (options) {
    var _this = this;

    _this.options = {
        'onSuccess' : function (data) {
            console.log("Success file");
            console.log(data);
        },
        'onError' : function (data) {
            console.log("Failed file");
            console.log(data);
        },
        'onAdded' : function (file) {
            console.log("Added file");
        },
        'selector':      "#upload-dropzone-document",
        'paramName':     "form[attachment]",
        'uploadMultiple':false,
        'maxFilesize':   64,
        'autoDiscover':  false,
        'headers': {"_token": Rozier.ajaxToken}
    };

    if (typeof options !== "undefined") {
        $.extend( _this.options, options );
    }
    if ($(_this.options.selector).length) {
        _this.init();
    }
};
DocumentUploader.prototype.options = null;
DocumentUploader.prototype.init = function() {
    var _this = this;

    Dropzone.options.uploadDropzoneDocument = {
        url: Rozier.routes.documentsUploadPage,
        method:'post',
        headers:_this.options.headers,
        paramName: _this.options.paramName,
        uploadMultiple: _this.options.uploadMultiple,
        maxFilesize: _this.options.maxFilesize,
        init: function() {
            this.on("addedfile", function(file, data) {
                _this.options.onAdded(file);
            });
            this.on("success", function(file, data) {
                _this.options.onSuccess(JSON.parse(data));
                Rozier.getMessages();
            });
            this.on("canceled", function(file, data) {
                _this.options.onError(JSON.parse(data));
                Rozier.getMessages();
            });
        }
    };
    Dropzone.autoDiscover = _this.options.autoDiscover;
    var dropZone = new Dropzone(
        _this.options.selector,
        Dropzone.options.uploadDropzoneDocument
    );
};