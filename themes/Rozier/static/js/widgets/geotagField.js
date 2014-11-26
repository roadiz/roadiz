var GeotagField = function () {
    var _this = this;

    _this.$fields = $('input.rz-geotag-field');

    if( _this.$fields.length &&
        Rozier.googleClientId !== ""){

        _this.init();
    }
};

GeotagField.prototype.geocoder = null;
GeotagField.prototype.$fields = null;
GeotagField.prototype.init = function() {
    var _this = this;

    if(!Rozier.gMapLoaded) {

        var script = document.createElement('script');
        script.type = 'text/javascript';
        script.src = '//maps.googleapis.com/maps/api/js?key='+Rozier.googleClientId +
            '&callback=initializeGeotagFields';
        document.body.appendChild(script);

    } else {
        _this.bindFields();
    }
};


GeotagField.prototype.bindFields = function() {
    var _this = this;

    _this.geocoder = new google.maps.Geocoder();

    _this.$fields.each(function (index, element) {

        _this.bindSingleField(element);
    });
};


GeotagField.prototype.bindSingleField = function(element) {
    var _this = this;

    var $input = $(element);
    var jsonCode = {'lat':45.769785, 'lng':4.833967, 'zoom':14}; // default location
    var fieldId = 'geotag-canvas-'+GeotagField.uniqid();
    var fieldAddressId = fieldId+'-address';
    var resetButtonId = fieldId+'-reset';

    var mapOptions = {
        center: new google.maps.LatLng(jsonCode.lat, jsonCode.lng),
        zoom: jsonCode.zoom,
        styles: Rozier.mapsStyle
    };

    /*
     * prepare DOM
     */
    $input.hide();
    $input.attr('data-geotag-canvas', fieldId);
    $input.after('<div class="rz-geotag-canvas" id="'+fieldId+'" style="width: 100%; height: 400px;"></div>');
    // Geocode input text
    var metaDOM = '<nav class="rz-geotag-meta"><input class="rz-geotag-address" id="'+fieldAddressId+'" type="text" value="" />';
    metaDOM += '<a id="'+resetButtonId+'" class="uk-button uk-button-content uk-button-table-delete rz-geotag-reset" title="'+Rozier.messages.geotag.resetMarker+'" data-uk-tooltip="{animation:true}"><i class="uk-icon-rz-trash-o"></i></a></nav>';
    $input.after(metaDOM);

    var $geocodeInput = $('#'+fieldAddressId);
    $geocodeInput.attr('placeholder', Rozier.messages.geotag.typeAnAddress);
    // Reset button
    var $geocodeReset = $('#'+resetButtonId);
    $geocodeReset.hide();

    /*
     * Prepare map and marker
     */
    var map = new google.maps.Map(document.getElementById(fieldId), mapOptions);
    var marker = null;

    if($input.val() !== ""){
        try {
            jsonCode = JSON.parse($input.val());
            marker = _this.createMarker(jsonCode, $input, map);
            $geocodeReset.show();
        } catch (e) {

            $input.show();
            $(document.getElementById(fieldId)).hide();

            return false;
        }
    } else {
        marker = new google.maps.Marker({
            //map:map,
            draggable:true,
            position: mapOptions.center,
            animation: google.maps.Animation.DROP
        });
    }

    google.maps.event.addListener(marker, 'dragend', $.proxy(_this.setMarkerEvent, _this, marker, $input, $geocodeReset, map));
    google.maps.event.addListener(map, 'click', $.proxy(_this.setMarkerEvent, _this, marker, $input, $geocodeReset, map));

    $geocodeInput.on('keypress', $.proxy(_this.requestGeocode, _this, marker, $input, $geocodeReset, map));
    $geocodeReset.on('click', $.proxy(_this.resetMarker, _this, marker, $input, $geocodeReset, map));

    setTimeout(function () {
        google.maps.event.trigger(map, "resize");

        if (null !== marker) {
            map.panTo(marker.getPosition());
        } else {
            map.panTo(mapOptions.center);
        }

    }, 500);
};

/**
 * @param Marker marker
 * @param jQuery DOM $input
 * @param Map map
 * @param Event event
 */
GeotagField.prototype.resetMarker = function(marker, $input, $geocodeReset, map, event) {
    var _this = this;

    marker.setMap(null);
    $input.val("");

    $geocodeReset.hide();

    return false;
};
/**
 * @param Marker marker
 * @param jQuery DOM $input
 * @param Map map
 * @param Event event
 */
GeotagField.prototype.setMarkerEvent = function(marker, $input, $geocodeReset, map, event) {
    var _this = this;

    _this.setMarker(marker, $input, $geocodeReset, map, event.latLng);
};

/**
 * @param Marker marker
 * @param jQuery DOM $input
 * @param Map map
 * @param Event event
 */
GeotagField.prototype.setMarker = function(marker, $input, $geocodeReset, map, latlng) {
    var _this = this;

    marker.setPosition(latlng);
    marker.setMap(map);

    map.panTo(latlng);

    var geoCode = {
        'lat':latlng.lat(),
        'lng':latlng.lng(),
        'zoom':map.getZoom()
    };

    $input.val(JSON.stringify(geoCode));

    $geocodeReset.show();
};

/**
 * @param  Object geocode
 * @param  jQuery DOM $input
 * @param  Map map
 *
 * @return Marker
 */
GeotagField.prototype.createMarker = function(geocode, $input, map) {
    var _this = this;
    var latlng = new google.maps.LatLng(geocode.lat, geocode.lng);
    var marker = new google.maps.Marker({
        map:map,
        draggable:true,
        animation: google.maps.Animation.DROP,
        position: latlng
    });

    map.panTo(latlng);
    map.setZoom(geocode.zoom);

    return marker;
};

GeotagField.prototype.requestGeocode = function(marker, $input, $geocodeReset, map, event) {
    var _this = this;

    var address = event.currentTarget.value;

    if(event.which == 13) {
        event.preventDefault();

        _this.geocoder.geocode( {'address': address}, function(results, status) {
            if (status == google.maps.GeocoderStatus.OK) {
                _this.setMarker(marker, $input, $geocodeReset, map, results[0].geometry.location);

            } else {
                console.err("Geocode was not successful for the following reason: " + status);
            }
        });

        return false;
    }
};

var initializeGeotagFields = function () {
    Rozier.gMapLoaded = true;
    new GeotagField();
};

GeotagField.uniqid = function () {
    var n = new Date();
    return n.getTime();
};