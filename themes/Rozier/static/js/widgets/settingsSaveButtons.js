/**
 * SETTINGS SAVE BUTTONS
 */

SettingsSaveButtons = function(){
    var _this = this;

    // Selectors
    _this.$button = $('.uk-button-settings-save');
    _this.currentRequest = null;
    // Methods
    if(_this.$button.length) _this.init();

};


/**
 * Init
 * @return {[type]} [description]
 */
SettingsSaveButtons.prototype.init = function(){
    var _this = this;

    // Events
    _this.$button.off('click', $.proxy(_this.buttonClick, _this));
    _this.$button.on('click', $.proxy(_this.buttonClick, _this));

};


/**
 * Button click
 * @return {[type]} [description]
 */
SettingsSaveButtons.prototype.buttonClick = function(e){
    var _this = this;

    if(_this.currentRequest && _this.currentRequest.readyState != 4){
        _this.currentRequest.abort();
    }

    var $form = $(e.currentTarget).parent().parent().find('.uk-form').eq(0);

    if ($form.find('input[type=file]').length) {
        $form.submit();
        return false;
    }

    Rozier.lazyload.canvasLoader.show();
    var formData = new FormData($form[0]);
    var sendData = {
        url: window.location.href,
        type: 'post',
        data: formData,
        processData: false,
        cache : false,
        contentType: false
    };

    _this.currentRequest = $.ajax(sendData)
    .done(function() {
        console.log("Saved setting with success.");
    })
    .fail(function() {
        console.log("Error during save.");
    })
    .always(function() {
        Rozier.lazyload.canvasLoader.hide();
        Rozier.getMessages();
    });

    return false;

};


/**
 * Window resize callback
 * @return {[type]} [description]
 */
SettingsSaveButtons.prototype.resize = function(){
    var _this = this;

};
