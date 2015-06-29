/**
 * NODE EDIT SOURCE
 */

NodeEditSource = function(){
    var _this = this;

    // Selectors
    _this.$content = $('.content-node-edit-source');
    _this.$form = $('#edit-node-source-form');
    _this.$formRow = null;
    _this.$dropdown = null;
    _this.$input = null;

    // Methods
    if(_this.$content.length){
        _this.$formRow = _this.$content.find('.uk-form-row');
        _this.wrapInTabs();
        _this.init();
    }

};

NodeEditSource.prototype.wrapInTabs = function() {
    var _this = this;

    var fieldGroups = {};
    var $fields = _this.$content.find('.uk-form-row[data-field-group]');
    var fieldsLength = $fields.length;
    var fieldsGroupsLength = 0;

    if (fieldsLength > 1) {
        for (var i = 0; i < fieldsLength; i++) {
            var groupName = $fields[i].getAttribute('data-field-group');
            if (typeof fieldGroups[groupName] === "undefined" ) {
                fieldGroups[groupName] = [];
                fieldsGroupsLength++;
            }
            fieldGroups[groupName].push($fields[i]);
        }

        if (fieldsGroupsLength > 1) {
            _this.$form.prepend('<ul class="uk-switcher-nav uk-subnav uk-subnav-pill" data-uk-switcher="{connect:\'#edit-node-source-form-switcher\', animation: \'slide-horizontal\'}"></ul><ul id="edit-node-source-form-switcher" class="uk-switcher"></ul>');
            var $formSwitcher = _this.$form.find('.uk-switcher');
            var $formSwitcherNav = _this.$form.find('.uk-switcher-nav');

            /*
             * Sort tab name and put default in first
             */
            var keysSorted = Object.keys(fieldGroups).sort(function (a,b) {
                if (a == 'default') { return -1; }
                if (b == 'default') { return 1; }
                return +(a.toLowerCase() > b.toLowerCase()) || +(a.toLowerCase() === b.toLowerCase()) - 1;
            });

            for (var keyIndex in keysSorted) {
                var groupName2 = keysSorted[keyIndex];
                var groupId = 'group-' + groupName2.toLowerCase();
                $formSwitcher.append('<li class="field-group" id="' + groupId + '"></li>');
                $formSwitcherNav.append('<li><a href="#">' + groupName2 + '</a></li>');
                var $group = $formSwitcher.find('#'+groupId);

                for(var index = 0; index < fieldGroups[groupName2].length; index++) {
                    $group.append($(fieldGroups[groupName2][index]));
                }
            }
        }
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
