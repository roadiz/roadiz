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


SettingsSaveButtons.prototype.$button = null;


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

    $(e.currentTarget).parent().parent().find('.uk-form').submit();

    return false;

};


/**
 * Window resize callback
 * @return {[type]} [description]
 */
SettingsSaveButtons.prototype.resize = function(){
    var _this = this;

};