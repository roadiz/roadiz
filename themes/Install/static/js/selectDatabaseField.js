var SelectDatabaseField = function () {
    var _this = this;

    _this.init();
};

SelectDatabaseField.prototype.init = function() {
    var _this = this;
    _this.changeField($("#form_driver").val());
    $("#form_driver").on('change', $.proxy(_this.changeFieldEvent, _this));
};

SelectDatabaseField.prototype.changeFieldEvent = function(event) {
    var _this = this;
    var $choices = $(event.currentTarget);

    _this.changeField($choices.val());
};

SelectDatabaseField.prototype.changeField = function(driver) {
    var _this = this;

    console.log(driver);
    if (driver == "pdo_sqlite") {
        _this.disableField($("#form_host"));
        _this.disableField($("#form_port"));
        _this.disableField($("#form_unix_socket"));
        _this.enableField($("#form_path"));
        _this.disableField($("#form_dbname"));
    }
    else if (driver == "pdo_mysql") {
        _this.enableField($("#form_host"));
        _this.enableField($("#form_port"));
        _this.enableField($("#form_unix_socket"));
        _this.disableField($("#form_path"));
        _this.enableField($("#form_dbname"));
    }
    else if (driver == "pdo_pgsql") {
        _this.enableField($("#form_host"));
        _this.enableField($("#form_port"));
        _this.disableField($("#form_unix_socket"));
        _this.disableField($("#form_path"));
        _this.enableField($("#form_dbname"));
    }
    else if (driver == "oci8") {
        _this.enableField($("#form_host"));
        _this.enableField($("#form_port"));
        _this.disableField($("#form_unix_socket"));
        _this.disableField($("#form_path"));
        _this.enableField($("#form_dbname"));
    }
};

SelectDatabaseField.prototype.disableField = function (field) {
    console.log('disable');
    console.log(field.parent());
    field.parent().hide();
    field.attr("disabled", "disabled");
};

SelectDatabaseField.prototype.enableField = function (field) {
    field.parent().show();
    field.removeAttr("disabled");
};