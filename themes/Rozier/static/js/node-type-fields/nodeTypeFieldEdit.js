/**
 * NODE TYPE FIELD EDIT
 */
NodeTypeFieldEdit = function(){
    var _this = this;

    // Selectors
    _this.$btn = $('.node-type-field-edit-button');

    if (_this.$btn.length) {
        _this.$formFieldRow = $('.node-type-field-row');
        _this.$formFieldCol = $('.node-type-field-col');

        _this.indexOpen = null;
        _this.openFormDelay = 0;
        _this.$formCont = null;
        _this.$form = null;
        _this.$formIcon = null;
        _this.$formContHeight = null;

        // Methods
        _this.init();
    }
};


/**
 * Init
 * @return {[type]} [description]
 */
NodeTypeFieldEdit.prototype.init = function(){
    var _this = this;

    // Events
    var proxy = $.proxy(_this.btnClick, _this);
    _this.$btn.off('click');
    _this.$btn.on('click', proxy);
};


/**
 * Btn click
 * @return {[type]} [description]
 */
NodeTypeFieldEdit.prototype.btnClick = function(e){
    var _this = this;

    e.preventDefault();

    if(_this.indexOpen !== null){
        _this.closeForm();
        _this.openFormDelay = 500;
    }
    else _this.openFormDelay = 0;

    if(_this.indexOpen !== parseInt(e.currentTarget.getAttribute('data-index'))) {
        Rozier.lazyload.canvasLoader.show();

        setTimeout(function(){
            console.log('Opening node-type field…');
            _this.indexOpen = parseInt(e.currentTarget.getAttribute('data-index'));

            $.ajax({
                url: e.currentTarget.href,
                type: 'get',
                dataType: 'html'
            })
            .done(function(data) {
                _this.applyContent(e.currentTarget, data, e.currentTarget.href);
            })
            .fail(function() {
                console.log("error");
                UIkit.notify({
                    message : Rozier.messages.forbiddenPage,
                    status  : 'danger',
                    timeout : 3000,
                    pos     : 'top-center'
                });
            })
            .always(function () {
                Rozier.lazyload.canvasLoader.hide();
            });
        }, _this.openFormDelay);
    }

    return false;
};


/**
 * Apply content
 */
NodeTypeFieldEdit.prototype.applyContent = function(target, data, url){
    var _this = this;

    var dataWrapped = [
        '<tr class="node-type-field-edit-form-row">',
            '<td colspan="4">',
                '<div class="node-type-field-edit-form-cont">',
                    data,
                '</div>',
            '</td>',
        '</tr>'
    ].join('');

    $(target).parent().parent().after(dataWrapped);

    // Remove class to pause sortable actions
    _this.$formFieldCol.removeClass('node-type-field-col');

    // Switch checkboxes
    $(".rz-boolean-checkbox").bootstrapSwitch({
        size: 'small'
    });

    Rozier.lazyload.initMarkdownEditors();

    setTimeout(function(){
        _this.$formCont = $('.node-type-field-edit-form-cont');
        _this.formContHeight = _this.$formCont.actual('height');
        _this.$formRow = $('.node-type-field-edit-form-row');
        _this.$form = $('#edit-node-type-field-form');
        _this.$formIcon = $(_this.$formFieldRow[_this.indexOpen]).find('.node-type-field-col-1 i');

        _this.$form.attr('action', url);
        _this.$formIcon[0].className = 'uk-icon-chevron-down';

        _this.$formCont[0].style.height = '0px';
        _this.$formCont[0].style.display = 'block';
        TweenLite.to(_this.$form, 0.6, {height:_this.formContHeight, ease:Expo.easeOut});
        TweenLite.to(_this.$formCont, 0.6, {height:_this.formContHeight, ease:Expo.easeOut});
    }, 200);
};


/**
 * Close form
 * @return {[type]} [description]
 */
NodeTypeFieldEdit.prototype.closeForm = function(){
    var _this = this;

    _this.$formIcon[0].className = 'uk-icon-chevron-right';

    TweenLite.to(_this.$formCont, 0.4, {height:0, ease:Expo.easeOut, onComplete:function(){
        _this.$formRow.remove();
        _this.indexOpen = null;
        _this.$formFieldCol.addClass('node-type-field-col');
    }});

};


/**
 * Window resize callback
 * @return {[type]} [description]
 */
NodeTypeFieldEdit.prototype.resize = function(){
    var _this = this;

};
