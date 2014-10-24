var NodeStatuses = function () {
    var _this = this;

    _this.$containers = $(".node-statuses");
    _this.$icon = _this.$containers.find('header i');
    _this.$inputs = _this.$containers.find('input[type="checkbox"], input[type="radio"]');
    _this.$item = _this.$containers.find('.node-statuses-item');

    _this.init();
};

NodeStatuses.prototype.$containers = null;
NodeStatuses.prototype.$icon = null;
NodeStatuses.prototype.$inputs = null;
NodeStatuses.prototype.$item = null;

NodeStatuses.prototype.init = function() {
    var _this = this;

    _this.$item.on('click', $.proxy(_this.itemClick, _this));

    _this.$inputs.off('change', $.proxy(_this.onChange, _this));
    _this.$inputs.on('change', $.proxy(_this.onChange, _this));

    _this.$containers.find(".rz-boolean-checkbox").bootstrapSwitch({
        "onSwitchChange": $.proxy(_this.onChange, _this)
    });
};

NodeStatuses.prototype.itemClick = function(event) {
    var _this = this;

    $(event.currentTarget).find('input[type="checkbox"], input[type="radio"]').prop('checked', true);
    _this.$icon[0].className = $(event.currentTarget).find('i')[0].className;

    return false;
};

NodeStatuses.prototype.onChange = function(event) {
    var _this = this;

    var $input = $(event.currentTarget);

    if ($input.length) {

        var statusName = $input.attr('name');
        var statusValue = null;
        if($input.is('input[type="checkbox"]')){
            statusValue = Number($input.is(':checked'));
        } else if($input.is('input[type="radio"]')){
            statusValue = Number($input.val());
        }

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
            console.log(data);
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