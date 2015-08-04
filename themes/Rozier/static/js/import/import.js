var Import = function ( routesArray ) {
    var _this = this;

    _this.routes = routesArray;
    console.log("init importer");
    _this.always(0);
};

Import.prototype.routes = null;
Import.prototype.score = 0;
Import.prototype.always = function(index) {
    var _this = this;

    if(_this.routes.length > index) {
        if (typeof _this.routes[index].update !== "undefined") {
            $.ajax({
                url:_this.routes[index].update,
                type: 'POST',
                dataType: 'json',
                complete: function() {
                    //console.log("updateSchema");
                    _this.callSingleImport(index);
                }
            });
        } else {
            _this.callSingleImport(index);
        }
    } else {
        $('#next-step-button').removeClass('uk-button-disabled');
    }
};

Import.prototype.callSingleImport = function( index ) {
    var _this = this;

    var $row = $("#"+_this.routes[index].id);
    var $icon = $row.find("i");
    $icon.removeClass('uk-icon-circle-o');
    $icon.addClass('uk-icon-spin');
    $icon.addClass('uk-icon-spinner');


    var postData = {
        'filename':_this.routes[index].filename
    };

    $.ajax({
        url: _this.routes[index].url,
        type: 'POST',
        dataType: 'json',
        data: postData,
        success: function(data) {
            $icon.removeClass('uk-icon-spinner');
            $icon.addClass('uk-icon-check');
            $row.addClass('uk-badge-success');

            /*
             * Call post-update route
             */
            if (typeof _this.routes[index].postUpdate !== "undefined") {
                if(_this.routes[index].postUpdate instanceof Array &&
                    _this.routes[index].postUpdate.length > 1){
                    /*
                     * Call clear cache before updating schema
                     */
                    $.ajax({
                        url:_this.routes[index].postUpdate[0],
                        type: 'POST',
                        dataType: 'json',
                        complete: function() {
                            /*
                             * Update schema
                             */
                            console.log('Calling: ' + _this.routes[index].postUpdate[0]);
                            $.ajax({
                                url:_this.routes[index].postUpdate[1],
                                type: 'POST',
                                dataType: 'json',
                                complete: function() {
                                    console.log('Calling: ' + _this.routes[index].postUpdate[1]);
                                    _this.always(index + 1);
                                }
                            });
                        }
                    });
                } else {
                    $.ajax({
                        url:_this.routes[index].postUpdate,
                        type: 'POST',
                        dataType: 'json',
                        complete: function() {
                            _this.always(index + 1);
                        }
                    });
                }
            } else {
                _this.always(index + 1);
            }
        },
        error: function(data) {
            console.log(data);
            $icon.removeClass('uk-icon-spinner');
            $icon.addClass('uk-icon-warning');
            $row.addClass('uk-badge-danger');

            if (typeof data.responseJSON != "undefined" &&
                typeof data.responseJSON.error != "undefined") {
                $row.parent().parent().after("<tr><td class=\"uk-alert uk-alert-danger\" colspan=\"3\">"+data.responseJSON.error+"</td></tr>");
            }
        },
        complete: function(data) {
            //console.log("complete");
            //console.log(index);
            $icon.removeClass('uk-icon-spin');
        }
    });
};
