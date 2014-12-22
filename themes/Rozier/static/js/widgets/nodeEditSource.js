/**
 * NODE EDIT SOURCE
 */

NodeEditSource = function(){
    var _this = this;

    // Selectors
    _this.$content = $('.content-node-edit-source');

    // Methods
    if(_this.$content.length){
        _this.$formRow = _this.$content.find('.uk-form-row');
        _this.init();
    }

};


NodeEditSource.prototype.$content = null;
NodeEditSource.prototype.$formRow = null;
NodeEditSource.prototype.$dropdown = null;
NodeEditSource.prototype.$input = null;


/**
 * Init
 * @return {[type]} [description]
 */
NodeEditSource.prototype.init = function(){
    var _this = this;

    // Inputs - add form help
    _this.$input = _this.$content.find('input, select');

    for(var i = 0; i < _this.$input.length; i++) {

        if(_this.$input[i].getAttribute('data-desc') !== ''){
            $(_this.$input[i]).after('<div class="form-help uk-alert uk-alert-large">'+_this.$input[i].getAttribute('data-desc')+'</div>');
        }

    }

    _this.$input.on('focus', $.proxy(_this.inputFocus, _this));
    _this.$input.on('focusout', $.proxy(_this.inputFocusOut, _this));


    // Check if children node widget needs his dropdowns to be flipped up
    for(var j = 0; j < _this.$formRow.length; j++) {

        if(_this.$formRow[j].className.indexOf('children-nodes-widget') >= 0){
            _this.childrenNodeWidgetFlip(j);
            break;
        }
    }

};


/**
 * Flip children node widget 
 * @param  {[type]} index [description]
 * @return {[type]}       [description]
 */
NodeEditSource.prototype.childrenNodeWidgetFlip = function(index){
    var _this = this;

    if(index >= (_this.$formRow.length-2)){
        _this.$dropdown = $(_this.$formRow[index]).find('.uk-dropdown-small');
        _this.$dropdown.addClass('uk-dropdown-up');
    }

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
 * Window resize callback
 * @return {[type]} [description]
 */
NodeEditSource.prototype.resize = function(){
    var _this = this;

};