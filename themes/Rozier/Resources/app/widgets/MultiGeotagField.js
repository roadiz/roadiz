import $ from 'jquery'
import GeotagField from './GeotagField'

export default class MultiGeotagField extends GeotagField {
    constructor () {
        super()

        this.$fields = $('.rz-multi-geotag-field')
        this.geocoder = null

        if (this.$fields.length &&
            window.Rozier.googleClientId !== '') {
            this.init()
        }
    }

    /**
     * @param markers
     * @param $input
     * @param $geocodeReset
     * @param map
     * @param $selector
     * @param event
     * @returns {boolean}
     */
    resetMarker (markers, $input, $geocodeReset, map, $selector, event) {
        $input.val('')
        for (let i = markers.length - 1; i >= 0; i--) {
            markers[i].setMap(null)
            markers[i] = null
        }
        markers = []

        $geocodeReset.hide()
        this.syncSelector($selector, markers, map, $input)

        return false
    }

    bindSingleField (element) {
        let $input = $(element)
        let $label = $input.parent().find('.uk-form-label')
        let labelText = $label[0].innerHTML
        let jsonCode = {'lat': 45.769785, 'lng': 4.833967, 'zoom': 14} // default location
        let fieldId = 'geotag-canvas-' + this.uniqid()
        let fieldAddressId = fieldId + '-address'
        let resetButtonId = fieldId + '-reset'
        let mapOptions = {
            center: new window.google.maps.LatLng(jsonCode.lat, jsonCode.lng),
            zoom: jsonCode.zoom,
            scrollwheel: false,
            styles: window.Rozier.mapsStyle
        }

        // Prepare DOM
        $input.hide()
        $label.hide()
        $input.attr('data-geotag-canvas', fieldId)

        // Geocode input text
        let metaDOM = [
            '<nav class="geotag-widget-nav uk-navbar rz-geotag-meta">',
            '<ul class="uk-navbar-nav">',
            '<li class="uk-navbar-brand"><i class="uk-icon-rz-map-multi-marker"></i>',
            '<li class="uk-navbar-brand label">' + labelText + '</li>',
            '</ul>',
            '<div class="uk-navbar-content uk-navbar-flip">',
            '<div class="geotag-widget-quick-creation uk-button-group">',
            '<input class="rz-geotag-address" id="' + fieldAddressId + '" type="text" value="" />',
            '<a id="' + resetButtonId + '" class="uk-button uk-button-content uk-button-table-delete rz-geotag-reset" title="' + window.Rozier.messages.geotag.resetMarker + '" data-uk-tooltip="{animation:true}"><i class="uk-icon-rz-trash-o"></i></a>',
            '</div>',
            '</div>',
            '</nav>',
            '<div class="multi-geotag-group">',
            '<ul class="multi-geotag-list-markers">',
            '</ul>',
            '<div class="rz-geotag-canvas" id="' + fieldId + '"></div>',
            '</div>'
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
        let markers = []
        let $selector = $input.parent().find('.multi-geotag-list-markers')

        if ($input.val() !== '') {
            try {
                let geocodes = JSON.parse($input.val())
                let geocodeslength = geocodes.length
                for (let i = 0; i < geocodeslength; i++) {
                    markers[i] = this.createMarker(geocodes[i], $input, map)
                    window.google.maps.event.addListener(markers[i], 'dragend', $.proxy(this.setMarkerEvent, this, markers[i], markers, $input, $geocodeReset, map))
                }
                $geocodeReset.show()
            } catch (e) {
                $input.show()
                $(document.getElementById(fieldId)).hide()

                return false
            }
        }

        window.google.maps.event.addListener(map, 'click', $.proxy(this.setMarkerEvent, this, null, markers, $input, $geocodeReset, map))
        window.google.maps.event.addListener(map, 'click', $.proxy(this.syncSelector, this, $selector, markers, map, $input))

        $geocodeInput.on('keypress', $.proxy(this.requestGeocode, this, markers, $input, $geocodeReset, map, $selector))
        $geocodeReset.on('click', $.proxy(this.resetMarker, this, markers, $input, $geocodeReset, map, $selector))
        $geocodeReset.on('click', $.proxy(this.syncSelector, this, $selector, markers, map, $input))

        window.Rozier.$window.on('resize', $.proxy(this.resetMap, this, map, markers, mapOptions))
        this.resetMap(map, markers, mapOptions, null)
        this.syncSelector($selector, markers, map, $input)
    }

    syncSelector ($selector, markers, map, $input) {
        let _this = this

        $selector.empty()
        let markersLength = markers.length
        for (let i = 0; i < markersLength; i++) {
            if (markers[i] !== null) {
                let geocode = this.getGeocodeFromMarker(markers[i])

                $selector.append([
                    '<li>',
                    '<span class="multi-geotag-marker-name">',
                    geocode.name ? geocode.name : ('#' + i),
                    '</span>',
                    '<a class="button rz-multi-geotag-center" data-geocode-id="' + i + '" data-geocode="' + JSON.stringify(geocode) + '"><i class="uk-icon-rz-marker"></i></a>',
                    '<a class="button rz-multi-geotag-remove" data-geocode-id="' + i + '"><i class="uk-icon-rz-trash-o"></i></a>',
                    '</li>'
                ].join(''))

                let $centerBtn = $selector.find('.rz-multi-geotag-center[data-geocode-id="' + i + '"]')
                let $removeBtn = $selector.find('.rz-multi-geotag-remove[data-geocode-id="' + i + '"]')

                $centerBtn.on('click', $.proxy(this.centerMap, _this, map, markers[i]))
                $removeBtn.on('click', $.proxy(this.removeMarker, _this, map, markers, i, $selector, $input))
            }
        }
    }

    removeMarker (map, markers, index, $selector, $input, event) {
        markers[index].setMap(null)
        markers[index] = null

        this.syncSelector($selector, markers, map, $input)
        this.writeMarkers(markers, $input)

        return false
    }

    getGeocodeFromMarker (marker) {
        return {
            'lat': marker.getPosition().lat(),
            'lng': marker.getPosition().lng(),
            'zoom': marker.zoom,
            'name': marker.name
        }
    }

    resetMap (map, markers, mapOptions, event) {
        window.setTimeout(() => {
            window.google.maps.event.trigger(map, 'resize')

            if (typeof markers !== 'undefined' && markers.length > 0) {
                map.fitBounds(this.getMediumLatLng(markers))
            } else {
                map.panTo(mapOptions.center)
            }
        }, 300)
    }

    centerMap (map, marker, event) {
        window.setTimeout(() => {
            window.google.maps.event.trigger(map, 'resize')

            if (typeof marker !== 'undefined') {
                map.panTo(marker.getPosition())
            }
            if (typeof marker.zoom !== 'undefined') {
                map.setZoom(marker.zoom)
            }
        }, 300)

        return false
    }

    getMediumLatLng (markers) {
        let bounds = new window.google.maps.LatLngBounds()
        for (let index in markers) {
            bounds.extend(markers[index].getPosition())
        }

        return bounds
    }

    /**
     * @param marker
     * @param markers
     * @param $input
     * @param $geocodeReset
     * @param map
     * @param event
     */
    setMarkerEvent (marker, markers, $input, $geocodeReset, map, event) {
        this.setMarker(marker, markers, $input, $geocodeReset, map, event.latLng)
    }

    /**
     * @param marker
     * @param markers
     * @param $input
     * @param $geocodeReset
     * @param map
     * @param latlng
     * @param name
     * @returns {Object}
     */
    setMarker (marker, markers, $input, $geocodeReset, map, latlng, name) {
        if (marker === null) {
            marker = new window.google.maps.Marker({
                map: map,
                draggable: true,
                animation: window.google.maps.Animation.DROP,
                position: latlng,
                icon: window.Rozier.resourcesUrl + 'assets/img/map_marker.png'
            })
        }

        marker.setPosition(latlng)
        marker.setMap(map)
        marker.zoom = map.getZoom()
        marker.name = name
        map.panTo(latlng)
        markers.push(marker)

        this.writeMarkers(markers, $input)

        $geocodeReset.show()

        return marker
    }

    writeMarkers (markers, $input) {
        let geocodes = []

        for (let i = markers.length - 1; i >= 0; i--) {
            if (markers[i] !== null) {
                geocodes.push(this.getGeocodeFromMarker(markers[i]))
            }
        }

        $input.val(JSON.stringify(geocodes))
    }

    requestGeocode (markers, $input, $geocodeReset, map, $selector, event) {
        let address = event.currentTarget.value

        if (event.which === 13) {
            event.preventDefault()

            this.geocoder.geocode({'address': address}, (results, status) => {
                if (status === window.google.maps.GeocoderStatus.OK) {
                    this.setMarker(null, markers, $input, $geocodeReset, map, results[0].geometry.location, address)
                    this.syncSelector($selector, markers, map, $input)
                } else {
                    console.err('Geocode was not successful for the following reason: ' + status)
                }
            })

            return false
        }
    }
}
