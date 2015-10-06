/**
 * SETTINGS SAVE BUTTONS
 */

SettingsSaveButtons = function(){
    var _this = this;

    // Selectors
    _this.$button = $('.uk-button-settings-save');

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

    var $form = $($(e.currentTarget).parent().parent().find('.uk-form')[0]);

    if ($form.find('input[type=file]').length) {
        $form.submit();
        return false;
    }

    Rozier.lazyload.canvasLoader.show();
    var formData = new FormData($form[0]);
    var sendData = {
        url: $form.attr('action'),
        type: 'post',
        data: formData,
        processData: false,
        cache : false,
        contentType: false
    };

    $.ajax(sendData)
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
