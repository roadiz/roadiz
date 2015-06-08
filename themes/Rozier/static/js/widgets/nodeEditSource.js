/**
 * NODE EDIT SOURCE
 */

NodeEditSource = function(){
    var _this = this;

    // Selectors
    _this.$content = $('.content-node-edit-source');
    _this.$formRow = null;
    _this.$dropdown = null;
    _this.$input = null;

    // Methods
    if(_this.$content.length){
        _this.$formRow = _this.$content.find('.uk-form-row');
        _this.init();
    }

};

/**
 * Init
 * @return {[type]} [description]
 */
NodeEditSource.prototype.init = function(){
    var _this = this;

    // Inputs - add form help
    _this.$input = _this.$content.find('input, select');
    _this.$devNames = _this.$content.find('[data-dev-name]');

    for (var i = _this.$input.length - 1; i >= 0; i--) {
        if(_this.$input[i].getAttribute('data-desc') !== ''){
            $(_this.$input[i]).after('<div class="form-help uk-alert uk-alert-large">'+_this.$input[i].getAttribute('data-desc')+'</div>');
        }
    }

    for (var j = _this.$devNames.length - 1; j >= 0; j--) {
        var input = _this.$devNames[j];
        var $input = $(input);
        if(input.getAttribute('data-dev-name') !== ''){
            var $label = $input.parents('.uk-form-row').find('label');
            var $barLabel = $input.find('.uk-navbar-brand.label');

            if($label.length){
                $label.append('<span class="field-dev-name">'+input.getAttribute('data-dev-name')+'</span>');
            } else if($barLabel.length){
                $barLabel.append('<span class="field-dev-name">'+input.getAttribute('data-dev-name')+'</span>');
            }
        }
    }
    Rozier.$window.off('keydown', $.proxy(_this.onInputKeyDown, _this));
    Rozier.$window.on('keydown', $.proxy(_this.onInputKeyDown, _this));
    Rozier.$window.off('keyup', $.proxy(_this.onInputKeyUp, _this));
    Rozier.$window.on('keyup', $.proxy(_this.onInputKeyUp, _this));

    _this.$input.off('focus', $.proxy(_this.inputFocus, _this));
    _this.$input.on('focus', $.proxy(_this.inputFocus, _this));
    _this.$input.off('focusout', $.proxy(_this.inputFocusOut, _this));
    _this.$input.on('focusout', $.proxy(_this.inputFocusOut, _this));

    // Check if children node widget needs his dropdowns to be flipped up
    for (var k = _this.$formRow.length - 1; k >= 0; k--) {
        if(_this.$formRow[k].className.indexOf('children-nodes-widget') >= 0){
            _this.childrenNodeWidgetFlip(k);
            break;
        }
    }
};

NodeEditSource.prototype.onInputKeyDown = function(event) {
    var _this = this;

    // ALT key
    if(event.keyCode == 18) {
        Rozier.$body.toggleClass('dev-name-visible');
    }
};
NodeEditSource.prototype.onInputKeyUp = function(event) {
    var _this = this;

    // ALT key
    if(event.keyCode == 18) {
        Rozier.$body.toggleClass('dev-name-visible');
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
