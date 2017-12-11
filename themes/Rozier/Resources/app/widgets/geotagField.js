import $ from 'jquery'
import MultiGeotagField from './multiGeotagField'

export default function GeotagField () {
    var _this = this

    _this.$fields = $('input.rz-geotag-field')
    _this.geocoder = null

    if (_this.$fields.length &&
        window.Rozier.googleClientId !== '') {
        _this.init()
    }
}

GeotagField.prototype.init = function () {
    var _this = this

    if (!window.Rozier.gMapLoaded && !window.Rozier.gMapLoading) {
        window.Rozier.gMapLoading = true
        var script = document.createElement('script')
        script.type = 'text/javascript'
        script.src = '//maps.googleapis.com/maps/api/js?key=' + window.Rozier.googleClientId +
            '&callback=initializeGeotagFields'
        document.body.appendChild(script)
    } else if (window.Rozier.gMapLoaded && !window.Rozier.gMapLoading) {
        _this.bindFields()
    }
}

GeotagField.prototype.bindFields = function () {
    var _this = this

    _this.geocoder = new window.google.maps.Geocoder()

    _this.$fields.each(function (index, element) {
        _this.bindSingleField(element)
    })
}

GeotagField.prototype.bindSingleField = function (element) {
    var _this = this
    var $input = $(element)
    var $label = $input.parent().find('.uk-form-label')
    var labelText = $label[0].innerHTML
    var jsonCode = null

    if (window.Rozier.defaultMapLocation) {
        jsonCode = window.Rozier.defaultMapLocation
    } else {
        jsonCode = {'lat': 45.769785, 'lng': 4.833967, 'zoom': 14} // default location
    }

    var fieldId = 'geotag-canvas-' + GeotagField.uniqid()
    var fieldAddressId = fieldId + '-address'
    var resetButtonId = fieldId + '-reset'

    var mapOptions = {
        center: new window.google.maps.LatLng(jsonCode.lat, jsonCode.lng),
        zoom: jsonCode.zoom,
        scrollwheel: false,
        styles: window.Rozier.mapsStyle
    }

    /*
     * prepare DOM
     */
    $input.hide()
    $label.hide()
    $input.attr('data-geotag-canvas', fieldId)
    $input.after('<div class="rz-geotag-canvas" id="' + fieldId + '" style="width: 100%; height: 400px;"></div>')

    // Geocode input text
    var metaDOM = [
        '<nav class="geotag-widget-nav uk-navbar rz-geotag-meta">',
        '<ul class="uk-navbar-nav">',
        '<li class="uk-navbar-brand"><i class="uk-icon-rz-map-marker"></i>',
        '<li class="uk-navbar-brand label">' + labelText + '</li>',
        '</ul>',
        '<div class="uk-navbar-content uk-navbar-flip">',
        '<div class="geotag-widget-quick-creation uk-button-group">',
        '<input class="rz-geotag-address" id="' + fieldAddressId + '" type="text" value="" />',
        '<a id="' + resetButtonId + '" class="uk-button uk-button-content uk-button-table-delete rz-geotag-reset" title="' + window.Rozier.messages.geotag.resetMarker + '" data-uk-tooltip="{animation:true}"><i class="uk-icon-rz-trash-o"></i></a>',
        '</div>',
        '</div>',
        '</nav>'
    ].join('')

    $input.after(metaDOM)

    var $geocodeInput = $('#' + fieldAddressId)
    $geocodeInput.attr('placeholder', window.Rozier.messages.geotag.typeAnAddress)
    // Reset button
    var $geocodeReset = $('#' + resetButtonId)
    $geocodeReset.hide()

    /*
     * Prepare map and marker
     */
    var map = new window.google.maps.Map(document.getElementById(fieldId), mapOptions)
    var marker = null

    if ($input.val() !== '') {
        try {
            jsonCode = JSON.parse($input.val())
            marker = _this.createMarker(jsonCode, $input, map)
            $geocodeReset.show()
        } catch (e) {
            $input.show()
            $(document.getElementById(fieldId)).hide()

            return false
        }
    } else {
        marker = new window.google.maps.Marker({
            // map:map,
            draggable: true,
            position: mapOptions.center,
            animation: window.google.maps.Animation.DROP,
            icon: window.Rozier.resourcesUrl + 'img/map_marker.png'
        })
    }

    window.google.maps.event.addListener(marker, 'dragend', $.proxy(_this.setMarkerEvent, _this, marker, $input, $geocodeReset, map))
    window.google.maps.event.addListener(map, 'click', $.proxy(_this.setMarkerEvent, _this, marker, $input, $geocodeReset, map))

    $geocodeInput.on('keypress', $.proxy(_this.requestGeocode, _this, marker, $input, $geocodeReset, map))
    $geocodeReset.on('click', $.proxy(_this.resetMarker, _this, marker, $input, $geocodeReset, map))

    window.Rozier.$window.on('resize', $.proxy(_this.resetMap, this, map, marker, mapOptions))
    _this.resetMap(map, marker, mapOptions, null)
}

GeotagField.prototype.resetMap = function (map, marker, mapOptions, event) {
    window.setTimeout(function () {
        window.google.maps.event.trigger(map, 'resize')

        if (marker !== null) {
            map.panTo(marker.getPosition())
        } else {
            map.panTo(mapOptions.center)
        }
    }, 300)
}

/**
 * @param Marker marker
 * @param jQuery DOM $input
 * @param Map map
 * @param Event event
 */
GeotagField.prototype.resetMarker = function (marker, $input, $geocodeReset, map, event) {
    marker.setMap(null)
    $input.val('')

    $geocodeReset.hide()

    return false
}
/**
 * @param Marker marker
 * @param jQuery DOM $input
 * @param Map map
 * @param Event event
 */
GeotagField.prototype.setMarkerEvent = function (marker, $input, $geocodeReset, map, event) {
    var _this = this

    _this.setMarker(marker, $input, $geocodeReset, map, event.latLng)
}

/**
 * @param Marker marker
 * @param jQuery DOM $input
 * @param Map map
 * @param Event event
 */
GeotagField.prototype.setMarker = function (marker, $input, $geocodeReset, map, latlng) {
    marker.setPosition(latlng)
    marker.setMap(map)

    map.panTo(latlng)

    var geoCode = {
        'lat': latlng.lat(),
        'lng': latlng.lng(),
        'zoom': map.getZoom()
    }

    $input.val(JSON.stringify(geoCode))

    $geocodeReset.show()
}

/**
 * @param  Object geocode
 * @param  jQuery DOM $input
 * @param  Map map
 *
 * @return Marker
 */
GeotagField.prototype.createMarker = function (geocode, $input, map) {
    var latlng = new window.google.maps.LatLng(geocode.lat, geocode.lng)
    var marker = new window.google.maps.Marker({
        map: map,
        draggable: true,
        animation: window.google.maps.Animation.DROP,
        position: latlng,
        icon: window.Rozier.resourcesUrl + 'img/map_marker.png'
    })

    map.panTo(latlng)
    map.setZoom(geocode.zoom)

    /*
     * Add custom fields to markers
     */
    marker.zoom = geocode.zoom
    if (typeof geocode.name !== 'undefined') {
        marker.name = geocode.name
    }

    return marker
}

GeotagField.prototype.requestGeocode = function (marker, $input, $geocodeReset, map, event) {
    var _this = this

    var address = event.currentTarget.value

    if (event.which === 13) {
        event.preventDefault()

        _this.geocoder.geocode({'address': address}, function (results, status) {
            if (status === window.google.maps.GeocoderStatus.OK) {
                _this.setMarker(marker, $input, $geocodeReset, map, results[0].geometry.location)
            } else {
                console.err('Geocode was not successful for the following reason: ' + status)
            }
        })

        return false
    }
}

export const initializeGeotagFields = function () {
    window.Rozier.gMapLoaded = true
    window.Rozier.gMapLoading = false

    /* eslint-disable no-new */
    new GeotagField()
    new MultiGeotagField()
}

GeotagField.uniqid = function () {
    var n = new Date()
    return n.getTime()
}
