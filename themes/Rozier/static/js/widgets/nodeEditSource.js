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

   console.log(_this.$input);

    for(var i = 0; i < _this.$input.length; i++) {
        
        if(_this.$input[i].getAttribute('data-desc') !== null){
            $(_this.$input[i]).after('<div class="uk-alert uk-alert-large">'+_this.$input[i].getAttribute('data-desc')+'</div>');
        }   

    }    

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