/*
 * Copyright © 2019, Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * Except as contained in this notice, the name of the roadiz shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from Ambroise Maupate and Julien Blanchet.
 *
 * @file MultiLeafletGeotagField.js
 * @author Ambroise Maupate
 *
 */

import $ from 'jquery'
import LeafletGeotagField from './LeafletGeotagField'
import { LatLng, LatLngBounds } from 'leaflet'
import GeoCodingService from '../services/GeoCodingService'

export default class MultiLeafletGeotagField extends LeafletGeotagField {
    constructor () {
        super()
        this.$fields = $('.rz-multi-geotag-field')
        this.geocoder = null

        if (this.$fields.length) {
            this.init()
        }
    }

    /**
     * @param markers
     * @param $input
     * @param $geocodeReset
     * @param map
     * @param $selector
     * @returns {boolean}
     */
    resetMarker (markers, $input, $geocodeReset, map, $selector) {
        $input.val('')
        for (let i = markers.length - 1; i >= 0; i--) {
            markers[i].removeFrom(map)
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
        let jsonCode = {'lat': 45.769785, 'lng': 4.833967, 'zoom': 14}
        let fieldId = 'geotag-canvas-' + this.uniqid()
        let fieldAddressId = fieldId + '-address'
        let resetButtonId = fieldId + '-reset'
        let mapOptions = {
            center: this.createLatLng(jsonCode),
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
        let map = this.createMap(fieldId, mapOptions)
        let markers = []
        let $selector = $input.parent().find('.multi-geotag-list-markers')

        if ($input.val() !== '') {
            try {
                let geocodes = JSON.parse($input.val())
                let geocodeslength = geocodes.length
                for (let i = 0; i < geocodeslength; i++) {
                    markers[i] = this.createMarker(geocodes[i], map)
                    markers[i].on('dragend', $.proxy(this.setMarkerEvent, this, markers[i], markers, $input, $geocodeReset, map))
                }
                $geocodeReset.show()
            } catch (e) {
                $input.show()
                $(document.getElementById(fieldId)).hide()

                return false
            }
        }

        map.on('click', $.proxy(this.setMarkerEvent, this, null, markers, $input, $geocodeReset, map))
        map.on('click', $.proxy(this.syncSelector, this, $selector, markers, map, $input))
        $geocodeInput.on('keypress', $.proxy(this.requestGeocode, this, markers, $input, $geocodeReset, map, $selector))
        $geocodeReset.on('click', $.proxy(this.resetMarker, this, markers, $input, $geocodeReset, map, $selector))
        $geocodeReset.on('click', $.proxy(this.syncSelector, this, $selector, markers, map, $input))
        window.Rozier.$window.on('resize', $.proxy(this.resetMap, this, map, markers, mapOptions))
        window.Rozier.$window.on('pageshowend', $.proxy(this.resetMap, this, map, markers, mapOptions))
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

    removeMarker (map, markers, index, $selector, $input) {
        markers[index].removeFrom(map)
        markers[index] = null

        this.syncSelector($selector, markers, map, $input)
        this.writeMarkers(markers, $input)

        return false
    }

    getGeocodeFromMarker (marker) {
        return {
            'lat': marker.getLatLng().lat,
            'lng': marker.getLatLng().lng,
            'zoom': marker.getLatLng().alt,
            'name': marker.name
        }
    }

    resetMap (map, markers, mapOptions, event) {
        window.setTimeout(() => {
            map.invalidateSize(true)
            if (typeof markers !== 'undefined' && markers.length > 0) {
                map.fitBounds(this.getMediumLatLng(markers))
            } else {
                map.panTo(mapOptions.center)
            }
        }, 400)
    }

    /**
     *
     * @param {Map} map
     * @param {Marker} marker
     * @returns {boolean}
     */
    centerMap (map, marker) {
        window.setTimeout(() => {
            if (typeof marker !== 'undefined') {
                map.panTo(marker.getLatLng())
                map.setZoom(marker.getLatLng().alt)
            }
        }, 300)

        return false
    }

    /**
     *
     * @param markers
     * @returns {*|LatLngBounds}
     */
    getMediumLatLng (markers) {
        let bounds = new LatLngBounds()
        for (const marker of markers) {
            bounds.extend(marker.getLatLng())
        }
        return bounds
    }

    /**
     * @param {Marker} marker
     * @param {Array} markers
     * @param $input
     * @param $geocodeReset
     * @param {Map} map
     * @param event
     */
    setMarkerEvent (marker, markers, $input, $geocodeReset, map, event) {
        if (typeof event.latlng !== 'undefined') {
            this.setMarker(marker, markers, $input, $geocodeReset, map, event.latlng)
        } else if (marker !== null) {
            let latlng = marker.getLatLng()
            map.panTo(latlng)
            this.writeMarkers(markers, $input)
        }
    }

    /**
     * @param {Marker} marker
     * @param {Array<Marker>} markers
     * @param $input
     * @param $geocodeReset
     * @param {Map} map
     * @param {LatLng} latlng
     * @param name
     * @returns {Object}
     */
    setMarker (marker, markers, $input, $geocodeReset, map, latlng, name) {
        latlng.zoom = map.getZoom()
        latlng.alt = map.getZoom()

        if (marker === null) {
            marker = this.createMarker(latlng, map)
        } else {
            marker.setLatLng(latlng)
            marker.addTo(map)
        }

        marker.name = name
        map.panTo(latlng)
        markers.push(marker)
        this.writeMarkers(markers, $input)
        $geocodeReset.show()

        return marker
    }

    writeMarkers (markers, $input) {
        let geocodes = []
        for (const marker of markers) {
            if (marker !== null) {
                geocodes.push(this.getGeocodeFromMarker(marker))
            }
        }
        $input.val(JSON.stringify(geocodes))
    }

    requestGeocode (markers, $input, $geocodeReset, map, $selector, event) {
        let address = event.currentTarget.value

        if (event.which === 13) {
            event.preventDefault()
            GeoCodingService.geoCode(address).then((response) => {
                if (response !== null) {
                    const latlng = new LatLng(response.lat, response.lon, map.getZoom())
                    this.setMarker(null, markers, $input, $geocodeReset, map, latlng, response.display_name)
                    this.syncSelector($selector, markers, map, $input)
                } else {
                    console.error('Geocode was not successful.')
                }
            })

            return false
        }
    }
}
