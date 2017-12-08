var MultiGeotagField = function () {
    var _this = this;

    _this.$fields = $('.rz-multi-geotag-field');
    _this.geocoder = null;

    if( _this.$fields.length &&
        Rozier.googleClientId !== ""){

        _this.init();
    }
};

$.extend(MultiGeotagField.prototype, GeotagField.prototype);


/**
 * @param Marker marker
 * @param jQuery DOM $input
 * @param Map map
 * @param Event event
 */
MultiGeotagField.prototype.resetMarker = function(markers, $input, $geocodeReset, map, $selector, event) {
    var _this = this;

    $input.val("");
    for (var i = markers.length - 1; i >= 0; i--) {
        markers[i].setMap(null);
        markers[i] = null;
    }
    markers = [];

    $geocodeReset.hide();
    _this.syncSelector($selector, markers, map, $input);

    return false;
};


MultiGeotagField.prototype.bindSingleField = function(element) {
    var _this = this;

    var $input = $(element),
        $label = $input.parent().find('.uk-form-label'),
        labelText = $label[0].innerHTML;

    var jsonCode = {'lat':45.769785, 'lng':4.833967, 'zoom':14}; // default location
    var fieldId = 'geotag-canvas-'+GeotagField.uniqid();
    var fieldAddressId = fieldId+'-address';
    var resetButtonId = fieldId+'-reset';

    var mapOptions = {
        center: new google.maps.LatLng(jsonCode.lat, jsonCode.lng),
        zoom: jsonCode.zoom,
        scrollwheel: false,
        styles: Rozier.mapsStyle
    };

    /*
     * prepare DOM
     */
    $input.hide();
    $label.hide();
    $input.attr('data-geotag-canvas', fieldId);

    // Geocode input text
    var metaDOM = [
        '<nav class="geotag-widget-nav uk-navbar rz-geotag-meta">',
            '<ul class="uk-navbar-nav">',
                '<li class="uk-navbar-brand"><i class="uk-icon-rz-map-multi-marker"></i>',
                '<li class="uk-navbar-brand label">'+labelText+'</li>',
            '</ul>',
            '<div class="uk-navbar-content uk-navbar-flip">',
                '<div class="geotag-widget-quick-creation uk-button-group">',
                    '<input class="rz-geotag-address" id="'+fieldAddressId+'" type="text" value="" />',
                    '<a id="'+resetButtonId+'" class="uk-button uk-button-content uk-button-table-delete rz-geotag-reset" title="'+Rozier.messages.geotag.resetMarker+'" data-uk-tooltip="{animation:true}"><i class="uk-icon-rz-trash-o"></i></a>',
                '</div>',
            '</div>',
        '</nav>',
        '<div class="multi-geotag-group">',
            '<ul class="multi-geotag-list-markers">',
            '</ul>',
            '<div class="rz-geotag-canvas" id="'+fieldId+'"></div>',
        '</div>',
    ].join('');


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
    var markers = [];
    var $selector = $input.parent().find('.multi-geotag-list-markers');

    if($input.val() !== ""){
        try {
            var geocodes = JSON.parse($input.val());
            var geocodeslength = geocodes.length;
            for (var i = 0; i < geocodeslength; i++) {
                markers[i] = _this.createMarker(geocodes[i], $input, map);
                google.maps.event.addListener(markers[i], 'dragend', $.proxy(_this.setMarkerEvent, _this, markers[i], markers, $input, $geocodeReset, map));
            }
            $geocodeReset.show();

        } catch (e) {
            $input.show();
            $(document.getElementById(fieldId)).hide();

            return false;
        }
    }

    google.maps.event.addListener(map, 'click', $.proxy(_this.setMarkerEvent, _this, null, markers, $input, $geocodeReset, map));
    google.maps.event.addListener(map, 'click', $.proxy(_this.syncSelector, _this, $selector, markers, map, $input));

    $geocodeInput.on('keypress', $.proxy(_this.requestGeocode, _this, markers, $input, $geocodeReset, map, $selector));
    $geocodeReset.on('click', $.proxy(_this.resetMarker, _this, markers, $input, $geocodeReset, map, $selector));
    $geocodeReset.on('click', $.proxy(_this.syncSelector, _this, $selector, markers, map, $input));

    Rozier.$window.on('resize', $.proxy(_this.resetMap, _this, map, markers, mapOptions));
    _this.resetMap(map, markers, mapOptions, null);
    _this.syncSelector($selector, markers, map, $input);
};

MultiGeotagField.prototype.syncSelector = function($selector, markers, map, $input) {
    var _this = this;

    $selector.empty();
    var markersLength = markers.length;
    for (var i = 0; i < markersLength; i++) {
        if (null !== markers[i]) {
            var geocode = _this.getGeocodeFromMarker(markers[i]);

            $selector.append([
                '<li>',
                    '<span class="multi-geotag-marker-name">',
                    geocode.name ? geocode.name : ('#' + i),
                    '</span>',
                    '<a class="button rz-multi-geotag-center" data-geocode-id="' + i + '" data-geocode="' + JSON.stringify(geocode) + '"><i class="uk-icon-rz-marker"></i></a>',
                    '<a class="button rz-multi-geotag-remove" data-geocode-id="' + i + '"><i class="uk-icon-rz-trash-o"></i></a>',
                '</li>',
            ].join(''));

            var $centerBtn = $selector.find('.rz-multi-geotag-center[data-geocode-id="' + i + '"]');
            var $removeBtn = $selector.find('.rz-multi-geotag-remove[data-geocode-id="' + i + '"]');

            $centerBtn.on('click', $.proxy(_this.centerMap, _this, map, markers[i]));
            $removeBtn.on('click', $.proxy(_this.removeMarker, _this, map, markers, i, $selector, $input));
        }
    }
};

MultiGeotagField.prototype.removeMarker = function(map, markers, index, $selector, $input, event) {
    var _this = this;

    markers[index].setMap(null);
    markers[index] = null;

    _this.syncSelector($selector, markers, map, $input);
    _this.writeMarkers(markers, $input);

    return false;
};

MultiGeotagField.prototype.getGeocodeFromMarker = function(marker) {
    var _this = this;

    return {
        'lat':marker.getPosition().lat(),
        'lng':marker.getPosition().lng(),
        'zoom':marker.zoom,
        'name':marker.name
    };
};

MultiGeotagField.prototype.resetMap = function(map, markers, mapOptions, event) {
    var _this = this;

    setTimeout(function () {
        google.maps.event.trigger(map, "resize");

        if (typeof markers !== "undefined" && markers.length > 0) {
            map.fitBounds(_this.getMediumLatLng(markers));
        } else {
            map.panTo(mapOptions.center);
        }
    }, 300);
};

MultiGeotagField.prototype.centerMap = function(map, marker, event) {
    var _this = this;
    setTimeout(function () {
        google.maps.event.trigger(map, "resize");

        if (typeof marker !== "undefined") {
            map.panTo(marker.getPosition());
        }
        if (typeof marker.zoom !== "undefined") {
            map.setZoom(marker.zoom);
        }
    }, 300);

    return false;
};

MultiGeotagField.prototype.getMediumLatLng = function (markers) {
    var _this = this;

    var bounds = new google.maps.LatLngBounds();
    for (var index in markers) {
        var data = markers[index];
        bounds.extend(markers[index].getPosition());
    }

    return bounds;
};

/**
 * @param Marker marker
 * @param jQuery DOM $input
 * @param Map map
 * @param Event event
 */
MultiGeotagField.prototype.setMarkerEvent = function(marker, markers, $input, $geocodeReset, map, event) {
    var _this = this;

    _this.setMarker(marker, markers, $input, $geocodeReset, map, event.latLng);
};

/**
 * @param Marker marker
 * @param jQuery DOM $input
 * @param Map map
 * @param Event event
 */
MultiGeotagField.prototype.setMarker = function(marker, markers, $input, $geocodeReset, map, latlng, name) {
    var _this = this;

    if (null === marker) {
        marker = new google.maps.Marker({
            map:map,
            draggable:true,
            animation: google.maps.Animation.DROP,
            position: latlng,
            icon : Rozier.resourcesUrl+'img/map_marker.png'
        });
    }
    marker.setPosition(latlng);
    marker.setMap(map);
    marker.zoom = map.getZoom();
    marker.name = name;

    map.panTo(latlng);

    var geoCode = {
        'lat':latlng.lat(),
        'lng':latlng.lng(),
        'zoom':map.getZoom(),
        'name':name
    };

    markers.push(marker);

    _this.writeMarkers(markers, $input);

    $geocodeReset.show();

    return marker;
};

MultiGeotagField.prototype.writeMarkers = function(markers, $input) {
    var _this = this;

    var geocodes = [];
    for (var i = markers.length - 1; i >= 0; i--) {
        if (null !== markers[i]) {
            geocodes.push(_this.getGeocodeFromMarker(markers[i]));
        }
    }

    $input.val(JSON.stringify(geocodes));
};

MultiGeotagField.prototype.requestGeocode = function(markers, $input, $geocodeReset, map, $selector, event) {
    var _this = this;

    var address = event.currentTarget.value;

    if(event.which == 13) {
        event.preventDefault();

        _this.geocoder.geocode( {'address': address}, function(results, status) {
            if (status == google.maps.GeocoderStatus.OK) {
                _this.setMarker(null, markers, $input, $geocodeReset, map, results[0].geometry.location, address);
                _this.syncSelector($selector, markers, map, $input);
            } else {
                console.err("Geocode was not successful for the following reason: " + status);
            }
        });

        return false;
    }
};
