import $ from 'jquery'

export const toType = obj => {
    return ({}).toString.call(obj).match(/\s([a-zA-Z]+)/)[1].toLowerCase()
};

// Avoid `console` errors in browsers that lack a console.
(function () {
    let method
    let noop = () => {}
    let methods = [
        'assert', 'clear', 'count', 'debug', 'dir', 'dirxml', 'error',
        'exception', 'group', 'groupCollapsed', 'groupEnd', 'info', 'log',
        'markTimeline', 'profile', 'profileEnd', 'table', 'time', 'timeEnd',
        'timeStamp', 'trace', 'warn'
    ]
    let length = methods.length
    let console = (window.console = window.console || {})

    while (length--) {
        method = methods[length]

        // Only stub undefined methods.
        if (!console[method]) {
            console[method] = noop
        }
    }
}())

// Strip tags
export const stripTags = (input, allowed) => {
  //  discuss at: http://phpjs.org/functions/strip_tags/
  // original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
  // improved by: Luke Godfrey
  // improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
  //    input by: Pul
  //    input by: Alex
  //    input by: Marc Palau
  //    input by: Brett Zamir (http://brett-zamir.me)
  //    input by: Bobby Drake
  //    input by: Evertjan Garretsen
  // bugfixed by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
  // bugfixed by: Onno Marsman
  // bugfixed by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
  // bugfixed by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
  // bugfixed by: Eric Nagel
  // bugfixed by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
  // bugfixed by: Tomasz Wesolowski
  //  revised by: Rafał Kukawski (http://blog.kukawski.pl/)
  //   example 1: strip_tags('<p>Kevin</p> <br /><b>van</b> <i>Zonneveld</i>', '<i><b>');
  //   returns 1: 'Kevin <b>van</b> <i>Zonneveld</i>'
  //   example 2: strip_tags('<p>Kevin <img src="someimage.png" onmouseover="someFunction()">van <i>Zonneveld</i></p>', '<p>');
  //   returns 2: '<p>Kevin van Zonneveld</p>'
  //   example 3: strip_tags("<a href='http://kevin.vanzonneveld.net'>Kevin van Zonneveld</a>", "<a>");
  //   returns 3: "<a href='http://kevin.vanzonneveld.net'>Kevin van Zonneveld</a>"
  //   example 4: strip_tags('1 < 5 5 > 1');
  //   returns 4: '1 < 5 5 > 1'
  //   example 5: strip_tags('1 <br/> 1');
  //   returns 5: '1  1'
  //   example 6: strip_tags('1 <br/> 1', '<br>');
  //   returns 6: '1 <br/> 1'
  //   example 7: strip_tags('1 <br/> 1', '<br><br/>');
  //   returns 7: '1 <br/> 1'

    allowed = (((allowed || '') + '')
      .toLowerCase()
      .match(/<[a-z][a-z0-9]*>/g) || [])
    .join('') // making sure the allowed arg is a string containing only tags in lowercase (<a><b><c>)
    let tags = /<\/?([a-z][a-z0-9]*)\b[^>]*>/gi
    let commentsAndPhpTags = /<!--[\s\S]*?-->|<\?(?:php)?[\s\S]*?\?>/gi
    return input.replace(commentsAndPhpTags, '')
    .replace(tags, ($0, $1) => {
        return allowed.indexOf('<' + $1.toLowerCase() + '>') > -1 ? $0 : ''
    })
}

// Add class
export const addClass = (el, classToAdd) => {
    if (el) {
        if (el.classList) {
            el.classList.add(classToAdd)
        } else {
            el.className += ' ' + classToAdd
        }
    }
}

// Remove class
export const removeClass = (el, classToRemove) => {
    if (el) {
        if (el.classList) {
            el.classList.remove(classToRemove)
        } else {
            el.className = el.className.replace(new RegExp('(^|\\b)' + classToRemove.split(' ').join('|') + '(\\b|$)', 'gi'), '')

            let posLastCar = el.className.length - 1

            if (el.className[posLastCar] === ' ') {
                el.className = el.className.substring(0, posLastCar)
            }
        }
    }
}

/*
 * Pointer Events Polyfill: Adds support for the style attribute "pointer-events: none" to browsers without this feature (namely, IE).
 * (c) 2013, Kent Mewhort, licensed under BSD. See LICENSE.txt for details.
 */
// constructor
export class PointerEventsPolyfill {
    constructor (options) {
        // set defaults
        this.options = {
            selector: '*',
            mouseEvents: ['click', 'dblclick', 'mousedown', 'mouseup'],
            usePolyfillIf: () => {
                if (navigator.appName === 'Microsoft Internet Explorer') {
                    let agent = navigator.userAgent
                    if (agent.match(/MSIE ([0-9]{1,}[.0-9]{0,})/) !== null) {
                        let version = parseFloat(RegExp.$1)
                        if (version < 11) { return true }
                    }
                }
                return false
            }
        }

        if (options) {
            let obj = this
            $.each(options, (k, v) => {
                obj.options[k] = v
            })
        }

        if (this.options.usePolyfillIf()) {
            this.registerMouseEvents()
        }
    }

    // singleton initializer
    initialize (options) {
        if (!PointerEventsPolyfill.singleton) {
            PointerEventsPolyfill.singleton = new PointerEventsPolyfill(options)
        }

        return PointerEventsPolyfill.singleton
    }

    // handle mouse events w/ support for pointer-events: none
    registerMouseEvents () {
        // register on all elements (and all future elements) matching the selector
        $(document).on(this.options.mouseEvents.join(' '), this.options.selector, (e) => {
            if ($(this).css('pointer-events') === 'none') {
                // peak at the element below
                let origDisplayAttribute = $(this).css('display')
                $(this).css('display', 'none')

                let underneathElem = document.elementFromPoint(e.clientX, e.clientY)

                if (origDisplayAttribute) {
                    $(this)
                        .css('display', origDisplayAttribute)
                } else { $(this).css('display', '') }

                // fire the mouse event on the element below
                e.target = underneathElem
                $(underneathElem).trigger(e)

                return false
            }

            return true
        })
    }
}
