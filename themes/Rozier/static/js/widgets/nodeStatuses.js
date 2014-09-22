var NodeStatuses = function () {
    var _this = this;


    _this.$containers = $(".node-statuses");
    _this.$inputs = _this.$containers.find('input[type="checkbox"]');

    _this.init();
};

NodeStatuses.prototype.$containers = null;
NodeStatuses.prototype.$inputs = null;

NodeStatuses.prototype.init = function() {
    var _this = this;

    _this.$inputs.off('change', $.proxy(_this.onChange, _this));
    _this.$inputs.on('change', $.proxy(_this.onChange, _this));


    _this.$containers.find(".rz-boolean-checkbox").bootstrapSwitch({
        "onSwitchChange": $.proxy(_this.onChange, _this)
    });
};

NodeStatuses.prototype.onChange = function(event) {
    var _this = this;

    var $input = $(event.currentTarget);

    console.log("Changed status of : "+$input.attr('name')+" : "+($input.is(':checked') ? "ON" : "OFF"));


    $.ajax({
        url: Rozier.routes.nodesStatusesAjax,
        type: 'post',
        dataType: 'json',
    })
    .done(function() {
        console.log("success");
    })
    .fail(function() {
        console.log("error");
    })
    .always(function() {
        console.log("complete");
    });

};