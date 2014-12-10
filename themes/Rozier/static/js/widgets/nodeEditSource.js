/**
 * NODE EDIT SOURCE
 */

NodeEditSource = function(){
    var _this = this;

    // Selectors
    _this.$content = $('.content-node-edit-source');

    // Methods
    if(_this.$content.length) _this.init();

};


NodeEditSource.prototype.$content = null;
NodeEditSource.prototype.$input = null;


/**
 * Init
 * @return {[type]} [description]
 */
NodeEditSource.prototype.init = function(){
    var _this = this;

   _this.$input = _this.$content.find('input, select');

    for(var i = 0; i < _this.$input.length; i++) {

        if(_this.$input[i].getAttribute('data-desc') !== ''){
            $(_this.$input[i]).after('<div class="form-help uk-alert uk-alert-large">'+_this.$input[i].getAttribute('data-desc')+'</div>');
        }

    }

    _this.$input.on('focus', $.proxy(_this.inputFocus, _this));
    _this.$input.on('focusout', $.proxy(_this.inputFocusOut, _this));

};


/**
 * Input focus
 * @return {[type]} [description]
 */
NodeEditSource.prototype.inputFocus = function(e){
    var _this = this;

    $(e.currentTarget).parent().addClass('form-col-focus');

};


/**
 * Input focus out
 * @return {[type]} [description]
 */
NodeEditSource.prototype.inputFocusOut = function(e){
    var _this = this;


    $(e.currentTarget).parent().removeClass('form-col-focus');

};






/**
 * Destroy
 * @return {[type]} [description]
 */
NodeEditSource.prototype.destroy = function(){
    var _this = this;


};


/**
 * Window resize callback
 * @return {[type]} [description]
 */
NodeEditSource.prototype.resize = function(){
    var _this = this;

};