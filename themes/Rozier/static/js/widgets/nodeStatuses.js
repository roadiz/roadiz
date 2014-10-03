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

    if ($input.length) {

        var statusName = $input.attr('name');
        var statusValue = $input.is(':checked');

        var postData = {
            "_token": Rozier.ajaxToken,
            "_action":'nodeChangeStatus',
            "nodeId":parseInt($input.attr('data-node-id')),
            "statusName": statusName,
            "statusValue": statusValue
        };
        console.log(postData);

        $.ajax({
            url: Rozier.routes.nodesStatusesAjax,
            type: 'post',
            dataType: 'json',
            data: postData
        })
        .done(function(data) {
            //console.log(data.responseText);
            Rozier.refreshMainNodeTree();
            $.UIkit.notify({
                message : data.responseText,
                status  : data.status,
                timeout : 3000,
                pos     : 'top-center'
            });
        })
        .fail(function(data) {
            console.log(data.responseJSON);

            data = JSON.parse(data.responseText);

            $.UIkit.notify({
                message : data.responseText,
                status  : data.status,
                timeout : 3000,
                pos     : 'top-center'
            });
        })
        .always(function(data) {

        });
    }
};