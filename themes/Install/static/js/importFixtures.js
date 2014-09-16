var ImportFixtures = function ( routesArray ) {
    var _this = this;

    _this.routes = routesArray;

    _this.callSingleImport(0);
};

ImportFixtures.prototype.routes = null;
ImportFixtures.prototype.score = 0;

ImportFixtures.prototype.callSingleImport = function( index ) {
    var _this = this;

    if(_this.routes.length > index){

        var $row = $("#"+_this.routes[index].id);
        var $icon = $row.find("i");
        $icon.removeClass('uk-icon-circle-o');
        $icon.addClass('uk-icon-spin');
        $icon.addClass('uk-icon-spinner');

        $.ajax({
            url: _this.routes[index].url,
            type: 'GET',
            dataType: 'json'
        })
        .done(function(data) {
            console.log("success");
            console.log(data);
            _this.score++;

            $icon.removeClass('uk-icon-spinner');
            $icon.addClass('uk-icon-check');
            $row.addClass('uk-badge-success');

        })
        .fail(function(data) {
            console.log("error");
            console.log(data.responseJSON);

            $icon.removeClass('uk-icon-spinner');
            $icon.addClass('uk-icon-warning');
            $row.addClass('uk-badge-danger');

            $row.parent().parent().after("<tr><td class=\"uk-alert uk-alert-danger\" colspan=\"3\">"+data.responseJSON.error+"</td></tr>")
        })
        .always(function(data) {
            console.log("complete");
            $icon.removeClass('uk-icon-spin');

            _this.callSingleImport(index + 1);
        });
    } else {

        if(_this.score === _this.routes.length){
            $('#next-step-button').removeClass('uk-button-disabled');
        }
    }
};