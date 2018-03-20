import $ from 'jquery'

export default class GeotagField {
    constructor () {
        this.$fields = $('input.rz-geotag-field')
        this.geocoder = null

        if (this.$fields.length &&
            window.Rozier.googleClientId !== '') {
            this.init()
        }
    }

    init () {
        if (!window.Rozier.gMapLoaded && !window.Rozier.gMapLoading) {
            window.Rozier.gMapLoading = true
            let script = document.createElement('script')
            script.type = 'text/javascript'
            script.src = '//maps.googleapis.com/maps/api/js?key=' + window.Rozier.googleClientId + '&callback=initializeGeotagFields'
            document.body.appendChild(script)
        } else if (window.Rozier.gMapLoaded && !window.Rozier.gMapLoading && !this.$fields.hasClass('is-enable')) {
            this.$fields.addClass('is-enable')
            this.bindFields()
        }
    }

    unbind () {

    }

    bindFields () {
        this.geocoder = new window.google.maps.Geocoder()
        this.$fields.each((index, element) => {
            this.bindSingleField(element)
        })
    }

    bindSingleField (element) {
        let $input = $(element)
        let $label = $input.parent().find('.uk-form-label')
        let labelText = $label[0].innerHTML
        let jsonCode = null

        if (window.Rozier.defaultMapLocation) {
            jsonCode = window.Rozier.defaultMapLocation
        } else {
            jsonCode = {'lat': 45.769785, 'lng': 4.833967, 'zoom': 14} // default location
        }

        let fieldId = 'geotag-canvas-' + this.uniqid()
        let fieldAddressId = fieldId + '-address'
        let resetButtonId = fieldId + '-reset'

        let mapOptions = {
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
        let metaDOM = [
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

        let $geocodeInput = $('#' + fieldAddressId)
        $geocodeInput.attr('placeholder', window.Rozier.messages.geotag.typeAnAddress)
        // Reset button
        let $geocodeReset = $('#' + resetButtonId)
        $geocodeReset.hide()

        /*
         * Prepare map and marker
         */
        let map = new window.google.maps.Map(document.getElementById(fieldId), mapOptions)
        let marker = null

        if ($input.val() !== '') {
            try {
                jsonCode = JSON.parse($input.val())
                marker = this.createMarker(jsonCode, $input, map)
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

        window.google.maps.event.addListener(marker, 'dragend', $.proxy(this.setMarkerEvent, this, marker, $input, $geocodeReset, map))
        window.google.maps.event.addListener(map, 'click', $.proxy(this.setMarkerEvent, this, marker, $input, $geocodeReset, map))

        $geocodeInput.on('keypress', $.proxy(this.requestGeocode, this, marker, $input, $geocodeReset, map))
        $geocodeReset.on('click', $.proxy(this.resetMarker, this, marker, $input, $geocodeReset, map))

        window.Rozier.$window.on('resize', $.proxy(this.resetMap, this, map, marker, mapOptions))
        this.resetMap(map, marker, mapOptions, null)
    }

    resetMap (map, marker, mapOptions, event) {
        window.setTimeout(() => {
            window.google.maps.event.trigger(map, 'resize')

            if (marker !== null) {
                map.panTo(marker.getPosition())
            } else {
                map.panTo(mapOptions.center)
            }
        }, 300)
    }

    /**
     * @param {Object} marker
     * @param {jQuery} $input
     * @param $geocodeReset
     * @param {Map} map
     * @param {Event} event
     */
    resetMarker (marker, $input, $geocodeReset, map, event) {
        marker.setMap(null)
        $input.val('')

        $geocodeReset.hide()

        return false
    }

    /**
     * @param {Object} marker
     * @param {jQuery} $input
     * @param $geocodeReset
     * @param {Map} map
     * @param {Event} event
     */
    setMarkerEvent (marker, $input, $geocodeReset, map, event) {
        this.setMarker(marker, $input, $geocodeReset, map, event.latLng)
    }

    /**
     * @param {Object} marker
     * @param $input
     * @param $geocodeReset
     * @param {Map} map
     * @param {Object} latlng
     */
    setMarker (marker, $input, $geocodeReset, map, latlng) {
        marker.setPosition(latlng)
        marker.setMap(map)

        map.panTo(latlng)

        let geoCode = {
            'lat': latlng.lat(),
            'lng': latlng.lng(),
            'zoom': map.getZoom()
        }

        $input.val(JSON.stringify(geoCode))

        $geocodeReset.show()
    }

    /**
     * @param {Object} geocode
     * @param {jQuery} $input
     * @param {Map} map
     *
     * @return Marker
     */
    createMarker (geocode, $input, map) {
        let latlng = new window.google.maps.LatLng(geocode.lat, geocode.lng)
        let marker = new window.google.maps.Marker({
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

    requestGeocode (marker, $input, $geocodeReset, map, event) {
        let address = event.currentTarget.value

        if (event.which === 13) {
            event.preventDefault()

            this.geocoder.geocode({'address': address}, (results, status) => {
                if (status === window.google.maps.GeocoderStatus.OK) {
                    this.setMarker(marker, $input, $geocodeReset, map, results[0].geometry.location)
                } else {
                    console.err('Geocode was not successful for the following reason: ' + status)
                }
            })

            return false
        }
    }

    uniqid () {
        let n = new Date()
        return n.getTime()
    }
}
