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
 * @file LeafletGeotagField.js
 * @author Ambroise Maupate
 *
 */

import $ from 'jquery'
import { Map, Marker, LatLng, TileLayer, Icon } from 'leaflet'
import GeoCodingService from '../services/GeoCodingService'

export default class LeafletGeotagField {
    constructor () {
        this.$fields = $('input.rz-geotag-field')
        this.geocoder = null

        if (this.$fields.length) {
            this.init()
        }
    }

    init () {
        if (!this.$fields.hasClass('is-enable')) {
            this.$fields.addClass('is-enable')
            this.bindFields()
        }
    }

    unbind () {

    }

    bindFields () {
        this.geocoder = null
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
        let mapOptions = {
            center: this.createLatLng(jsonCode),
            zoom: jsonCode.zoom,
            styles: window.Rozier.mapsStyle
        }
        let map = this.createMap(fieldId, mapOptions)
        let marker = null

        if ($input.val() !== '') {
            try {
                jsonCode = JSON.parse($input.val())
                marker = this.createMarker(jsonCode, map)
                $geocodeReset.show()
            } catch (e) {
                $input.show()
                $(document.getElementById(fieldId)).hide()
                return false
            }
        } else {
            marker = this.createMarker(jsonCode, map)
        }

        marker.on('dragend', $.proxy(this.setMarkerEvent, this, marker, $input, $geocodeReset, map))
        map.on('click', $.proxy(this.setMarkerEvent, this, marker, $input, $geocodeReset, map))

        $geocodeInput.on('keypress', $.proxy(this.requestGeocode, this, marker, $input, $geocodeReset, map))
        $geocodeReset.on('click', $.proxy(this.resetMarker, this, marker, $input, $geocodeReset, map))
        window.Rozier.$window.on('resize', $.proxy(this.resetMap, this, map, marker, mapOptions))
        window.Rozier.$window.on('pageshowend', $.proxy(this.resetMap, this, map, marker, mapOptions))
        this.resetMap(map, marker, mapOptions, null)
    }

    resetMap (map, marker, mapOptions) {
        window.setTimeout(() => {
            map.invalidateSize(true)
            if (marker !== null) {
                map.panTo(marker.getLatLng())
            } else {
                map.panTo(mapOptions.center)
            }
        }, 400)
    }

    /**
     * @param {Object} marker
     * @param {jQuery} $input
     * @param $geocodeReset
     * @param {Map} map
     * @param {Event} event
     */
    resetMarker (marker, $input, $geocodeReset, map, event) {
        marker.removeFrom(map)
        $input.val('')
        $geocodeReset.hide()

        return false
    }

    /**
     * @param {Marker} marker
     * @param {jQuery} $input
     * @param $geocodeReset
     * @param {Map} map
     * @param {Event} event
     */
    setMarkerEvent (marker, $input, $geocodeReset, map, event) {
        console.debug(event)
        if (typeof event.latlng !== 'undefined') {
            this.setMarker(marker, $input, $geocodeReset, map, event.latlng)
        } else if (marker !== null) {
            let latlng = marker.getLatLng()
            map.panTo(latlng)
            this.applyGeocode($input, $geocodeReset, latlng, map.getZoom())
        }
    }

    /**
     * @param {Marker} marker
     * @param $input
     * @param $geocodeReset
     * @param {Map} map
     * @param {LatLng} latlng
     */
    setMarker (marker, $input, $geocodeReset, map, latlng) {
        marker.setLatLng(latlng)
        marker.addTo(map)
        map.panTo(latlng)
        this.applyGeocode($input, $geocodeReset, marker.getLatLng(), map.getZoom())
    }

    /**
     *
     * @param $input
     * @param $geocodeReset
     * @param {LatLng} latlng
     * @param {Number} zoom
     */
    applyGeocode ($input, $geocodeReset, latlng, zoom) {
        let geoCode = {
            'lat': latlng.lat,
            'lng': latlng.lng,
            'zoom': zoom
        }
        $input.val(JSON.stringify(geoCode))
        $geocodeReset.show()
    }

    /**
     * @param {Object|LatLng} geocode
     * @param {jQuery} $input
     * @param {Map} map
     *
     * @return Marker
     */
    createMarker (geocode, map) {
        let latlng = null
        if (geocode instanceof LatLng) {
            latlng = geocode
        } else {
            latlng = this.createLatLng(geocode)
        }
        let marker = new Marker(latlng, {
            icon: this.createIcon(),
            draggable: true
        }).addTo(map)

        map.panTo(latlng)
        map.setZoom(geocode.zoom)

        marker.alt = geocode.zoom
        if (typeof geocode.name !== 'undefined') {
            marker.name = geocode.name
        }

        return marker
    }

    requestGeocode (marker, $input, $geocodeReset, map, event) {
        let address = event.currentTarget.value

        if (event.which === 13) {
            event.preventDefault()
            GeoCodingService.geoCode(address).then((response) => {
                if (response !== null) {
                    const latlng = new LatLng(response.lat, response.lon)
                    this.setMarker(marker, $input, $geocodeReset, map, latlng)
                } else {
                    console.error('Geocode was not successful.')
                }
            })

            return false
        }
    }

    uniqid () {
        let n = new Date()
        return n.getTime()
    }

    /**
     *
     * @param fieldId
     * @param mapOptions
     * @returns {*}
     */
    createMap (fieldId, mapOptions) {
        const map = new Map(document.getElementById(fieldId)).setView(mapOptions.center, mapOptions.zoom)
        const osmLayer = new TileLayer(window.Rozier.leafletMapTileUrl, {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 19
        })
        map.addLayer(osmLayer)
        return map
    }

    createLatLng (jsonCode) {
        return new LatLng(jsonCode.lat, jsonCode.lng, jsonCode.zoom)
    }

    createIcon () {
        return new Icon({
            iconUrl: window.Rozier.resourcesUrl + 'assets/img/marker.png',
            iconRetinaUrl: window.Rozier.resourcesUrl + 'assets/img/marker@2x.png',
            shadowUrl: window.Rozier.resourcesUrl + 'assets/img/marker_shadow.png',
            shadowRetinaUrl: window.Rozier.resourcesUrl + 'assets/img/marker_shadow@2x.png',
            iconSize: [22, 30], // size of the icon
            shadowSize: [25, 22], // size of the shadow
            iconAnchor: [11, 30], // point of the icon which will correspond to marker's location
            shadowAnchor: [2, 22]  // the same for the shadow
        })
    }
}
