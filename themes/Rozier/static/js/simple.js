/******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/
/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId]) {
/******/ 			return installedModules[moduleId].exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			i: moduleId,
/******/ 			l: false,
/******/ 			exports: {}
/******/ 		};
/******/
/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/
/******/ 		// Flag the module as loaded
/******/ 		module.l = true;
/******/
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/
/******/
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;
/******/
/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;
/******/
/******/ 	// define getter function for harmony exports
/******/ 	__webpack_require__.d = function(exports, name, getter) {
/******/ 		if(!__webpack_require__.o(exports, name)) {
/******/ 			Object.defineProperty(exports, name, {
/******/ 				configurable: false,
/******/ 				enumerable: true,
/******/ 				get: getter
/******/ 			});
/******/ 		}
/******/ 	};
/******/
/******/ 	// getDefaultExport function for compatibility with non-harmony modules
/******/ 	__webpack_require__.n = function(module) {
/******/ 		var getter = module && module.__esModule ?
/******/ 			function getDefault() { return module['default']; } :
/******/ 			function getModuleExports() { return module; };
/******/ 		__webpack_require__.d(getter, 'a', getter);
/******/ 		return getter;
/******/ 	};
/******/
/******/ 	// Object.prototype.hasOwnProperty.call
/******/ 	__webpack_require__.o = function(object, property) { return Object.prototype.hasOwnProperty.call(object, property); };
/******/
/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "";
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = "../Resources/app/simple.js");
/******/ })
/************************************************************************/
/******/ ({

/***/ "../Resources/app/components/login/login.js":
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var _jquery = __webpack_require__("jquery");

var _jquery2 = _interopRequireDefault(_jquery);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

(function () {
    var onLoad = function onLoad(data) {
        var $splashContainer = (0, _jquery2.default)('#splash-container');
        $splashContainer.css({
            'background-image': 'url(' + data.url + ')'
        });
        $splashContainer.addClass('visible');
    };

    var requestImage = function requestImage() {
        _jquery2.default.ajax({
            url: window.RozierRoot.routes.splashRequest,
            async: true,
            type: 'GET',
            cache: false,
            dataType: 'json'
        }).done(function (data) {
            if (data === false) {
                requestImage();
            } else if (typeof data.url !== 'undefined') {
                var myImage = new Image(window.width, window.height);
                myImage.src = data.url;
                myImage.onload = _jquery2.default.proxy(onLoad, this, data);
            }
        });
    };

    if (typeof window.RozierRoot.routes.splashRequest !== 'undefined') {
        requestImage();
    }
})();

/***/ }),

/***/ "../Resources/app/simple.js":
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var _uikit = __webpack_require__("../bower_components/uikit/js/uikit.js");

var _uikit2 = _interopRequireDefault(_uikit);

__webpack_require__("../bower_components/uikit/js/components/nestable.js");

__webpack_require__("../bower_components/uikit/js/components/sortable.js");

__webpack_require__("../bower_components/uikit/js/components/datepicker.js");

__webpack_require__("../bower_components/uikit/js/components/pagination.js");

__webpack_require__("../bower_components/uikit/js/components/notify.js");

__webpack_require__("../bower_components/uikit/js/components/tooltip.js");

__webpack_require__("../Resources/app/components/login/login.js");

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

/*
 * Copyright (c) 2017. Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
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
 * Except as contained in this notice, the name of the ROADIZ shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from Ambroise Maupate and Julien Blanchet.
 *
 * @file simple.js
 * @author Adrien Scholaert <adrien@rezo-zero.com>
 */

window.UIkit = _uikit2.default;

/***/ }),

/***/ "../bower_components/uikit/js/components/datepicker.js":
/***/ (function(module, exports, __webpack_require__) {

"use strict";
/* WEBPACK VAR INJECTION */(function(global) {var __WEBPACK_AMD_DEFINE_ARRAY__, __WEBPACK_AMD_DEFINE_RESULT__;

var _typeof2 = __webpack_require__("../node_modules/babel-runtime/helpers/typeof.js");

var _typeof3 = _interopRequireDefault(_typeof2);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

/*! UIkit 2.27.4 | http://www.getuikit.com | (c) 2014 YOOtheme | MIT License */
(function (addon) {

    var component;

    if (window.UIkit2) {
        component = addon(UIkit2);
    }

    if (true) {
        !(__WEBPACK_AMD_DEFINE_ARRAY__ = [__webpack_require__("uikit")], __WEBPACK_AMD_DEFINE_RESULT__ = (function () {
            return component || addon(UIkit2);
        }).apply(exports, __WEBPACK_AMD_DEFINE_ARRAY__),
				__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));
    }
})(function (UI) {

    "use strict";

    // Datepicker

    var active = false,
        dropdown,
        moment;

    UI.component('datepicker', {

        defaults: {
            mobile: false,
            weekstart: 1,
            i18n: {
                months: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
                weekdays: ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']
            },
            format: 'YYYY-MM-DD',
            offsettop: 5,
            maxDate: false,
            minDate: false,
            pos: 'auto',
            container: 'body',
            template: function template(data, opts) {

                var content = '',
                    i;

                content += '<div class="uk-datepicker-nav">';
                content += '<a href="" class="uk-datepicker-previous"></a>';
                content += '<a href="" class="uk-datepicker-next"></a>';

                if (UI.formSelect) {

                    var currentyear = new Date().getFullYear(),
                        options = [],
                        months,
                        years,
                        minYear,
                        maxYear;

                    for (i = 0; i < opts.i18n.months.length; i++) {
                        if (i == data.month) {
                            options.push('<option value="' + i + '" selected>' + opts.i18n.months[i] + '</option>');
                        } else {
                            options.push('<option value="' + i + '">' + opts.i18n.months[i] + '</option>');
                        }
                    }

                    months = '<span class="uk-form-select">' + opts.i18n.months[data.month] + '<select class="update-picker-month">' + options.join('') + '</select></span>';

                    // --

                    options = [];

                    minYear = data.minDate ? data.minDate.year() : currentyear - 50;
                    maxYear = data.maxDate ? data.maxDate.year() : currentyear + 20;

                    for (i = minYear; i <= maxYear; i++) {
                        if (i == data.year) {
                            options.push('<option value="' + i + '" selected>' + i + '</option>');
                        } else {
                            options.push('<option value="' + i + '">' + i + '</option>');
                        }
                    }

                    years = '<span class="uk-form-select">' + data.year + '<select class="update-picker-year">' + options.join('') + '</select></span>';

                    content += '<div class="uk-datepicker-heading">' + months + ' ' + years + '</div>';
                } else {
                    content += '<div class="uk-datepicker-heading">' + opts.i18n.months[data.month] + ' ' + data.year + '</div>';
                }

                content += '</div>';

                content += '<table class="uk-datepicker-table">';
                content += '<thead>';
                for (i = 0; i < data.weekdays.length; i++) {
                    if (data.weekdays[i]) {
                        content += '<th>' + data.weekdays[i] + '</th>';
                    }
                }
                content += '</thead>';

                content += '<tbody>';
                for (i = 0; i < data.days.length; i++) {
                    if (data.days[i] && data.days[i].length) {
                        content += '<tr>';
                        for (var d = 0; d < data.days[i].length; d++) {
                            if (data.days[i][d]) {
                                var day = data.days[i][d],
                                    cls = [];

                                if (!day.inmonth) cls.push("uk-datepicker-table-muted");
                                if (day.selected) cls.push("uk-active");
                                if (day.disabled) cls.push('uk-datepicker-date-disabled uk-datepicker-table-muted');

                                content += '<td><a href="" class="' + cls.join(" ") + '" data-date="' + day.day.format() + '">' + day.day.format("D") + '</a></td>';
                            }
                        }
                        content += '</tr>';
                    }
                }
                content += '</tbody>';

                content += '</table>';

                return content;
            }
        },

        boot: function boot() {

            UI.$win.on('resize orientationchange', function () {

                if (active) {
                    active.hide();
                }
            });

            // init code
            UI.$html.on('focus.datepicker.uikit', '[data-uk-datepicker]', function (e) {

                var ele = UI.$(this);

                if (!ele.data('datepicker')) {
                    e.preventDefault();
                    UI.datepicker(ele, UI.Utils.options(ele.attr('data-uk-datepicker')));
                    ele.trigger('focus');
                }
            });

            UI.$html.on('click focus', '*', function (e) {

                var target = UI.$(e.target);

                if (active && target[0] != dropdown[0] && !target.data('datepicker') && !target.parents('.uk-datepicker:first').length) {
                    active.hide();
                }
            });
        },

        init: function init() {

            // use native datepicker on touch devices
            if (UI.support.touch && this.element.attr('type') == 'date' && !this.options.mobile) {
                return;
            }

            var $this = this;

            this.current = this.element.val() ? moment(this.element.val(), this.options.format) : moment();

            this.on('click focus', function () {
                if (active !== $this) $this.pick(this.value ? this.value : '');
            }).on('change', function () {

                if ($this.element.val() && !moment($this.element.val(), $this.options.format).isValid()) {
                    $this.element.val(moment().format($this.options.format));
                }
            });

            // init dropdown
            if (!dropdown) {

                dropdown = UI.$('<div class="uk-dropdown uk-datepicker"></div>');

                dropdown.on('click', '.uk-datepicker-next, .uk-datepicker-previous, [data-date]', function (e) {

                    e.stopPropagation();
                    e.preventDefault();

                    var ele = UI.$(this);

                    if (ele.hasClass('uk-datepicker-date-disabled')) return false;

                    if (ele.is('[data-date]')) {
                        active.current = moment(ele.data("date"));
                        active.element.val(active.current.isValid() ? active.current.format(active.options.format) : null).trigger("change");
                        active.hide();
                    } else {
                        active.add(ele.hasClass("uk-datepicker-next") ? 1 : -1, "months");
                    }
                });

                dropdown.on('change', '.update-picker-month, .update-picker-year', function () {

                    var select = UI.$(this);
                    active[select.is('.update-picker-year') ? 'setYear' : 'setMonth'](Number(select.val()));
                });

                dropdown.appendTo(this.options.container);
            }
        },

        pick: function pick(initdate) {

            var offset = this.element.offset(),
                css = { left: offset.left, right: '' };

            this.current = isNaN(initdate) ? moment(initdate, this.options.format) : moment();
            this.initdate = this.current.format("YYYY-MM-DD");

            this.update();

            if (UI.langdirection == 'right') {
                css.right = window.innerWidth - (css.left + this.element.outerWidth());
                css.left = '';
            }

            var posTop = offset.top - this.element.outerHeight() + this.element.height() - this.options.offsettop - dropdown.outerHeight(),
                posBottom = offset.top + this.element.outerHeight() + this.options.offsettop;

            css.top = posBottom;

            if (this.options.pos == 'top') {
                css.top = posTop;
            } else if (this.options.pos == 'auto' && window.innerHeight - posBottom - dropdown.outerHeight() < 0 && posTop >= 0) {
                css.top = posTop;
            }

            dropdown.css(css).show();
            this.trigger('show.uk.datepicker');

            active = this;
        },

        add: function add(unit, value) {
            this.current.add(unit, value);
            this.update();
        },

        setMonth: function setMonth(month) {
            this.current.month(month);
            this.update();
        },

        setYear: function setYear(year) {
            this.current.year(year);
            this.update();
        },

        update: function update() {

            var data = this.getRows(this.current.year(), this.current.month()),
                tpl = this.options.template(data, this.options);

            dropdown.html(tpl);

            this.trigger('update.uk.datepicker');
        },

        getRows: function getRows(year, month) {

            var opts = this.options,
                now = moment().format('YYYY-MM-DD'),
                days = [31, year % 4 === 0 && year % 100 !== 0 || year % 400 === 0 ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31][month],
                before = new Date(year, month, 1, 12).getDay(),
                data = { month: month, year: year, weekdays: [], days: [], maxDate: false, minDate: false },
                row = [];

            // We need these to be midday to avoid issues from DST transition protection.
            if (opts.maxDate !== false) {
                data.maxDate = isNaN(opts.maxDate) ? moment(opts.maxDate, opts.format).startOf('day').hours(12) : moment().add(opts.maxDate, 'days').startOf('day').hours(12);
            }

            if (opts.minDate !== false) {
                data.minDate = isNaN(opts.minDate) ? moment(opts.minDate, opts.format).startOf('day').hours(12) : moment().add(opts.minDate - 1, 'days').startOf('day').hours(12);
            }

            data.weekdays = function () {

                for (var i = 0, arr = []; i < 7; i++) {

                    var day = i + (opts.weekstart || 0);

                    while (day >= 7) {
                        day -= 7;
                    }

                    arr.push(opts.i18n.weekdays[day]);
                }

                return arr;
            }();

            if (opts.weekstart && opts.weekstart > 0) {
                before -= opts.weekstart;
                if (before < 0) {
                    before += 7;
                }
            }

            var cells = days + before,
                after = cells;

            while (after > 7) {
                after -= 7;
            }

            cells += 7 - after;

            var day, isDisabled, isSelected, isToday, isInMonth;

            for (var i = 0, r = 0; i < cells; i++) {

                day = new Date(year, month, 1 + (i - before), 12);
                isDisabled = data.minDate && data.minDate > day || data.maxDate && day > data.maxDate;
                isInMonth = !(i < before || i >= days + before);

                day = moment(day);

                isSelected = this.initdate == day.format('YYYY-MM-DD');
                isToday = now == day.format('YYYY-MM-DD');

                row.push({ selected: isSelected, today: isToday, disabled: isDisabled, day: day, inmonth: isInMonth });

                if (++r === 7) {
                    data.days.push(row);
                    row = [];
                    r = 0;
                }
            }

            return data;
        },

        hide: function hide() {

            if (active && active === this) {
                dropdown.hide();
                active = false;

                this.trigger('hide.uk.datepicker');
            }
        }
    });

    //! moment.js
    //! version : 2.8.3
    //! authors : Tim Wood, Iskren Chernev, Moment.js contributors
    //! license : MIT
    //! momentjs.com

    moment = function (undefined) {
        /************************************
            Constants
        ************************************/
        var moment,
            VERSION = '2.8.3',

        // the global-scope this is NOT the global object in Node.js
        globalScope = typeof global !== 'undefined' ? global : this,
            oldGlobalMoment,
            round = Math.round,
            hasOwnProperty = Object.prototype.hasOwnProperty,
            i,
            YEAR = 0,
            MONTH = 1,
            DATE = 2,
            HOUR = 3,
            MINUTE = 4,
            SECOND = 5,
            MILLISECOND = 6,


        // internal storage for locale config files
        locales = {},


        // extra moment internal properties (plugins register props here)
        momentProperties = [],


        // check for nodeJS
        hasModule = typeof module !== 'undefined' && module.exports,


        // ASP.NET json date format regex
        aspNetJsonRegex = /^\/?Date\((\-?\d+)/i,
            aspNetTimeSpanJsonRegex = /(\-)?(?:(\d*)\.)?(\d+)\:(\d+)(?:\:(\d+)\.?(\d{3})?)?/,


        // from http://docs.closure-library.googlecode.com/git/closure_goog_date_date.js.source.html
        // somewhat more in line with 4.4.3.2 2004 spec, but allows decimal anywhere
        isoDurationRegex = /^(-)?P(?:(?:([0-9,.]*)Y)?(?:([0-9,.]*)M)?(?:([0-9,.]*)D)?(?:T(?:([0-9,.]*)H)?(?:([0-9,.]*)M)?(?:([0-9,.]*)S)?)?|([0-9,.]*)W)$/,


        // format tokens
        formattingTokens = /(\[[^\[]*\])|(\\)?(Mo|MM?M?M?|Do|DDDo|DD?D?D?|ddd?d?|do?|w[o|w]?|W[o|W]?|Q|YYYYYY|YYYYY|YYYY|YY|gg(ggg?)?|GG(GGG?)?|e|E|a|A|hh?|HH?|mm?|ss?|S{1,4}|X|zz?|ZZ?|.)/g,
            localFormattingTokens = /(\[[^\[]*\])|(\\)?(LT|LL?L?L?|l{1,4})/g,


        // parsing token regexes
        parseTokenOneOrTwoDigits = /\d\d?/,
            // 0 - 99
        parseTokenOneToThreeDigits = /\d{1,3}/,
            // 0 - 999
        parseTokenOneToFourDigits = /\d{1,4}/,
            // 0 - 9999
        parseTokenOneToSixDigits = /[+\-]?\d{1,6}/,
            // -999,999 - 999,999
        parseTokenDigits = /\d+/,
            // nonzero number of digits
        parseTokenWord = /[0-9]*['a-z\u00A0-\u05FF\u0700-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]+|[\u0600-\u06FF\/]+(\s*?[\u0600-\u06FF]+){1,2}/i,
            // any word (or two) characters or numbers including two/three word month in arabic.
        parseTokenTimezone = /Z|[\+\-]\d\d:?\d\d/gi,
            // +00:00 -00:00 +0000 -0000 or Z
        parseTokenT = /T/i,
            // T (ISO separator)
        parseTokenTimestampMs = /[\+\-]?\d+(\.\d{1,3})?/,
            // 123456789 123456789.123
        parseTokenOrdinal = /\d{1,2}/,


        //strict parsing regexes
        parseTokenOneDigit = /\d/,
            // 0 - 9
        parseTokenTwoDigits = /\d\d/,
            // 00 - 99
        parseTokenThreeDigits = /\d{3}/,
            // 000 - 999
        parseTokenFourDigits = /\d{4}/,
            // 0000 - 9999
        parseTokenSixDigits = /[+-]?\d{6}/,
            // -999,999 - 999,999
        parseTokenSignedNumber = /[+-]?\d+/,
            // -inf - inf

        // iso 8601 regex
        // 0000-00-00 0000-W00 or 0000-W00-0 + T + 00 or 00:00 or 00:00:00 or 00:00:00.000 + +00:00 or +0000 or +00)
        isoRegex = /^\s*(?:[+-]\d{6}|\d{4})-(?:(\d\d-\d\d)|(W\d\d$)|(W\d\d-\d)|(\d\d\d))((T| )(\d\d(:\d\d(:\d\d(\.\d+)?)?)?)?([\+\-]\d\d(?::?\d\d)?|\s*Z)?)?$/,
            isoFormat = 'YYYY-MM-DDTHH:mm:ssZ',
            isoDates = [['YYYYYY-MM-DD', /[+-]\d{6}-\d{2}-\d{2}/], ['YYYY-MM-DD', /\d{4}-\d{2}-\d{2}/], ['GGGG-[W]WW-E', /\d{4}-W\d{2}-\d/], ['GGGG-[W]WW', /\d{4}-W\d{2}/], ['YYYY-DDD', /\d{4}-\d{3}/]],


        // iso time formats and regexes
        isoTimes = [['HH:mm:ss.SSSS', /(T| )\d\d:\d\d:\d\d\.\d+/], ['HH:mm:ss', /(T| )\d\d:\d\d:\d\d/], ['HH:mm', /(T| )\d\d:\d\d/], ['HH', /(T| )\d\d/]],


        // timezone chunker '+10:00' > ['10', '00'] or '-1530' > ['-15', '30']
        parseTimezoneChunker = /([\+\-]|\d\d)/gi,


        // getter and setter names
        proxyGettersAndSetters = 'Date|Hours|Minutes|Seconds|Milliseconds'.split('|'),
            unitMillisecondFactors = {
            'Milliseconds': 1,
            'Seconds': 1e3,
            'Minutes': 6e4,
            'Hours': 36e5,
            'Days': 864e5,
            'Months': 2592e6,
            'Years': 31536e6
        },
            unitAliases = {
            ms: 'millisecond',
            s: 'second',
            m: 'minute',
            h: 'hour',
            d: 'day',
            D: 'date',
            w: 'week',
            W: 'isoWeek',
            M: 'month',
            Q: 'quarter',
            y: 'year',
            DDD: 'dayOfYear',
            e: 'weekday',
            E: 'isoWeekday',
            gg: 'weekYear',
            GG: 'isoWeekYear'
        },
            camelFunctions = {
            dayofyear: 'dayOfYear',
            isoweekday: 'isoWeekday',
            isoweek: 'isoWeek',
            weekyear: 'weekYear',
            isoweekyear: 'isoWeekYear'
        },


        // format function strings
        formatFunctions = {},


        // default relative time thresholds
        relativeTimeThresholds = {
            s: 45, // seconds to minute
            m: 45, // minutes to hour
            h: 22, // hours to day
            d: 26, // days to month
            M: 11 // months to year
        },


        // tokens to ordinalize and pad
        ordinalizeTokens = 'DDD w W M D d'.split(' '),
            paddedTokens = 'M D H h m s w W'.split(' '),
            formatTokenFunctions = {
            M: function M() {
                return this.month() + 1;
            },
            MMM: function MMM(format) {
                return this.localeData().monthsShort(this, format);
            },
            MMMM: function MMMM(format) {
                return this.localeData().months(this, format);
            },
            D: function D() {
                return this.date();
            },
            DDD: function DDD() {
                return this.dayOfYear();
            },
            d: function d() {
                return this.day();
            },
            dd: function dd(format) {
                return this.localeData().weekdaysMin(this, format);
            },
            ddd: function ddd(format) {
                return this.localeData().weekdaysShort(this, format);
            },
            dddd: function dddd(format) {
                return this.localeData().weekdays(this, format);
            },
            w: function w() {
                return this.week();
            },
            W: function W() {
                return this.isoWeek();
            },
            YY: function YY() {
                return leftZeroFill(this.year() % 100, 2);
            },
            YYYY: function YYYY() {
                return leftZeroFill(this.year(), 4);
            },
            YYYYY: function YYYYY() {
                return leftZeroFill(this.year(), 5);
            },
            YYYYYY: function YYYYYY() {
                var y = this.year(),
                    sign = y >= 0 ? '+' : '-';
                return sign + leftZeroFill(Math.abs(y), 6);
            },
            gg: function gg() {
                return leftZeroFill(this.weekYear() % 100, 2);
            },
            gggg: function gggg() {
                return leftZeroFill(this.weekYear(), 4);
            },
            ggggg: function ggggg() {
                return leftZeroFill(this.weekYear(), 5);
            },
            GG: function GG() {
                return leftZeroFill(this.isoWeekYear() % 100, 2);
            },
            GGGG: function GGGG() {
                return leftZeroFill(this.isoWeekYear(), 4);
            },
            GGGGG: function GGGGG() {
                return leftZeroFill(this.isoWeekYear(), 5);
            },
            e: function e() {
                return this.weekday();
            },
            E: function E() {
                return this.isoWeekday();
            },
            a: function a() {
                return this.localeData().meridiem(this.hours(), this.minutes(), true);
            },
            A: function A() {
                return this.localeData().meridiem(this.hours(), this.minutes(), false);
            },
            H: function H() {
                return this.hours();
            },
            h: function h() {
                return this.hours() % 12 || 12;
            },
            m: function m() {
                return this.minutes();
            },
            s: function s() {
                return this.seconds();
            },
            S: function S() {
                return toInt(this.milliseconds() / 100);
            },
            SS: function SS() {
                return leftZeroFill(toInt(this.milliseconds() / 10), 2);
            },
            SSS: function SSS() {
                return leftZeroFill(this.milliseconds(), 3);
            },
            SSSS: function SSSS() {
                return leftZeroFill(this.milliseconds(), 3);
            },
            Z: function Z() {
                var a = -this.zone(),
                    b = '+';
                if (a < 0) {
                    a = -a;
                    b = '-';
                }
                return b + leftZeroFill(toInt(a / 60), 2) + ':' + leftZeroFill(toInt(a) % 60, 2);
            },
            ZZ: function ZZ() {
                var a = -this.zone(),
                    b = '+';
                if (a < 0) {
                    a = -a;
                    b = '-';
                }
                return b + leftZeroFill(toInt(a / 60), 2) + leftZeroFill(toInt(a) % 60, 2);
            },
            z: function z() {
                return this.zoneAbbr();
            },
            zz: function zz() {
                return this.zoneName();
            },
            X: function X() {
                return this.unix();
            },
            Q: function Q() {
                return this.quarter();
            }
        },
            deprecations = {},
            lists = ['months', 'monthsShort', 'weekdays', 'weekdaysShort', 'weekdaysMin'];

        // Pick the first defined of two or three arguments. dfl comes from
        // default.
        function dfl(a, b, c) {
            switch (arguments.length) {
                case 2:
                    return a != null ? a : b;
                case 3:
                    return a != null ? a : b != null ? b : c;
                default:
                    throw new Error('Implement me');
            }
        }

        function hasOwnProp(a, b) {
            return hasOwnProperty.call(a, b);
        }

        function defaultParsingFlags() {
            // We need to deep clone this object, and es5 standard is not very
            // helpful.
            return {
                empty: false,
                unusedTokens: [],
                unusedInput: [],
                overflow: -2,
                charsLeftOver: 0,
                nullInput: false,
                invalidMonth: null,
                invalidFormat: false,
                userInvalidated: false,
                iso: false
            };
        }

        function printMsg(msg) {
            if (moment.suppressDeprecationWarnings === false && typeof console !== 'undefined' && console.warn) {
                console.warn('Deprecation warning: ' + msg);
            }
        }

        function deprecate(msg, fn) {
            var firstTime = true;
            return extend(function () {
                if (firstTime) {
                    printMsg(msg);
                    firstTime = false;
                }
                return fn.apply(this, arguments);
            }, fn);
        }

        function deprecateSimple(name, msg) {
            if (!deprecations[name]) {
                printMsg(msg);
                deprecations[name] = true;
            }
        }

        function padToken(func, count) {
            return function (a) {
                return leftZeroFill(func.call(this, a), count);
            };
        }
        function ordinalizeToken(func, period) {
            return function (a) {
                return this.localeData().ordinal(func.call(this, a), period);
            };
        }

        while (ordinalizeTokens.length) {
            i = ordinalizeTokens.pop();
            formatTokenFunctions[i + 'o'] = ordinalizeToken(formatTokenFunctions[i], i);
        }
        while (paddedTokens.length) {
            i = paddedTokens.pop();
            formatTokenFunctions[i + i] = padToken(formatTokenFunctions[i], 2);
        }
        formatTokenFunctions.DDDD = padToken(formatTokenFunctions.DDD, 3);

        /************************************
            Constructors
        ************************************/

        function Locale() {}

        // Moment prototype object
        function Moment(config, skipOverflow) {
            if (skipOverflow !== false) {
                checkOverflow(config);
            }
            copyConfig(this, config);
            this._d = new Date(+config._d);
        }

        // Duration Constructor
        function Duration(duration) {
            var normalizedInput = normalizeObjectUnits(duration),
                years = normalizedInput.year || 0,
                quarters = normalizedInput.quarter || 0,
                months = normalizedInput.month || 0,
                weeks = normalizedInput.week || 0,
                days = normalizedInput.day || 0,
                hours = normalizedInput.hour || 0,
                minutes = normalizedInput.minute || 0,
                seconds = normalizedInput.second || 0,
                milliseconds = normalizedInput.millisecond || 0;

            // representation for dateAddRemove
            this._milliseconds = +milliseconds + seconds * 1e3 + // 1000
            minutes * 6e4 + // 1000 * 60
            hours * 36e5; // 1000 * 60 * 60
            // Because of dateAddRemove treats 24 hours as different from a
            // day when working around DST, we need to store them separately
            this._days = +days + weeks * 7;
            // It is impossible translate months into days without knowing
            // which months you are are talking about, so we have to store
            // it separately.
            this._months = +months + quarters * 3 + years * 12;

            this._data = {};

            this._locale = moment.localeData();

            this._bubble();
        }

        /************************************
            Helpers
        ************************************/

        function extend(a, b) {
            for (var i in b) {
                if (hasOwnProp(b, i)) {
                    a[i] = b[i];
                }
            }

            if (hasOwnProp(b, 'toString')) {
                a.toString = b.toString;
            }

            if (hasOwnProp(b, 'valueOf')) {
                a.valueOf = b.valueOf;
            }

            return a;
        }

        function copyConfig(to, from) {
            var i, prop, val;

            if (typeof from._isAMomentObject !== 'undefined') {
                to._isAMomentObject = from._isAMomentObject;
            }
            if (typeof from._i !== 'undefined') {
                to._i = from._i;
            }
            if (typeof from._f !== 'undefined') {
                to._f = from._f;
            }
            if (typeof from._l !== 'undefined') {
                to._l = from._l;
            }
            if (typeof from._strict !== 'undefined') {
                to._strict = from._strict;
            }
            if (typeof from._tzm !== 'undefined') {
                to._tzm = from._tzm;
            }
            if (typeof from._isUTC !== 'undefined') {
                to._isUTC = from._isUTC;
            }
            if (typeof from._offset !== 'undefined') {
                to._offset = from._offset;
            }
            if (typeof from._pf !== 'undefined') {
                to._pf = from._pf;
            }
            if (typeof from._locale !== 'undefined') {
                to._locale = from._locale;
            }

            if (momentProperties.length > 0) {
                for (i in momentProperties) {
                    prop = momentProperties[i];
                    val = from[prop];
                    if (typeof val !== 'undefined') {
                        to[prop] = val;
                    }
                }
            }

            return to;
        }

        function absRound(number) {
            if (number < 0) {
                return Math.ceil(number);
            } else {
                return Math.floor(number);
            }
        }

        // left zero fill a number
        // see http://jsperf.com/left-zero-filling for performance comparison
        function leftZeroFill(number, targetLength, forceSign) {
            var output = '' + Math.abs(number),
                sign = number >= 0;

            while (output.length < targetLength) {
                output = '0' + output;
            }
            return (sign ? forceSign ? '+' : '' : '-') + output;
        }

        function positiveMomentsDifference(base, other) {
            var res = { milliseconds: 0, months: 0 };

            res.months = other.month() - base.month() + (other.year() - base.year()) * 12;
            if (base.clone().add(res.months, 'M').isAfter(other)) {
                --res.months;
            }

            res.milliseconds = +other - +base.clone().add(res.months, 'M');

            return res;
        }

        function momentsDifference(base, other) {
            var res;
            other = makeAs(other, base);
            if (base.isBefore(other)) {
                res = positiveMomentsDifference(base, other);
            } else {
                res = positiveMomentsDifference(other, base);
                res.milliseconds = -res.milliseconds;
                res.months = -res.months;
            }

            return res;
        }

        // TODO: remove 'name' arg after deprecation is removed
        function createAdder(direction, name) {
            return function (val, period) {
                var dur, tmp;
                //invert the arguments, but complain about it
                if (period !== null && !isNaN(+period)) {
                    deprecateSimple(name, 'moment().' + name + '(period, number) is deprecated. Please use moment().' + name + '(number, period).');
                    tmp = val;val = period;period = tmp;
                }

                val = typeof val === 'string' ? +val : val;
                dur = moment.duration(val, period);
                addOrSubtractDurationFromMoment(this, dur, direction);
                return this;
            };
        }

        function addOrSubtractDurationFromMoment(mom, duration, isAdding, updateOffset) {
            var milliseconds = duration._milliseconds,
                days = duration._days,
                months = duration._months;
            updateOffset = updateOffset == null ? true : updateOffset;

            if (milliseconds) {
                mom._d.setTime(+mom._d + milliseconds * isAdding);
            }
            if (days) {
                rawSetter(mom, 'Date', rawGetter(mom, 'Date') + days * isAdding);
            }
            if (months) {
                rawMonthSetter(mom, rawGetter(mom, 'Month') + months * isAdding);
            }
            if (updateOffset) {
                moment.updateOffset(mom, days || months);
            }
        }

        // check if is an array
        function isArray(input) {
            return Object.prototype.toString.call(input) === '[object Array]';
        }

        function isDate(input) {
            return Object.prototype.toString.call(input) === '[object Date]' || input instanceof Date;
        }

        // compare two arrays, return the number of differences
        function compareArrays(array1, array2, dontConvert) {
            var len = Math.min(array1.length, array2.length),
                lengthDiff = Math.abs(array1.length - array2.length),
                diffs = 0,
                i;
            for (i = 0; i < len; i++) {
                if (dontConvert && array1[i] !== array2[i] || !dontConvert && toInt(array1[i]) !== toInt(array2[i])) {
                    diffs++;
                }
            }
            return diffs + lengthDiff;
        }

        function normalizeUnits(units) {
            if (units) {
                var lowered = units.toLowerCase().replace(/(.)s$/, '$1');
                units = unitAliases[units] || camelFunctions[lowered] || lowered;
            }
            return units;
        }

        function normalizeObjectUnits(inputObject) {
            var normalizedInput = {},
                normalizedProp,
                prop;

            for (prop in inputObject) {
                if (hasOwnProp(inputObject, prop)) {
                    normalizedProp = normalizeUnits(prop);
                    if (normalizedProp) {
                        normalizedInput[normalizedProp] = inputObject[prop];
                    }
                }
            }

            return normalizedInput;
        }

        function makeList(field) {
            var count, setter;

            if (field.indexOf('week') === 0) {
                count = 7;
                setter = 'day';
            } else if (field.indexOf('month') === 0) {
                count = 12;
                setter = 'month';
            } else {
                return;
            }

            moment[field] = function (format, index) {
                var i,
                    getter,
                    method = moment._locale[field],
                    results = [];

                if (typeof format === 'number') {
                    index = format;
                    format = undefined;
                }

                getter = function getter(i) {
                    var m = moment().utc().set(setter, i);
                    return method.call(moment._locale, m, format || '');
                };

                if (index != null) {
                    return getter(index);
                } else {
                    for (i = 0; i < count; i++) {
                        results.push(getter(i));
                    }
                    return results;
                }
            };
        }

        function toInt(argumentForCoercion) {
            var coercedNumber = +argumentForCoercion,
                value = 0;

            if (coercedNumber !== 0 && isFinite(coercedNumber)) {
                if (coercedNumber >= 0) {
                    value = Math.floor(coercedNumber);
                } else {
                    value = Math.ceil(coercedNumber);
                }
            }

            return value;
        }

        function _daysInMonth(year, month) {
            return new Date(Date.UTC(year, month + 1, 0)).getUTCDate();
        }

        function _weeksInYear(year, dow, doy) {
            return weekOfYear(moment([year, 11, 31 + dow - doy]), dow, doy).week;
        }

        function daysInYear(year) {
            return _isLeapYear(year) ? 366 : 365;
        }

        function _isLeapYear(year) {
            return year % 4 === 0 && year % 100 !== 0 || year % 400 === 0;
        }

        function checkOverflow(m) {
            var overflow;
            if (m._a && m._pf.overflow === -2) {
                overflow = m._a[MONTH] < 0 || m._a[MONTH] > 11 ? MONTH : m._a[DATE] < 1 || m._a[DATE] > _daysInMonth(m._a[YEAR], m._a[MONTH]) ? DATE : m._a[HOUR] < 0 || m._a[HOUR] > 23 ? HOUR : m._a[MINUTE] < 0 || m._a[MINUTE] > 59 ? MINUTE : m._a[SECOND] < 0 || m._a[SECOND] > 59 ? SECOND : m._a[MILLISECOND] < 0 || m._a[MILLISECOND] > 999 ? MILLISECOND : -1;

                if (m._pf._overflowDayOfYear && (overflow < YEAR || overflow > DATE)) {
                    overflow = DATE;
                }

                m._pf.overflow = overflow;
            }
        }

        function _isValid(m) {
            if (m._isValid == null) {
                m._isValid = !isNaN(m._d.getTime()) && m._pf.overflow < 0 && !m._pf.empty && !m._pf.invalidMonth && !m._pf.nullInput && !m._pf.invalidFormat && !m._pf.userInvalidated;

                if (m._strict) {
                    m._isValid = m._isValid && m._pf.charsLeftOver === 0 && m._pf.unusedTokens.length === 0;
                }
            }
            return m._isValid;
        }

        function normalizeLocale(key) {
            return key ? key.toLowerCase().replace('_', '-') : key;
        }

        // pick the locale from the array
        // try ['en-au', 'en-gb'] as 'en-au', 'en-gb', 'en', as in move through the list trying each
        // substring from most specific to least, but move to the next array item if it's a more specific variant than the current root
        function chooseLocale(names) {
            var i = 0,
                j,
                next,
                locale,
                split;

            while (i < names.length) {
                split = normalizeLocale(names[i]).split('-');
                j = split.length;
                next = normalizeLocale(names[i + 1]);
                next = next ? next.split('-') : null;
                while (j > 0) {
                    locale = loadLocale(split.slice(0, j).join('-'));
                    if (locale) {
                        return locale;
                    }
                    if (next && next.length >= j && compareArrays(split, next, true) >= j - 1) {
                        //the next array item is better than a shallower substring of this one
                        break;
                    }
                    j--;
                }
                i++;
            }
            return null;
        }

        function loadLocale(name) {
            var oldLocale = null;
            if (!locales[name] && hasModule) {
                try {
                    oldLocale = moment.locale();
                    !(function webpackMissingModule() { var e = new Error("Cannot find module \"./locale\""); e.code = 'MODULE_NOT_FOUND'; throw e; }());
                    // because defineLocale currently also sets the global locale, we want to undo that for lazy loaded locales
                    moment.locale(oldLocale);
                } catch (e) {}
            }
            return locales[name];
        }

        // Return a moment from input, that is local/utc/zone equivalent to model.
        function makeAs(input, model) {
            return model._isUTC ? moment(input).zone(model._offset || 0) : moment(input).local();
        }

        /************************************
            Locale
        ************************************/

        extend(Locale.prototype, {

            set: function set(config) {
                var prop, i;
                for (i in config) {
                    prop = config[i];
                    if (typeof prop === 'function') {
                        this[i] = prop;
                    } else {
                        this['_' + i] = prop;
                    }
                }
            },

            _months: 'January_February_March_April_May_June_July_August_September_October_November_December'.split('_'),
            months: function months(m) {
                return this._months[m.month()];
            },

            _monthsShort: 'Jan_Feb_Mar_Apr_May_Jun_Jul_Aug_Sep_Oct_Nov_Dec'.split('_'),
            monthsShort: function monthsShort(m) {
                return this._monthsShort[m.month()];
            },

            monthsParse: function monthsParse(monthName) {
                var i, mom, regex;

                if (!this._monthsParse) {
                    this._monthsParse = [];
                }

                for (i = 0; i < 12; i++) {
                    // make the regex if we don't have it already
                    if (!this._monthsParse[i]) {
                        mom = moment.utc([2000, i]);
                        regex = '^' + this.months(mom, '') + '|^' + this.monthsShort(mom, '');
                        this._monthsParse[i] = new RegExp(regex.replace('.', ''), 'i');
                    }
                    // test the regex
                    if (this._monthsParse[i].test(monthName)) {
                        return i;
                    }
                }
            },

            _weekdays: 'Sunday_Monday_Tuesday_Wednesday_Thursday_Friday_Saturday'.split('_'),
            weekdays: function weekdays(m) {
                return this._weekdays[m.day()];
            },

            _weekdaysShort: 'Sun_Mon_Tue_Wed_Thu_Fri_Sat'.split('_'),
            weekdaysShort: function weekdaysShort(m) {
                return this._weekdaysShort[m.day()];
            },

            _weekdaysMin: 'Su_Mo_Tu_We_Th_Fr_Sa'.split('_'),
            weekdaysMin: function weekdaysMin(m) {
                return this._weekdaysMin[m.day()];
            },

            weekdaysParse: function weekdaysParse(weekdayName) {
                var i, mom, regex;

                if (!this._weekdaysParse) {
                    this._weekdaysParse = [];
                }

                for (i = 0; i < 7; i++) {
                    // make the regex if we don't have it already
                    if (!this._weekdaysParse[i]) {
                        mom = moment([2000, 1]).day(i);
                        regex = '^' + this.weekdays(mom, '') + '|^' + this.weekdaysShort(mom, '') + '|^' + this.weekdaysMin(mom, '');
                        this._weekdaysParse[i] = new RegExp(regex.replace('.', ''), 'i');
                    }
                    // test the regex
                    if (this._weekdaysParse[i].test(weekdayName)) {
                        return i;
                    }
                }
            },

            _longDateFormat: {
                LT: 'h:mm A',
                L: 'MM/DD/YYYY',
                LL: 'MMMM D, YYYY',
                LLL: 'MMMM D, YYYY LT',
                LLLL: 'dddd, MMMM D, YYYY LT'
            },
            longDateFormat: function longDateFormat(key) {
                var output = this._longDateFormat[key];
                if (!output && this._longDateFormat[key.toUpperCase()]) {
                    output = this._longDateFormat[key.toUpperCase()].replace(/MMMM|MM|DD|dddd/g, function (val) {
                        return val.slice(1);
                    });
                    this._longDateFormat[key] = output;
                }
                return output;
            },

            isPM: function isPM(input) {
                // IE8 Quirks Mode & IE7 Standards Mode do not allow accessing strings like arrays
                // Using charAt should be more compatible.
                return (input + '').toLowerCase().charAt(0) === 'p';
            },

            _meridiemParse: /[ap]\.?m?\.?/i,
            meridiem: function meridiem(hours, minutes, isLower) {
                if (hours > 11) {
                    return isLower ? 'pm' : 'PM';
                } else {
                    return isLower ? 'am' : 'AM';
                }
            },

            _calendar: {
                sameDay: '[Today at] LT',
                nextDay: '[Tomorrow at] LT',
                nextWeek: 'dddd [at] LT',
                lastDay: '[Yesterday at] LT',
                lastWeek: '[Last] dddd [at] LT',
                sameElse: 'L'
            },
            calendar: function calendar(key, mom) {
                var output = this._calendar[key];
                return typeof output === 'function' ? output.apply(mom) : output;
            },

            _relativeTime: {
                future: 'in %s',
                past: '%s ago',
                s: 'a few seconds',
                m: 'a minute',
                mm: '%d minutes',
                h: 'an hour',
                hh: '%d hours',
                d: 'a day',
                dd: '%d days',
                M: 'a month',
                MM: '%d months',
                y: 'a year',
                yy: '%d years'
            },

            relativeTime: function relativeTime(number, withoutSuffix, string, isFuture) {
                var output = this._relativeTime[string];
                return typeof output === 'function' ? output(number, withoutSuffix, string, isFuture) : output.replace(/%d/i, number);
            },

            pastFuture: function pastFuture(diff, output) {
                var format = this._relativeTime[diff > 0 ? 'future' : 'past'];
                return typeof format === 'function' ? format(output) : format.replace(/%s/i, output);
            },

            ordinal: function ordinal(number) {
                return this._ordinal.replace('%d', number);
            },
            _ordinal: '%d',

            preparse: function preparse(string) {
                return string;
            },

            postformat: function postformat(string) {
                return string;
            },

            week: function week(mom) {
                return weekOfYear(mom, this._week.dow, this._week.doy).week;
            },

            _week: {
                dow: 0, // Sunday is the first day of the week.
                doy: 6 // The week that contains Jan 1st is the first week of the year.
            },

            _invalidDate: 'Invalid date',
            invalidDate: function invalidDate() {
                return this._invalidDate;
            }
        });

        /************************************
            Formatting
        ************************************/

        function removeFormattingTokens(input) {
            if (input.match(/\[[\s\S]/)) {
                return input.replace(/^\[|\]$/g, '');
            }
            return input.replace(/\\/g, '');
        }

        function makeFormatFunction(format) {
            var array = format.match(formattingTokens),
                i,
                length;

            for (i = 0, length = array.length; i < length; i++) {
                if (formatTokenFunctions[array[i]]) {
                    array[i] = formatTokenFunctions[array[i]];
                } else {
                    array[i] = removeFormattingTokens(array[i]);
                }
            }

            return function (mom) {
                var output = '';
                for (i = 0; i < length; i++) {
                    output += array[i] instanceof Function ? array[i].call(mom, format) : array[i];
                }
                return output;
            };
        }

        // format date using native date object
        function formatMoment(m, format) {
            if (!m.isValid()) {
                return m.localeData().invalidDate();
            }

            format = expandFormat(format, m.localeData());

            if (!formatFunctions[format]) {
                formatFunctions[format] = makeFormatFunction(format);
            }

            return formatFunctions[format](m);
        }

        function expandFormat(format, locale) {
            var i = 5;

            function replaceLongDateFormatTokens(input) {
                return locale.longDateFormat(input) || input;
            }

            localFormattingTokens.lastIndex = 0;
            while (i >= 0 && localFormattingTokens.test(format)) {
                format = format.replace(localFormattingTokens, replaceLongDateFormatTokens);
                localFormattingTokens.lastIndex = 0;
                i -= 1;
            }

            return format;
        }

        /************************************
            Parsing
        ************************************/

        // get the regex to find the next token
        function getParseRegexForToken(token, config) {
            var a,
                strict = config._strict;
            switch (token) {
                case 'Q':
                    return parseTokenOneDigit;
                case 'DDDD':
                    return parseTokenThreeDigits;
                case 'YYYY':
                case 'GGGG':
                case 'gggg':
                    return strict ? parseTokenFourDigits : parseTokenOneToFourDigits;
                case 'Y':
                case 'G':
                case 'g':
                    return parseTokenSignedNumber;
                case 'YYYYYY':
                case 'YYYYY':
                case 'GGGGG':
                case 'ggggg':
                    return strict ? parseTokenSixDigits : parseTokenOneToSixDigits;
                case 'S':
                    if (strict) {
                        return parseTokenOneDigit;
                    }
                /* falls through */
                case 'SS':
                    if (strict) {
                        return parseTokenTwoDigits;
                    }
                /* falls through */
                case 'SSS':
                    if (strict) {
                        return parseTokenThreeDigits;
                    }
                /* falls through */
                case 'DDD':
                    return parseTokenOneToThreeDigits;
                case 'MMM':
                case 'MMMM':
                case 'dd':
                case 'ddd':
                case 'dddd':
                    return parseTokenWord;
                case 'a':
                case 'A':
                    return config._locale._meridiemParse;
                case 'X':
                    return parseTokenTimestampMs;
                case 'Z':
                case 'ZZ':
                    return parseTokenTimezone;
                case 'T':
                    return parseTokenT;
                case 'SSSS':
                    return parseTokenDigits;
                case 'MM':
                case 'DD':
                case 'YY':
                case 'GG':
                case 'gg':
                case 'HH':
                case 'hh':
                case 'mm':
                case 'ss':
                case 'ww':
                case 'WW':
                    return strict ? parseTokenTwoDigits : parseTokenOneOrTwoDigits;
                case 'M':
                case 'D':
                case 'd':
                case 'H':
                case 'h':
                case 'm':
                case 's':
                case 'w':
                case 'W':
                case 'e':
                case 'E':
                    return parseTokenOneOrTwoDigits;
                case 'Do':
                    return parseTokenOrdinal;
                default:
                    a = new RegExp(regexpEscape(unescapeFormat(token.replace('\\', '')), 'i'));
                    return a;
            }
        }

        function timezoneMinutesFromString(string) {
            string = string || '';
            var possibleTzMatches = string.match(parseTokenTimezone) || [],
                tzChunk = possibleTzMatches[possibleTzMatches.length - 1] || [],
                parts = (tzChunk + '').match(parseTimezoneChunker) || ['-', 0, 0],
                minutes = +(parts[1] * 60) + toInt(parts[2]);

            return parts[0] === '+' ? -minutes : minutes;
        }

        // function to convert string input to date
        function addTimeToArrayFromToken(token, input, config) {
            var a,
                datePartArray = config._a;

            switch (token) {
                // QUARTER
                case 'Q':
                    if (input != null) {
                        datePartArray[MONTH] = (toInt(input) - 1) * 3;
                    }
                    break;
                // MONTH
                case 'M': // fall through to MM
                case 'MM':
                    if (input != null) {
                        datePartArray[MONTH] = toInt(input) - 1;
                    }
                    break;
                case 'MMM': // fall through to MMMM
                case 'MMMM':
                    a = config._locale.monthsParse(input);
                    // if we didn't find a month name, mark the date as invalid.
                    if (a != null) {
                        datePartArray[MONTH] = a;
                    } else {
                        config._pf.invalidMonth = input;
                    }
                    break;
                // DAY OF MONTH
                case 'D': // fall through to DD
                case 'DD':
                    if (input != null) {
                        datePartArray[DATE] = toInt(input);
                    }
                    break;
                case 'Do':
                    if (input != null) {
                        datePartArray[DATE] = toInt(parseInt(input, 10));
                    }
                    break;
                // DAY OF YEAR
                case 'DDD': // fall through to DDDD
                case 'DDDD':
                    if (input != null) {
                        config._dayOfYear = toInt(input);
                    }

                    break;
                // YEAR
                case 'YY':
                    datePartArray[YEAR] = moment.parseTwoDigitYear(input);
                    break;
                case 'YYYY':
                case 'YYYYY':
                case 'YYYYYY':
                    datePartArray[YEAR] = toInt(input);
                    break;
                // AM / PM
                case 'a': // fall through to A
                case 'A':
                    config._isPm = config._locale.isPM(input);
                    break;
                // 24 HOUR
                case 'H': // fall through to hh
                case 'HH': // fall through to hh
                case 'h': // fall through to hh
                case 'hh':
                    datePartArray[HOUR] = toInt(input);
                    break;
                // MINUTE
                case 'm': // fall through to mm
                case 'mm':
                    datePartArray[MINUTE] = toInt(input);
                    break;
                // SECOND
                case 's': // fall through to ss
                case 'ss':
                    datePartArray[SECOND] = toInt(input);
                    break;
                // MILLISECOND
                case 'S':
                case 'SS':
                case 'SSS':
                case 'SSSS':
                    datePartArray[MILLISECOND] = toInt(('0.' + input) * 1000);
                    break;
                // UNIX TIMESTAMP WITH MS
                case 'X':
                    config._d = new Date(parseFloat(input) * 1000);
                    break;
                // TIMEZONE
                case 'Z': // fall through to ZZ
                case 'ZZ':
                    config._useUTC = true;
                    config._tzm = timezoneMinutesFromString(input);
                    break;
                // WEEKDAY - human
                case 'dd':
                case 'ddd':
                case 'dddd':
                    a = config._locale.weekdaysParse(input);
                    // if we didn't get a weekday name, mark the date as invalid
                    if (a != null) {
                        config._w = config._w || {};
                        config._w['d'] = a;
                    } else {
                        config._pf.invalidWeekday = input;
                    }
                    break;
                // WEEK, WEEK DAY - numeric
                case 'w':
                case 'ww':
                case 'W':
                case 'WW':
                case 'd':
                case 'e':
                case 'E':
                    token = token.substr(0, 1);
                /* falls through */
                case 'gggg':
                case 'GGGG':
                case 'GGGGG':
                    token = token.substr(0, 2);
                    if (input) {
                        config._w = config._w || {};
                        config._w[token] = toInt(input);
                    }
                    break;
                case 'gg':
                case 'GG':
                    config._w = config._w || {};
                    config._w[token] = moment.parseTwoDigitYear(input);
            }
        }

        function dayOfYearFromWeekInfo(config) {
            var w, weekYear, week, weekday, dow, doy, temp;

            w = config._w;
            if (w.GG != null || w.W != null || w.E != null) {
                dow = 1;
                doy = 4;

                // TODO: We need to take the current isoWeekYear, but that depends on
                // how we interpret now (local, utc, fixed offset). So create
                // a now version of current config (take local/utc/offset flags, and
                // create now).
                weekYear = dfl(w.GG, config._a[YEAR], weekOfYear(moment(), 1, 4).year);
                week = dfl(w.W, 1);
                weekday = dfl(w.E, 1);
            } else {
                dow = config._locale._week.dow;
                doy = config._locale._week.doy;

                weekYear = dfl(w.gg, config._a[YEAR], weekOfYear(moment(), dow, doy).year);
                week = dfl(w.w, 1);

                if (w.d != null) {
                    // weekday -- low day numbers are considered next week
                    weekday = w.d;
                    if (weekday < dow) {
                        ++week;
                    }
                } else if (w.e != null) {
                    // local weekday -- counting starts from begining of week
                    weekday = w.e + dow;
                } else {
                    // default to begining of week
                    weekday = dow;
                }
            }
            temp = dayOfYearFromWeeks(weekYear, week, weekday, doy, dow);

            config._a[YEAR] = temp.year;
            config._dayOfYear = temp.dayOfYear;
        }

        // convert an array to a date.
        // the array should mirror the parameters below
        // note: all values past the year are optional and will default to the lowest possible value.
        // [year, month, day , hour, minute, second, millisecond]
        function dateFromConfig(config) {
            var i,
                date,
                input = [],
                currentDate,
                yearToUse;

            if (config._d) {
                return;
            }

            currentDate = currentDateArray(config);

            //compute day of the year from weeks and weekdays
            if (config._w && config._a[DATE] == null && config._a[MONTH] == null) {
                dayOfYearFromWeekInfo(config);
            }

            //if the day of the year is set, figure out what it is
            if (config._dayOfYear) {
                yearToUse = dfl(config._a[YEAR], currentDate[YEAR]);

                if (config._dayOfYear > daysInYear(yearToUse)) {
                    config._pf._overflowDayOfYear = true;
                }

                date = makeUTCDate(yearToUse, 0, config._dayOfYear);
                config._a[MONTH] = date.getUTCMonth();
                config._a[DATE] = date.getUTCDate();
            }

            // Default to current date.
            // * if no year, month, day of month are given, default to today
            // * if day of month is given, default month and year
            // * if month is given, default only year
            // * if year is given, don't default anything
            for (i = 0; i < 3 && config._a[i] == null; ++i) {
                config._a[i] = input[i] = currentDate[i];
            }

            // Zero out whatever was not defaulted, including time
            for (; i < 7; i++) {
                config._a[i] = input[i] = config._a[i] == null ? i === 2 ? 1 : 0 : config._a[i];
            }

            config._d = (config._useUTC ? makeUTCDate : makeDate).apply(null, input);
            // Apply timezone offset from input. The actual zone can be changed
            // with parseZone.
            if (config._tzm != null) {
                config._d.setUTCMinutes(config._d.getUTCMinutes() + config._tzm);
            }
        }

        function dateFromObject(config) {
            var normalizedInput;

            if (config._d) {
                return;
            }

            normalizedInput = normalizeObjectUnits(config._i);
            config._a = [normalizedInput.year, normalizedInput.month, normalizedInput.day, normalizedInput.hour, normalizedInput.minute, normalizedInput.second, normalizedInput.millisecond];

            dateFromConfig(config);
        }

        function currentDateArray(config) {
            var now = new Date();
            if (config._useUTC) {
                return [now.getUTCFullYear(), now.getUTCMonth(), now.getUTCDate()];
            } else {
                return [now.getFullYear(), now.getMonth(), now.getDate()];
            }
        }

        // date from string and format string
        function makeDateFromStringAndFormat(config) {
            if (config._f === moment.ISO_8601) {
                parseISO(config);
                return;
            }

            config._a = [];
            config._pf.empty = true;

            // This array is used to make a Date, either with `new Date` or `Date.UTC`
            var string = '' + config._i,
                i,
                parsedInput,
                tokens,
                token,
                skipped,
                stringLength = string.length,
                totalParsedInputLength = 0;

            tokens = expandFormat(config._f, config._locale).match(formattingTokens) || [];

            for (i = 0; i < tokens.length; i++) {
                token = tokens[i];
                parsedInput = (string.match(getParseRegexForToken(token, config)) || [])[0];
                if (parsedInput) {
                    skipped = string.substr(0, string.indexOf(parsedInput));
                    if (skipped.length > 0) {
                        config._pf.unusedInput.push(skipped);
                    }
                    string = string.slice(string.indexOf(parsedInput) + parsedInput.length);
                    totalParsedInputLength += parsedInput.length;
                }
                // don't parse if it's not a known token
                if (formatTokenFunctions[token]) {
                    if (parsedInput) {
                        config._pf.empty = false;
                    } else {
                        config._pf.unusedTokens.push(token);
                    }
                    addTimeToArrayFromToken(token, parsedInput, config);
                } else if (config._strict && !parsedInput) {
                    config._pf.unusedTokens.push(token);
                }
            }

            // add remaining unparsed input length to the string
            config._pf.charsLeftOver = stringLength - totalParsedInputLength;
            if (string.length > 0) {
                config._pf.unusedInput.push(string);
            }

            // handle am pm
            if (config._isPm && config._a[HOUR] < 12) {
                config._a[HOUR] += 12;
            }
            // if is 12 am, change hours to 0
            if (config._isPm === false && config._a[HOUR] === 12) {
                config._a[HOUR] = 0;
            }

            dateFromConfig(config);
            checkOverflow(config);
        }

        function unescapeFormat(s) {
            return s.replace(/\\(\[)|\\(\])|\[([^\]\[]*)\]|\\(.)/g, function (matched, p1, p2, p3, p4) {
                return p1 || p2 || p3 || p4;
            });
        }

        // Code from http://stackoverflow.com/questions/3561493/is-there-a-regexp-escape-function-in-javascript
        function regexpEscape(s) {
            return s.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&');
        }

        // date from string and array of format strings
        function makeDateFromStringAndArray(config) {
            var tempConfig, bestMoment, scoreToBeat, i, currentScore;

            if (config._f.length === 0) {
                config._pf.invalidFormat = true;
                config._d = new Date(NaN);
                return;
            }

            for (i = 0; i < config._f.length; i++) {
                currentScore = 0;
                tempConfig = copyConfig({}, config);
                if (config._useUTC != null) {
                    tempConfig._useUTC = config._useUTC;
                }
                tempConfig._pf = defaultParsingFlags();
                tempConfig._f = config._f[i];
                makeDateFromStringAndFormat(tempConfig);

                if (!_isValid(tempConfig)) {
                    continue;
                }

                // if there is any input that was not parsed add a penalty for that format
                currentScore += tempConfig._pf.charsLeftOver;

                //or tokens
                currentScore += tempConfig._pf.unusedTokens.length * 10;

                tempConfig._pf.score = currentScore;

                if (scoreToBeat == null || currentScore < scoreToBeat) {
                    scoreToBeat = currentScore;
                    bestMoment = tempConfig;
                }
            }

            extend(config, bestMoment || tempConfig);
        }

        // date from iso format
        function parseISO(config) {
            var i,
                l,
                string = config._i,
                match = isoRegex.exec(string);

            if (match) {
                config._pf.iso = true;
                for (i = 0, l = isoDates.length; i < l; i++) {
                    if (isoDates[i][1].exec(string)) {
                        // match[5] should be 'T' or undefined
                        config._f = isoDates[i][0] + (match[6] || ' ');
                        break;
                    }
                }
                for (i = 0, l = isoTimes.length; i < l; i++) {
                    if (isoTimes[i][1].exec(string)) {
                        config._f += isoTimes[i][0];
                        break;
                    }
                }
                if (string.match(parseTokenTimezone)) {
                    config._f += 'Z';
                }
                makeDateFromStringAndFormat(config);
            } else {
                config._isValid = false;
            }
        }

        // date from iso format or fallback
        function makeDateFromString(config) {
            parseISO(config);
            if (config._isValid === false) {
                delete config._isValid;
                moment.createFromInputFallback(config);
            }
        }

        function map(arr, fn) {
            var res = [],
                i;
            for (i = 0; i < arr.length; ++i) {
                res.push(fn(arr[i], i));
            }
            return res;
        }

        function makeDateFromInput(config) {
            var input = config._i,
                matched;
            if (input === undefined) {
                config._d = new Date();
            } else if (isDate(input)) {
                config._d = new Date(+input);
            } else if ((matched = aspNetJsonRegex.exec(input)) !== null) {
                config._d = new Date(+matched[1]);
            } else if (typeof input === 'string') {
                makeDateFromString(config);
            } else if (isArray(input)) {
                config._a = map(input.slice(0), function (obj) {
                    return parseInt(obj, 10);
                });
                dateFromConfig(config);
            } else if ((typeof input === 'undefined' ? 'undefined' : (0, _typeof3.default)(input)) === 'object') {
                dateFromObject(config);
            } else if (typeof input === 'number') {
                // from milliseconds
                config._d = new Date(input);
            } else {
                moment.createFromInputFallback(config);
            }
        }

        function makeDate(y, m, d, h, M, s, ms) {
            //can't just apply() to create a date:
            //http://stackoverflow.com/questions/181348/instantiating-a-javascript-object-by-calling-prototype-constructor-apply
            var date = new Date(y, m, d, h, M, s, ms);

            //the date constructor doesn't accept years < 1970
            if (y < 1970) {
                date.setFullYear(y);
            }
            return date;
        }

        function makeUTCDate(y) {
            var date = new Date(Date.UTC.apply(null, arguments));
            if (y < 1970) {
                date.setUTCFullYear(y);
            }
            return date;
        }

        function parseWeekday(input, locale) {
            if (typeof input === 'string') {
                if (!isNaN(input)) {
                    input = parseInt(input, 10);
                } else {
                    input = locale.weekdaysParse(input);
                    if (typeof input !== 'number') {
                        return null;
                    }
                }
            }
            return input;
        }

        /************************************
            Relative Time
        ************************************/

        // helper function for moment.fn.from, moment.fn.fromNow, and moment.duration.fn.humanize
        function substituteTimeAgo(string, number, withoutSuffix, isFuture, locale) {
            return locale.relativeTime(number || 1, !!withoutSuffix, string, isFuture);
        }

        function relativeTime(posNegDuration, withoutSuffix, locale) {
            var duration = moment.duration(posNegDuration).abs(),
                seconds = round(duration.as('s')),
                minutes = round(duration.as('m')),
                hours = round(duration.as('h')),
                days = round(duration.as('d')),
                months = round(duration.as('M')),
                years = round(duration.as('y')),
                args = seconds < relativeTimeThresholds.s && ['s', seconds] || minutes === 1 && ['m'] || minutes < relativeTimeThresholds.m && ['mm', minutes] || hours === 1 && ['h'] || hours < relativeTimeThresholds.h && ['hh', hours] || days === 1 && ['d'] || days < relativeTimeThresholds.d && ['dd', days] || months === 1 && ['M'] || months < relativeTimeThresholds.M && ['MM', months] || years === 1 && ['y'] || ['yy', years];

            args[2] = withoutSuffix;
            args[3] = +posNegDuration > 0;
            args[4] = locale;
            return substituteTimeAgo.apply({}, args);
        }

        /************************************
            Week of Year
        ************************************/

        // firstDayOfWeek       0 = sun, 6 = sat
        //                      the day of the week that starts the week
        //                      (usually sunday or monday)
        // firstDayOfWeekOfYear 0 = sun, 6 = sat
        //                      the first week is the week that contains the first
        //                      of this day of the week
        //                      (eg. ISO weeks use thursday (4))
        function weekOfYear(mom, firstDayOfWeek, firstDayOfWeekOfYear) {
            var end = firstDayOfWeekOfYear - firstDayOfWeek,
                daysToDayOfWeek = firstDayOfWeekOfYear - mom.day(),
                adjustedMoment;

            if (daysToDayOfWeek > end) {
                daysToDayOfWeek -= 7;
            }

            if (daysToDayOfWeek < end - 7) {
                daysToDayOfWeek += 7;
            }

            adjustedMoment = moment(mom).add(daysToDayOfWeek, 'd');
            return {
                week: Math.ceil(adjustedMoment.dayOfYear() / 7),
                year: adjustedMoment.year()
            };
        }

        //http://en.wikipedia.org/wiki/ISO_week_date#Calculating_a_date_given_the_year.2C_week_number_and_weekday
        function dayOfYearFromWeeks(year, week, weekday, firstDayOfWeekOfYear, firstDayOfWeek) {
            var d = makeUTCDate(year, 0, 1).getUTCDay(),
                daysToAdd,
                dayOfYear;

            d = d === 0 ? 7 : d;
            weekday = weekday != null ? weekday : firstDayOfWeek;
            daysToAdd = firstDayOfWeek - d + (d > firstDayOfWeekOfYear ? 7 : 0) - (d < firstDayOfWeek ? 7 : 0);
            dayOfYear = 7 * (week - 1) + (weekday - firstDayOfWeek) + daysToAdd + 1;

            return {
                year: dayOfYear > 0 ? year : year - 1,
                dayOfYear: dayOfYear > 0 ? dayOfYear : daysInYear(year - 1) + dayOfYear
            };
        }

        /************************************
            Top Level Functions
        ************************************/

        function makeMoment(config) {
            var input = config._i,
                format = config._f;

            config._locale = config._locale || moment.localeData(config._l);

            if (input === null || format === undefined && input === '') {
                return moment.invalid({ nullInput: true });
            }

            if (typeof input === 'string') {
                config._i = input = config._locale.preparse(input);
            }

            if (moment.isMoment(input)) {
                return new Moment(input, true);
            } else if (format) {
                if (isArray(format)) {
                    makeDateFromStringAndArray(config);
                } else {
                    makeDateFromStringAndFormat(config);
                }
            } else {
                makeDateFromInput(config);
            }

            return new Moment(config);
        }

        moment = function moment(input, format, locale, strict) {
            var c;

            if (typeof locale === 'boolean') {
                strict = locale;
                locale = undefined;
            }
            // object construction must be done this way.
            // https://github.com/moment/moment/issues/1423
            c = {};
            c._isAMomentObject = true;
            c._i = input;
            c._f = format;
            c._l = locale;
            c._strict = strict;
            c._isUTC = false;
            c._pf = defaultParsingFlags();

            return makeMoment(c);
        };

        moment.suppressDeprecationWarnings = false;

        moment.createFromInputFallback = deprecate('moment construction falls back to js Date. This is ' + 'discouraged and will be removed in upcoming major ' + 'release. Please refer to ' + 'https://github.com/moment/moment/issues/1407 for more info.', function (config) {
            config._d = new Date(config._i);
        });

        // Pick a moment m from moments so that m[fn](other) is true for all
        // other. This relies on the function fn to be transitive.
        //
        // moments should either be an array of moment objects or an array, whose
        // first element is an array of moment objects.
        function pickBy(fn, moments) {
            var res, i;
            if (moments.length === 1 && isArray(moments[0])) {
                moments = moments[0];
            }
            if (!moments.length) {
                return moment();
            }
            res = moments[0];
            for (i = 1; i < moments.length; ++i) {
                if (moments[i][fn](res)) {
                    res = moments[i];
                }
            }
            return res;
        }

        moment.min = function () {
            var args = [].slice.call(arguments, 0);

            return pickBy('isBefore', args);
        };

        moment.max = function () {
            var args = [].slice.call(arguments, 0);

            return pickBy('isAfter', args);
        };

        // creating with utc
        moment.utc = function (input, format, locale, strict) {
            var c;

            if (typeof locale === 'boolean') {
                strict = locale;
                locale = undefined;
            }
            // object construction must be done this way.
            // https://github.com/moment/moment/issues/1423
            c = {};
            c._isAMomentObject = true;
            c._useUTC = true;
            c._isUTC = true;
            c._l = locale;
            c._i = input;
            c._f = format;
            c._strict = strict;
            c._pf = defaultParsingFlags();

            return makeMoment(c).utc();
        };

        // creating with unix timestamp (in seconds)
        moment.unix = function (input) {
            return moment(input * 1000);
        };

        // duration
        moment.duration = function (input, key) {
            var duration = input,

            // matching against regexp is expensive, do it on demand
            match = null,
                sign,
                ret,
                parseIso,
                diffRes;

            if (moment.isDuration(input)) {
                duration = {
                    ms: input._milliseconds,
                    d: input._days,
                    M: input._months
                };
            } else if (typeof input === 'number') {
                duration = {};
                if (key) {
                    duration[key] = input;
                } else {
                    duration.milliseconds = input;
                }
            } else if (!!(match = aspNetTimeSpanJsonRegex.exec(input))) {
                sign = match[1] === '-' ? -1 : 1;
                duration = {
                    y: 0,
                    d: toInt(match[DATE]) * sign,
                    h: toInt(match[HOUR]) * sign,
                    m: toInt(match[MINUTE]) * sign,
                    s: toInt(match[SECOND]) * sign,
                    ms: toInt(match[MILLISECOND]) * sign
                };
            } else if (!!(match = isoDurationRegex.exec(input))) {
                sign = match[1] === '-' ? -1 : 1;
                parseIso = function parseIso(inp) {
                    // We'd normally use ~~inp for this, but unfortunately it also
                    // converts floats to ints.
                    // inp may be undefined, so careful calling replace on it.
                    var res = inp && parseFloat(inp.replace(',', '.'));
                    // apply sign while we're at it
                    return (isNaN(res) ? 0 : res) * sign;
                };
                duration = {
                    y: parseIso(match[2]),
                    M: parseIso(match[3]),
                    d: parseIso(match[4]),
                    h: parseIso(match[5]),
                    m: parseIso(match[6]),
                    s: parseIso(match[7]),
                    w: parseIso(match[8])
                };
            } else if ((typeof duration === 'undefined' ? 'undefined' : (0, _typeof3.default)(duration)) === 'object' && ('from' in duration || 'to' in duration)) {
                diffRes = momentsDifference(moment(duration.from), moment(duration.to));

                duration = {};
                duration.ms = diffRes.milliseconds;
                duration.M = diffRes.months;
            }

            ret = new Duration(duration);

            if (moment.isDuration(input) && hasOwnProp(input, '_locale')) {
                ret._locale = input._locale;
            }

            return ret;
        };

        // version number
        moment.version = VERSION;

        // default format
        moment.defaultFormat = isoFormat;

        // constant that refers to the ISO standard
        moment.ISO_8601 = function () {};

        // Plugins that add properties should also add the key here (null value),
        // so we can properly clone ourselves.
        moment.momentProperties = momentProperties;

        // This function will be called whenever a moment is mutated.
        // It is intended to keep the offset in sync with the timezone.
        moment.updateOffset = function () {};

        // This function allows you to set a threshold for relative time strings
        moment.relativeTimeThreshold = function (threshold, limit) {
            if (relativeTimeThresholds[threshold] === undefined) {
                return false;
            }
            if (limit === undefined) {
                return relativeTimeThresholds[threshold];
            }
            relativeTimeThresholds[threshold] = limit;
            return true;
        };

        moment.lang = deprecate('moment.lang is deprecated. Use moment.locale instead.', function (key, value) {
            return moment.locale(key, value);
        });

        // This function will load locale and then set the global locale.  If
        // no arguments are passed in, it will simply return the current global
        // locale key.
        moment.locale = function (key, values) {
            var data;
            if (key) {
                if (typeof values !== 'undefined') {
                    data = moment.defineLocale(key, values);
                } else {
                    data = moment.localeData(key);
                }

                if (data) {
                    moment.duration._locale = moment._locale = data;
                }
            }

            return moment._locale._abbr;
        };

        moment.defineLocale = function (name, values) {
            if (values !== null) {
                values.abbr = name;
                if (!locales[name]) {
                    locales[name] = new Locale();
                }
                locales[name].set(values);

                // backwards compat for now: also set the locale
                moment.locale(name);

                return locales[name];
            } else {
                // useful for testing
                delete locales[name];
                return null;
            }
        };

        moment.langData = deprecate('moment.langData is deprecated. Use moment.localeData instead.', function (key) {
            return moment.localeData(key);
        });

        // returns locale data
        moment.localeData = function (key) {
            var locale;

            if (key && key._locale && key._locale._abbr) {
                key = key._locale._abbr;
            }

            if (!key) {
                return moment._locale;
            }

            if (!isArray(key)) {
                //short-circuit everything else
                locale = loadLocale(key);
                if (locale) {
                    return locale;
                }
                key = [key];
            }

            return chooseLocale(key);
        };

        // compare moment object
        moment.isMoment = function (obj) {
            return obj instanceof Moment || obj != null && hasOwnProp(obj, '_isAMomentObject');
        };

        // for typechecking Duration objects
        moment.isDuration = function (obj) {
            return obj instanceof Duration;
        };

        for (i = lists.length - 1; i >= 0; --i) {
            makeList(lists[i]);
        }

        moment.normalizeUnits = function (units) {
            return normalizeUnits(units);
        };

        moment.invalid = function (flags) {
            var m = moment.utc(NaN);
            if (flags != null) {
                extend(m._pf, flags);
            } else {
                m._pf.userInvalidated = true;
            }

            return m;
        };

        moment.parseZone = function () {
            return moment.apply(null, arguments).parseZone();
        };

        moment.parseTwoDigitYear = function (input) {
            return toInt(input) + (toInt(input) > 68 ? 1900 : 2000);
        };

        /************************************
            Moment Prototype
        ************************************/

        extend(moment.fn = Moment.prototype, {

            clone: function clone() {
                return moment(this);
            },

            valueOf: function valueOf() {
                return +this._d + (this._offset || 0) * 60000;
            },

            unix: function unix() {
                return Math.floor(+this / 1000);
            },

            toString: function toString() {
                return this.clone().locale('en').format('ddd MMM DD YYYY HH:mm:ss [GMT]ZZ');
            },

            toDate: function toDate() {
                return this._offset ? new Date(+this) : this._d;
            },

            toISOString: function toISOString() {
                var m = moment(this).utc();
                if (0 < m.year() && m.year() <= 9999) {
                    return formatMoment(m, 'YYYY-MM-DD[T]HH:mm:ss.SSS[Z]');
                } else {
                    return formatMoment(m, 'YYYYYY-MM-DD[T]HH:mm:ss.SSS[Z]');
                }
            },

            toArray: function toArray() {
                var m = this;
                return [m.year(), m.month(), m.date(), m.hours(), m.minutes(), m.seconds(), m.milliseconds()];
            },

            isValid: function isValid() {
                return _isValid(this);
            },

            isDSTShifted: function isDSTShifted() {
                if (this._a) {
                    return this.isValid() && compareArrays(this._a, (this._isUTC ? moment.utc(this._a) : moment(this._a)).toArray()) > 0;
                }

                return false;
            },

            parsingFlags: function parsingFlags() {
                return extend({}, this._pf);
            },

            invalidAt: function invalidAt() {
                return this._pf.overflow;
            },

            utc: function utc(keepLocalTime) {
                return this.zone(0, keepLocalTime);
            },

            local: function local(keepLocalTime) {
                if (this._isUTC) {
                    this.zone(0, keepLocalTime);
                    this._isUTC = false;

                    if (keepLocalTime) {
                        this.add(this._dateTzOffset(), 'm');
                    }
                }
                return this;
            },

            format: function format(inputString) {
                var output = formatMoment(this, inputString || moment.defaultFormat);
                return this.localeData().postformat(output);
            },

            add: createAdder(1, 'add'),

            subtract: createAdder(-1, 'subtract'),

            diff: function diff(input, units, asFloat) {
                var that = makeAs(input, this),
                    zoneDiff = (this.zone() - that.zone()) * 6e4,
                    diff,
                    output,
                    daysAdjust;

                units = normalizeUnits(units);

                if (units === 'year' || units === 'month') {
                    // average number of days in the months in the given dates
                    diff = (this.daysInMonth() + that.daysInMonth()) * 432e5; // 24 * 60 * 60 * 1000 / 2
                    // difference in months
                    output = (this.year() - that.year()) * 12 + (this.month() - that.month());
                    // adjust by taking difference in days, average number of days
                    // and dst in the given months.
                    daysAdjust = this - moment(this).startOf('month') - (that - moment(that).startOf('month'));
                    // same as above but with zones, to negate all dst
                    daysAdjust -= (this.zone() - moment(this).startOf('month').zone() - (that.zone() - moment(that).startOf('month').zone())) * 6e4;
                    output += daysAdjust / diff;
                    if (units === 'year') {
                        output = output / 12;
                    }
                } else {
                    diff = this - that;
                    output = units === 'second' ? diff / 1e3 : // 1000
                    units === 'minute' ? diff / 6e4 : // 1000 * 60
                    units === 'hour' ? diff / 36e5 : // 1000 * 60 * 60
                    units === 'day' ? (diff - zoneDiff) / 864e5 : // 1000 * 60 * 60 * 24, negate dst
                    units === 'week' ? (diff - zoneDiff) / 6048e5 : // 1000 * 60 * 60 * 24 * 7, negate dst
                    diff;
                }
                return asFloat ? output : absRound(output);
            },

            from: function from(time, withoutSuffix) {
                return moment.duration({ to: this, from: time }).locale(this.locale()).humanize(!withoutSuffix);
            },

            fromNow: function fromNow(withoutSuffix) {
                return this.from(moment(), withoutSuffix);
            },

            calendar: function calendar(time) {
                // We want to compare the start of today, vs this.
                // Getting start-of-today depends on whether we're zone'd or not.
                var now = time || moment(),
                    sod = makeAs(now, this).startOf('day'),
                    diff = this.diff(sod, 'days', true),
                    format = diff < -6 ? 'sameElse' : diff < -1 ? 'lastWeek' : diff < 0 ? 'lastDay' : diff < 1 ? 'sameDay' : diff < 2 ? 'nextDay' : diff < 7 ? 'nextWeek' : 'sameElse';
                return this.format(this.localeData().calendar(format, this));
            },

            isLeapYear: function isLeapYear() {
                return _isLeapYear(this.year());
            },

            isDST: function isDST() {
                return this.zone() < this.clone().month(0).zone() || this.zone() < this.clone().month(5).zone();
            },

            day: function day(input) {
                var day = this._isUTC ? this._d.getUTCDay() : this._d.getDay();
                if (input != null) {
                    input = parseWeekday(input, this.localeData());
                    return this.add(input - day, 'd');
                } else {
                    return day;
                }
            },

            month: makeAccessor('Month', true),

            startOf: function startOf(units) {
                units = normalizeUnits(units);
                // the following switch intentionally omits break keywords
                // to utilize falling through the cases.
                switch (units) {
                    case 'year':
                        this.month(0);
                    /* falls through */
                    case 'quarter':
                    case 'month':
                        this.date(1);
                    /* falls through */
                    case 'week':
                    case 'isoWeek':
                    case 'day':
                        this.hours(0);
                    /* falls through */
                    case 'hour':
                        this.minutes(0);
                    /* falls through */
                    case 'minute':
                        this.seconds(0);
                    /* falls through */
                    case 'second':
                        this.milliseconds(0);
                    /* falls through */
                }

                // weeks are a special case
                if (units === 'week') {
                    this.weekday(0);
                } else if (units === 'isoWeek') {
                    this.isoWeekday(1);
                }

                // quarters are also special
                if (units === 'quarter') {
                    this.month(Math.floor(this.month() / 3) * 3);
                }

                return this;
            },

            endOf: function endOf(units) {
                units = normalizeUnits(units);
                return this.startOf(units).add(1, units === 'isoWeek' ? 'week' : units).subtract(1, 'ms');
            },

            isAfter: function isAfter(input, units) {
                units = normalizeUnits(typeof units !== 'undefined' ? units : 'millisecond');
                if (units === 'millisecond') {
                    input = moment.isMoment(input) ? input : moment(input);
                    return +this > +input;
                } else {
                    return +this.clone().startOf(units) > +moment(input).startOf(units);
                }
            },

            isBefore: function isBefore(input, units) {
                units = normalizeUnits(typeof units !== 'undefined' ? units : 'millisecond');
                if (units === 'millisecond') {
                    input = moment.isMoment(input) ? input : moment(input);
                    return +this < +input;
                } else {
                    return +this.clone().startOf(units) < +moment(input).startOf(units);
                }
            },

            isSame: function isSame(input, units) {
                units = normalizeUnits(units || 'millisecond');
                if (units === 'millisecond') {
                    input = moment.isMoment(input) ? input : moment(input);
                    return +this === +input;
                } else {
                    return +this.clone().startOf(units) === +makeAs(input, this).startOf(units);
                }
            },

            min: deprecate('moment().min is deprecated, use moment.min instead. https://github.com/moment/moment/issues/1548', function (other) {
                other = moment.apply(null, arguments);
                return other < this ? this : other;
            }),

            max: deprecate('moment().max is deprecated, use moment.max instead. https://github.com/moment/moment/issues/1548', function (other) {
                other = moment.apply(null, arguments);
                return other > this ? this : other;
            }),

            // keepLocalTime = true means only change the timezone, without
            // affecting the local hour. So 5:31:26 +0300 --[zone(2, true)]-->
            // 5:31:26 +0200 It is possible that 5:31:26 doesn't exist int zone
            // +0200, so we adjust the time as needed, to be valid.
            //
            // Keeping the time actually adds/subtracts (one hour)
            // from the actual represented time. That is why we call updateOffset
            // a second time. In case it wants us to change the offset again
            // _changeInProgress == true case, then we have to adjust, because
            // there is no such time in the given timezone.
            zone: function zone(input, keepLocalTime) {
                var offset = this._offset || 0,
                    localAdjust;
                if (input != null) {
                    if (typeof input === 'string') {
                        input = timezoneMinutesFromString(input);
                    }
                    if (Math.abs(input) < 16) {
                        input = input * 60;
                    }
                    if (!this._isUTC && keepLocalTime) {
                        localAdjust = this._dateTzOffset();
                    }
                    this._offset = input;
                    this._isUTC = true;
                    if (localAdjust != null) {
                        this.subtract(localAdjust, 'm');
                    }
                    if (offset !== input) {
                        if (!keepLocalTime || this._changeInProgress) {
                            addOrSubtractDurationFromMoment(this, moment.duration(offset - input, 'm'), 1, false);
                        } else if (!this._changeInProgress) {
                            this._changeInProgress = true;
                            moment.updateOffset(this, true);
                            this._changeInProgress = null;
                        }
                    }
                } else {
                    return this._isUTC ? offset : this._dateTzOffset();
                }
                return this;
            },

            zoneAbbr: function zoneAbbr() {
                return this._isUTC ? 'UTC' : '';
            },

            zoneName: function zoneName() {
                return this._isUTC ? 'Coordinated Universal Time' : '';
            },

            parseZone: function parseZone() {
                if (this._tzm) {
                    this.zone(this._tzm);
                } else if (typeof this._i === 'string') {
                    this.zone(this._i);
                }
                return this;
            },

            hasAlignedHourOffset: function hasAlignedHourOffset(input) {
                if (!input) {
                    input = 0;
                } else {
                    input = moment(input).zone();
                }

                return (this.zone() - input) % 60 === 0;
            },

            daysInMonth: function daysInMonth() {
                return _daysInMonth(this.year(), this.month());
            },

            dayOfYear: function dayOfYear(input) {
                var dayOfYear = round((moment(this).startOf('day') - moment(this).startOf('year')) / 864e5) + 1;
                return input == null ? dayOfYear : this.add(input - dayOfYear, 'd');
            },

            quarter: function quarter(input) {
                return input == null ? Math.ceil((this.month() + 1) / 3) : this.month((input - 1) * 3 + this.month() % 3);
            },

            weekYear: function weekYear(input) {
                var year = weekOfYear(this, this.localeData()._week.dow, this.localeData()._week.doy).year;
                return input == null ? year : this.add(input - year, 'y');
            },

            isoWeekYear: function isoWeekYear(input) {
                var year = weekOfYear(this, 1, 4).year;
                return input == null ? year : this.add(input - year, 'y');
            },

            week: function week(input) {
                var week = this.localeData().week(this);
                return input == null ? week : this.add((input - week) * 7, 'd');
            },

            isoWeek: function isoWeek(input) {
                var week = weekOfYear(this, 1, 4).week;
                return input == null ? week : this.add((input - week) * 7, 'd');
            },

            weekday: function weekday(input) {
                var weekday = (this.day() + 7 - this.localeData()._week.dow) % 7;
                return input == null ? weekday : this.add(input - weekday, 'd');
            },

            isoWeekday: function isoWeekday(input) {
                // behaves the same as moment#day except
                // as a getter, returns 7 instead of 0 (1-7 range instead of 0-6)
                // as a setter, sunday should belong to the previous week.
                return input == null ? this.day() || 7 : this.day(this.day() % 7 ? input : input - 7);
            },

            isoWeeksInYear: function isoWeeksInYear() {
                return _weeksInYear(this.year(), 1, 4);
            },

            weeksInYear: function weeksInYear() {
                var weekInfo = this.localeData()._week;
                return _weeksInYear(this.year(), weekInfo.dow, weekInfo.doy);
            },

            get: function get(units) {
                units = normalizeUnits(units);
                return this[units]();
            },

            set: function set(units, value) {
                units = normalizeUnits(units);
                if (typeof this[units] === 'function') {
                    this[units](value);
                }
                return this;
            },

            // If passed a locale key, it will set the locale for this
            // instance.  Otherwise, it will return the locale configuration
            // variables for this instance.
            locale: function locale(key) {
                var newLocaleData;

                if (key === undefined) {
                    return this._locale._abbr;
                } else {
                    newLocaleData = moment.localeData(key);
                    if (newLocaleData != null) {
                        this._locale = newLocaleData;
                    }
                    return this;
                }
            },

            lang: deprecate('moment().lang() is deprecated. Use moment().localeData() instead.', function (key) {
                if (key === undefined) {
                    return this.localeData();
                } else {
                    return this.locale(key);
                }
            }),

            localeData: function localeData() {
                return this._locale;
            },

            _dateTzOffset: function _dateTzOffset() {
                // On Firefox.24 Date#getTimezoneOffset returns a floating point.
                // https://github.com/moment/moment/pull/1871
                return Math.round(this._d.getTimezoneOffset() / 15) * 15;
            }
        });

        function rawMonthSetter(mom, value) {
            var dayOfMonth;

            // TODO: Move this out of here!
            if (typeof value === 'string') {
                value = mom.localeData().monthsParse(value);
                // TODO: Another silent failure?
                if (typeof value !== 'number') {
                    return mom;
                }
            }

            dayOfMonth = Math.min(mom.date(), _daysInMonth(mom.year(), value));
            mom._d['set' + (mom._isUTC ? 'UTC' : '') + 'Month'](value, dayOfMonth);
            return mom;
        }

        function rawGetter(mom, unit) {
            return mom._d['get' + (mom._isUTC ? 'UTC' : '') + unit]();
        }

        function rawSetter(mom, unit, value) {
            if (unit === 'Month') {
                return rawMonthSetter(mom, value);
            } else {
                return mom._d['set' + (mom._isUTC ? 'UTC' : '') + unit](value);
            }
        }

        function makeAccessor(unit, keepTime) {
            return function (value) {
                if (value != null) {
                    rawSetter(this, unit, value);
                    moment.updateOffset(this, keepTime);
                    return this;
                } else {
                    return rawGetter(this, unit);
                }
            };
        }

        moment.fn.millisecond = moment.fn.milliseconds = makeAccessor('Milliseconds', false);
        moment.fn.second = moment.fn.seconds = makeAccessor('Seconds', false);
        moment.fn.minute = moment.fn.minutes = makeAccessor('Minutes', false);
        // Setting the hour should keep the time, because the user explicitly
        // specified which hour he wants. So trying to maintain the same hour (in
        // a new timezone) makes sense. Adding/subtracting hours does not follow
        // this rule.
        moment.fn.hour = moment.fn.hours = makeAccessor('Hours', true);
        // moment.fn.month is defined separately
        moment.fn.date = makeAccessor('Date', true);
        moment.fn.dates = deprecate('dates accessor is deprecated. Use date instead.', makeAccessor('Date', true));
        moment.fn.year = makeAccessor('FullYear', true);
        moment.fn.years = deprecate('years accessor is deprecated. Use year instead.', makeAccessor('FullYear', true));

        // add plural methods
        moment.fn.days = moment.fn.day;
        moment.fn.months = moment.fn.month;
        moment.fn.weeks = moment.fn.week;
        moment.fn.isoWeeks = moment.fn.isoWeek;
        moment.fn.quarters = moment.fn.quarter;

        // add aliased format methods
        moment.fn.toJSON = moment.fn.toISOString;

        /************************************
            Duration Prototype
        ************************************/

        function daysToYears(days) {
            // 400 years have 146097 days (taking into account leap year rules)
            return days * 400 / 146097;
        }

        function yearsToDays(years) {
            // years * 365 + absRound(years / 4) -
            //     absRound(years / 100) + absRound(years / 400);
            return years * 146097 / 400;
        }

        extend(moment.duration.fn = Duration.prototype, {

            _bubble: function _bubble() {
                var milliseconds = this._milliseconds,
                    days = this._days,
                    months = this._months,
                    data = this._data,
                    seconds,
                    minutes,
                    hours,
                    years = 0;

                // The following code bubbles up values, see the tests for
                // examples of what that means.
                data.milliseconds = milliseconds % 1000;

                seconds = absRound(milliseconds / 1000);
                data.seconds = seconds % 60;

                minutes = absRound(seconds / 60);
                data.minutes = minutes % 60;

                hours = absRound(minutes / 60);
                data.hours = hours % 24;

                days += absRound(hours / 24);

                // Accurately convert days to years, assume start from year 0.
                years = absRound(daysToYears(days));
                days -= absRound(yearsToDays(years));

                // 30 days to a month
                // TODO (iskren): Use anchor date (like 1st Jan) to compute this.
                months += absRound(days / 30);
                days %= 30;

                // 12 months -> 1 year
                years += absRound(months / 12);
                months %= 12;

                data.days = days;
                data.months = months;
                data.years = years;
            },

            abs: function abs() {
                this._milliseconds = Math.abs(this._milliseconds);
                this._days = Math.abs(this._days);
                this._months = Math.abs(this._months);

                this._data.milliseconds = Math.abs(this._data.milliseconds);
                this._data.seconds = Math.abs(this._data.seconds);
                this._data.minutes = Math.abs(this._data.minutes);
                this._data.hours = Math.abs(this._data.hours);
                this._data.months = Math.abs(this._data.months);
                this._data.years = Math.abs(this._data.years);

                return this;
            },

            weeks: function weeks() {
                return absRound(this.days() / 7);
            },

            valueOf: function valueOf() {
                return this._milliseconds + this._days * 864e5 + this._months % 12 * 2592e6 + toInt(this._months / 12) * 31536e6;
            },

            humanize: function humanize(withSuffix) {
                var output = relativeTime(this, !withSuffix, this.localeData());

                if (withSuffix) {
                    output = this.localeData().pastFuture(+this, output);
                }

                return this.localeData().postformat(output);
            },

            add: function add(input, val) {
                // supports only 2.0-style add(1, 's') or add(moment)
                var dur = moment.duration(input, val);

                this._milliseconds += dur._milliseconds;
                this._days += dur._days;
                this._months += dur._months;

                this._bubble();

                return this;
            },

            subtract: function subtract(input, val) {
                var dur = moment.duration(input, val);

                this._milliseconds -= dur._milliseconds;
                this._days -= dur._days;
                this._months -= dur._months;

                this._bubble();

                return this;
            },

            get: function get(units) {
                units = normalizeUnits(units);
                return this[units.toLowerCase() + 's']();
            },

            as: function as(units) {
                var days, months;
                units = normalizeUnits(units);

                if (units === 'month' || units === 'year') {
                    days = this._days + this._milliseconds / 864e5;
                    months = this._months + daysToYears(days) * 12;
                    return units === 'month' ? months : months / 12;
                } else {
                    // handle milliseconds separately because of floating point math errors (issue #1867)
                    days = this._days + yearsToDays(this._months / 12);
                    switch (units) {
                        case 'week':
                            return days / 7 + this._milliseconds / 6048e5;
                        case 'day':
                            return days + this._milliseconds / 864e5;
                        case 'hour':
                            return days * 24 + this._milliseconds / 36e5;
                        case 'minute':
                            return days * 24 * 60 + this._milliseconds / 6e4;
                        case 'second':
                            return days * 24 * 60 * 60 + this._milliseconds / 1000;
                        // Math.floor prevents floating point math errors here
                        case 'millisecond':
                            return Math.floor(days * 24 * 60 * 60 * 1000) + this._milliseconds;
                        default:
                            throw new Error('Unknown unit ' + units);
                    }
                }
            },

            lang: moment.fn.lang,
            locale: moment.fn.locale,

            toIsoString: deprecate('toIsoString() is deprecated. Please use toISOString() instead ' + '(notice the capitals)', function () {
                return this.toISOString();
            }),

            toISOString: function toISOString() {
                // inspired by https://github.com/dordille/moment-isoduration/blob/master/moment.isoduration.js
                var years = Math.abs(this.years()),
                    months = Math.abs(this.months()),
                    days = Math.abs(this.days()),
                    hours = Math.abs(this.hours()),
                    minutes = Math.abs(this.minutes()),
                    seconds = Math.abs(this.seconds() + this.milliseconds() / 1000);

                if (!this.asSeconds()) {
                    // this is the same as C#'s (Noda) and python (isodate)...
                    // but not other JS (goog.date)
                    return 'P0D';
                }

                return (this.asSeconds() < 0 ? '-' : '') + 'P' + (years ? years + 'Y' : '') + (months ? months + 'M' : '') + (days ? days + 'D' : '') + (hours || minutes || seconds ? 'T' : '') + (hours ? hours + 'H' : '') + (minutes ? minutes + 'M' : '') + (seconds ? seconds + 'S' : '');
            },

            localeData: function localeData() {
                return this._locale;
            }
        });

        moment.duration.fn.toString = moment.duration.fn.toISOString;

        function makeDurationGetter(name) {
            moment.duration.fn[name] = function () {
                return this._data[name];
            };
        }

        for (i in unitMillisecondFactors) {
            if (hasOwnProp(unitMillisecondFactors, i)) {
                makeDurationGetter(i.toLowerCase());
            }
        }

        moment.duration.fn.asMilliseconds = function () {
            return this.as('ms');
        };
        moment.duration.fn.asSeconds = function () {
            return this.as('s');
        };
        moment.duration.fn.asMinutes = function () {
            return this.as('m');
        };
        moment.duration.fn.asHours = function () {
            return this.as('h');
        };
        moment.duration.fn.asDays = function () {
            return this.as('d');
        };
        moment.duration.fn.asWeeks = function () {
            return this.as('weeks');
        };
        moment.duration.fn.asMonths = function () {
            return this.as('M');
        };
        moment.duration.fn.asYears = function () {
            return this.as('y');
        };

        /************************************
            Default Locale
        ************************************/

        // Set default locale, other locale will inherit from English.
        moment.locale('en', {
            ordinal: function ordinal(number) {
                var b = number % 10,
                    output = toInt(number % 100 / 10) === 1 ? 'th' : b === 1 ? 'st' : b === 2 ? 'nd' : b === 3 ? 'rd' : 'th';
                return number + output;
            }
        });

        return moment;
    }.call(this);

    UI.Utils.moment = moment;

    return UI.datepicker;
});
/* WEBPACK VAR INJECTION */}.call(exports, __webpack_require__("../node_modules/webpack/buildin/global.js")))

/***/ }),

/***/ "../bower_components/uikit/js/components/nestable.js":
/***/ (function(module, exports, __webpack_require__) {

"use strict";
var __WEBPACK_AMD_DEFINE_ARRAY__, __WEBPACK_AMD_DEFINE_RESULT__;

var _keys = __webpack_require__("../node_modules/babel-runtime/core-js/object/keys.js");

var _keys2 = _interopRequireDefault(_keys);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

/*! UIkit 2.27.4 | http://www.getuikit.com | (c) 2014 YOOtheme | MIT License */
/*
 * Based on Nestable jQuery Plugin - Copyright (c) 2012 David Bushell - http://dbushell.com/
 */
(function (addon) {

    var component;

    if (window.UIkit2) {
        component = addon(UIkit2);
    }

    if (true) {
        !(__WEBPACK_AMD_DEFINE_ARRAY__ = [__webpack_require__("uikit")], __WEBPACK_AMD_DEFINE_RESULT__ = (function () {
            return component || addon(UIkit2);
        }).apply(exports, __WEBPACK_AMD_DEFINE_ARRAY__),
				__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));
    }
})(function (UI) {

    "use strict";

    var hasTouch = 'ontouchstart' in window,
        html = UI.$html,
        touchedlists = [],
        $win = UI.$win,
        draggingElement;

    var eStart = hasTouch ? 'touchstart' : 'mousedown',
        eMove = hasTouch ? 'touchmove' : 'mousemove',
        eEnd = hasTouch ? 'touchend' : 'mouseup',
        eCancel = hasTouch ? 'touchcancel' : 'mouseup';

    UI.component('nestable', {

        defaults: {
            listBaseClass: 'uk-nestable',
            listClass: 'uk-nestable-list',
            listItemClass: 'uk-nestable-item',
            dragClass: 'uk-nestable-dragged',
            movingClass: 'uk-nestable-moving',
            noChildrenClass: 'uk-nestable-nochildren',
            emptyClass: 'uk-nestable-empty',
            handleClass: '',
            collapsedClass: 'uk-collapsed',
            placeholderClass: 'uk-nestable-placeholder',
            noDragClass: 'uk-nestable-nodrag',
            group: false,
            maxDepth: 10,
            threshold: 20,
            idlethreshold: 10
        },

        boot: function boot() {

            // adjust document scrolling
            UI.$html.on('mousemove touchmove', function (e) {

                if (draggingElement) {

                    var top = draggingElement.offset().top;

                    if (top < UI.$win.scrollTop()) {
                        UI.$win.scrollTop(UI.$win.scrollTop() - Math.ceil(draggingElement.height() / 2));
                    } else if (top + draggingElement.height() > window.innerHeight + UI.$win.scrollTop()) {
                        UI.$win.scrollTop(UI.$win.scrollTop() + Math.ceil(draggingElement.height() / 2));
                    }
                }
            });

            // init code
            UI.ready(function (context) {

                UI.$("[data-uk-nestable]", context).each(function () {

                    var ele = UI.$(this);

                    if (!ele.data("nestable")) {
                        UI.nestable(ele, UI.Utils.options(ele.attr("data-uk-nestable")));
                    }
                });
            });
        },

        init: function init() {

            var $this = this;

            (0, _keys2.default)(this.options).forEach(function (key) {

                if (String(key).indexOf('Class') != -1) {
                    $this.options['_' + key] = '.' + $this.options[key];
                }
            });

            this.find(this.options._listItemClass).find(">ul").addClass(this.options.listClass);

            this.checkEmptyList();

            this.reset();
            this.element.data('nestable-group', this.options.group || UI.Utils.uid('nestable-group'));

            this.find(this.options._listItemClass).each(function () {
                $this.setParent(UI.$(this));
            });

            this.on('click', '[data-nestable-action]', function (e) {

                if ($this.dragEl || !hasTouch && e.button !== 0) {
                    return;
                }

                e.preventDefault();

                var target = UI.$(e.currentTarget),
                    action = target.data('nestableAction'),
                    item = target.closest($this.options._listItemClass);

                if (action === 'collapse') {
                    $this.collapseItem(item);
                }
                if (action === 'expand') {
                    $this.expandItem(item);
                }
                if (action === 'toggle') {
                    $this.toggleItem(item);
                }
            });

            var onStartEvent = function onStartEvent(e) {

                var handle = UI.$(e.target),
                    link = handle.is('a[href]') ? handle : handle.parents('a[href]');

                if (e.target === $this.element[0]) {
                    return;
                }

                if (handle.is($this.options._noDragClass) || handle.closest($this.options._noDragClass).length) {
                    return;
                }

                if (handle.is('[data-nestable-action]') || handle.closest('[data-nestable-action]').length) {
                    return;
                }

                if ($this.options.handleClass && !handle.hasClass($this.options.handleClass)) {

                    if ($this.options.handleClass) {
                        handle = handle.closest($this.options._handleClass);
                    }
                }

                if (!handle.length || $this.dragEl || !hasTouch && e.button !== 0 || hasTouch && e.touches.length !== 1) {
                    return;
                }

                if (e.originalEvent && e.originalEvent.touches) {
                    e = evt.originalEvent.touches[0];
                }

                $this.delayMove = function (evt) {

                    link = false;

                    evt.preventDefault();
                    $this.dragStart(e);
                    $this.trigger('start.uk.nestable', [$this]);

                    $this.delayMove = false;
                };

                $this.delayMove.x = parseInt(e.pageX, 10);
                $this.delayMove.y = parseInt(e.pageY, 10);
                $this.delayMove.threshold = $this.options.idlethreshold;

                if (link.length && eEnd == 'touchend') {

                    $this.one(eEnd, function () {
                        if (link && link.attr('href').trim()) {
                            location.href = link.attr('href');
                        }
                    });
                }

                e.preventDefault();
            };

            var onMoveEvent = function onMoveEvent(e) {

                if (e.originalEvent && e.originalEvent.touches) {
                    e = e.originalEvent.touches[0];
                }

                if ($this.delayMove && (Math.abs(e.pageX - $this.delayMove.x) > $this.delayMove.threshold || Math.abs(e.pageY - $this.delayMove.y) > $this.delayMove.threshold)) {

                    if (!window.getSelection().toString()) {
                        $this.delayMove(e);
                    } else {
                        $this.delayMove = false;
                    }
                }

                if ($this.dragEl) {
                    e.preventDefault();
                    $this.dragMove(e);
                    $this.trigger('move.uk.nestable', [$this]);
                }
            };

            var onEndEvent = function onEndEvent(e) {

                if ($this.dragEl) {
                    e.preventDefault();
                    $this.dragStop(hasTouch ? e.touches[0] : e);
                }

                draggingElement = false;
                $this.delayMove = false;
            };

            if (hasTouch) {
                this.element[0].addEventListener(eStart, onStartEvent, false);
                window.addEventListener(eMove, onMoveEvent, false);
                window.addEventListener(eEnd, onEndEvent, false);
                window.addEventListener(eCancel, onEndEvent, false);
            } else {
                this.on(eStart, onStartEvent);
                $win.on(eMove, onMoveEvent);
                $win.on(eEnd, onEndEvent);
            }
        },

        serialize: function serialize() {

            var data,
                depth = 0,
                list = this,
                step = function step(level, depth) {

                var array = [],
                    items = level.children(list.options._listItemClass);

                items.each(function () {

                    var li = UI.$(this),
                        item = {},
                        attribute,
                        sub = li.children(list.options._listClass);

                    for (var i = 0, attr, val; i < li[0].attributes.length; i++) {
                        attribute = li[0].attributes[i];
                        if (attribute.name.indexOf('data-') === 0) {
                            attr = attribute.name.substr(5);
                            val = UI.Utils.str2json(attribute.value);
                            item[attr] = val || attribute.value == 'false' || attribute.value == '0' ? val : attribute.value;
                        }
                    }

                    if (sub.length) {
                        item.children = step(sub, depth + 1);
                    }

                    array.push(item);
                });
                return array;
            };

            data = step(list.element, depth);

            return data;
        },

        list: function list(options) {

            var data = [],
                list = this,
                depth = 0,
                step = function step(level, depth, parent) {

                var items = level.children(options._listItemClass);

                items.each(function (index) {
                    var li = UI.$(this),
                        item = UI.$.extend({ parent_id: parent ? parent : null, depth: depth, order: index }, li.data()),
                        sub = li.children(options._listClass);

                    data.push(item);

                    if (sub.length) {
                        step(sub, depth + 1, li.data(options.idProperty || 'id'));
                    }
                });
            };

            options = UI.$.extend({}, list.options, options);

            step(list.element, depth);

            return data;
        },

        reset: function reset() {

            this.mouse = {
                offsetX: 0,
                offsetY: 0,
                startX: 0,
                startY: 0,
                lastX: 0,
                lastY: 0,
                nowX: 0,
                nowY: 0,
                distX: 0,
                distY: 0,
                dirAx: 0,
                dirX: 0,
                dirY: 0,
                lastDirX: 0,
                lastDirY: 0,
                distAxX: 0,
                distAxY: 0
            };
            this.moving = false;
            this.dragEl = null;
            this.dragRootEl = null;
            this.dragDepth = 0;
            this.hasNewRoot = false;
            this.pointEl = null;

            for (var i = 0; i < touchedlists.length; i++) {
                this.checkEmptyList(touchedlists[i]);
            }

            touchedlists = [];
        },

        toggleItem: function toggleItem(li) {
            this[li.hasClass(this.options.collapsedClass) ? 'expandItem' : 'collapseItem'](li);
        },

        expandItem: function expandItem(li) {
            li.removeClass(this.options.collapsedClass);
        },

        collapseItem: function collapseItem(li) {
            var lists = li.children(this.options._listClass);
            if (lists.length) {
                li.addClass(this.options.collapsedClass);
            }
        },

        expandAll: function expandAll() {
            var list = this;
            this.find(list.options._listItemClass).each(function () {
                list.expandItem(UI.$(this));
            });
        },

        collapseAll: function collapseAll() {
            var list = this;
            this.find(list.options._listItemClass).each(function () {
                list.collapseItem(UI.$(this));
            });
        },

        setParent: function setParent(li) {

            if (li.children(this.options._listClass).length) {
                li.addClass('uk-parent');
            }
        },

        unsetParent: function unsetParent(li) {
            li.removeClass('uk-parent ' + this.options.collapsedClass);
            li.children(this.options._listClass).remove();
        },

        dragStart: function dragStart(e) {

            var mouse = this.mouse,
                target = UI.$(e.target),
                dragItem = target.closest(this.options._listItemClass),
                offset = dragItem.offset();

            this.placeEl = dragItem;

            mouse.offsetX = e.pageX - offset.left;
            mouse.offsetY = e.pageY - offset.top;

            mouse.startX = mouse.lastX = offset.left;
            mouse.startY = mouse.lastY = offset.top;

            this.dragRootEl = this.element;

            this.dragEl = UI.$('<ul></ul>').addClass(this.options.listClass + ' ' + this.options.dragClass).append(dragItem.clone());
            this.dragEl.css('width', dragItem.width());
            this.placeEl.addClass(this.options.placeholderClass);

            draggingElement = this.dragEl;

            this.tmpDragOnSiblings = [dragItem[0].previousSibling, dragItem[0].nextSibling];

            UI.$body.append(this.dragEl);

            this.dragEl.css({
                left: offset.left,
                top: offset.top
            });

            // total depth of dragging item
            var i,
                depth,
                items = this.dragEl.find(this.options._listItemClass);

            for (i = 0; i < items.length; i++) {
                depth = UI.$(items[i]).parents(this.options._listClass + ',' + this.options._listBaseClass).length;
                if (depth > this.dragDepth) {
                    this.dragDepth = depth;
                }
            }

            html.addClass(this.options.movingClass);
        },

        dragStop: function dragStop(e) {

            var el = UI.$(this.placeEl),
                root = this.placeEl.parents(this.options._listBaseClass + ':first');

            this.placeEl.removeClass(this.options.placeholderClass);
            this.dragEl.remove();

            if (this.element[0] !== root[0]) {

                root.trigger('change.uk.nestable', [root.data('nestable'), el, 'added']);
                this.element.trigger('change.uk.nestable', [this, el, 'removed']);
            } else {
                this.element.trigger('change.uk.nestable', [this, el, "moved"]);
            }

            this.trigger('stop.uk.nestable', [this, el]);

            this.reset();

            html.removeClass(this.options.movingClass);
        },

        dragMove: function dragMove(e) {
            var list,
                parent,
                prev,
                next,
                depth,
                opt = this.options,
                mouse = this.mouse,
                maxDepth = this.dragRootEl ? this.dragRootEl.data('nestable').options.maxDepth : opt.maxDepth;

            this.dragEl.css({
                left: e.pageX - mouse.offsetX,
                top: e.pageY - mouse.offsetY
            });

            // mouse position last events
            mouse.lastX = mouse.nowX;
            mouse.lastY = mouse.nowY;
            // mouse position this events
            mouse.nowX = e.pageX;
            mouse.nowY = e.pageY;
            // distance mouse moved between events
            mouse.distX = mouse.nowX - mouse.lastX;
            mouse.distY = mouse.nowY - mouse.lastY;
            // direction mouse was moving
            mouse.lastDirX = mouse.dirX;
            mouse.lastDirY = mouse.dirY;
            // direction mouse is now moving (on both axis)
            mouse.dirX = mouse.distX === 0 ? 0 : mouse.distX > 0 ? 1 : -1;
            mouse.dirY = mouse.distY === 0 ? 0 : mouse.distY > 0 ? 1 : -1;
            // axis mouse is now moving on
            var newAx = Math.abs(mouse.distX) > Math.abs(mouse.distY) ? 1 : 0;

            // do nothing on first move
            if (!mouse.moving) {
                mouse.dirAx = newAx;
                mouse.moving = true;
                return;
            }

            // calc distance moved on this axis (and direction)
            if (mouse.dirAx !== newAx) {
                mouse.distAxX = 0;
                mouse.distAxY = 0;
            } else {
                mouse.distAxX += Math.abs(mouse.distX);
                if (mouse.dirX !== 0 && mouse.dirX !== mouse.lastDirX) {
                    mouse.distAxX = 0;
                }
                mouse.distAxY += Math.abs(mouse.distY);
                if (mouse.dirY !== 0 && mouse.dirY !== mouse.lastDirY) {
                    mouse.distAxY = 0;
                }
            }
            mouse.dirAx = newAx;

            /**
             * move horizontal
             */
            if (mouse.dirAx && mouse.distAxX >= opt.threshold) {
                // reset move distance on x-axis for new phase
                mouse.distAxX = 0;
                prev = this.placeEl.prev('li');

                // increase horizontal level if previous sibling exists, is not collapsed, and does not have a 'no children' class
                if (mouse.distX > 0 && prev.length && !prev.hasClass(opt.collapsedClass) && !prev.hasClass(opt.noChildrenClass)) {

                    // cannot increase level when item above is collapsed
                    list = prev.find(opt._listClass).last();

                    // check if depth limit has reached
                    depth = this.placeEl.parents(opt._listClass + ',' + opt._listBaseClass).length;

                    if (depth + this.dragDepth <= maxDepth) {

                        // create new sub-level if one doesn't exist
                        if (!list.length) {
                            list = UI.$('<ul/>').addClass(opt.listClass);
                            list.append(this.placeEl);
                            prev.append(list);
                            this.setParent(prev);
                        } else {
                            // else append to next level up
                            list = prev.children(opt._listClass).last();
                            list.append(this.placeEl);
                        }
                    }
                }

                // decrease horizontal level
                if (mouse.distX < 0) {

                    // we cannot decrease the level if an item precedes the current one
                    next = this.placeEl.next(opt._listItemClass);
                    if (!next.length) {

                        // get parent ul of the list item
                        var parentUl = this.placeEl.closest([opt._listBaseClass, opt._listClass].join(','));
                        // try to get the li surrounding the ul
                        var surroundingLi = parentUl.closest(opt._listItemClass);

                        // if the ul is inside of a li (meaning it is nested)
                        if (surroundingLi.length) {
                            // we can decrease the horizontal level
                            surroundingLi.after(this.placeEl);
                            // if the previous parent ul is now empty
                            if (!parentUl.children().length) {
                                this.unsetParent(surroundingLi);
                            }
                        }
                    }
                }
            }

            var isEmpty = false;

            // find list item under cursor
            var pointX = e.pageX - (window.pageXOffset || document.scrollLeft || 0),
                pointY = e.pageY - (window.pageYOffset || document.documentElement.scrollTop);
            this.pointEl = UI.$(document.elementFromPoint(pointX, pointY));

            if (opt.handleClass && this.pointEl.hasClass(opt.handleClass)) {

                this.pointEl = this.pointEl.closest(opt._listItemClass);
            } else {

                var nestableitem = this.pointEl.closest(opt._listItemClass);

                if (nestableitem.length) {
                    this.pointEl = nestableitem;
                }
            }

            if (this.placeEl.find(this.pointEl).length) {
                return;
            }

            if (this.pointEl.data('nestable') && !this.pointEl.children().length) {
                isEmpty = true;
                this.checkEmptyList(this.pointEl);
            } else if (!this.pointEl.length || !this.pointEl.hasClass(opt.listItemClass)) {
                return;
            }

            // find parent list of item under cursor
            var pointElRoot = this.element,
                tmpRoot = this.pointEl.closest(this.options._listBaseClass),
                isNewRoot = pointElRoot[0] != tmpRoot[0];

            /**
             * move vertical
             */
            if (!mouse.dirAx || isNewRoot || isEmpty) {

                // check if groups match if dragging over new root
                if (isNewRoot && opt.group !== tmpRoot.data('nestable-group')) {
                    return;
                } else {
                    touchedlists.push(pointElRoot);
                }

                // check depth limit
                depth = this.dragDepth - 1 + this.pointEl.parents(opt._listClass + ',' + opt._listBaseClass).length;

                if (depth > maxDepth) {
                    return;
                }

                var before = e.pageY < this.pointEl.offset().top + this.pointEl.height() / 2;

                parent = this.placeEl.parent();

                if (isEmpty) {
                    this.pointEl.append(this.placeEl);
                } else if (before) {
                    this.pointEl.before(this.placeEl);
                } else {
                    this.pointEl.after(this.placeEl);
                }

                if (!parent.children().length) {
                    if (!parent.data('nestable')) this.unsetParent(parent.parent());
                }

                this.checkEmptyList(this.dragRootEl);
                this.checkEmptyList(pointElRoot);

                // parent root list has changed
                if (isNewRoot) {
                    this.dragRootEl = tmpRoot;
                    this.hasNewRoot = this.element[0] !== this.dragRootEl[0];
                }
            }
        },

        checkEmptyList: function checkEmptyList(list) {

            list = list ? UI.$(list) : this.element;

            if (this.options.emptyClass) {
                list[!list.children().length ? 'addClass' : 'removeClass'](this.options.emptyClass);
            }
        }

    });

    return UI.nestable;
});

/***/ }),

/***/ "../bower_components/uikit/js/components/notify.js":
/***/ (function(module, exports, __webpack_require__) {

"use strict";
var __WEBPACK_AMD_DEFINE_ARRAY__, __WEBPACK_AMD_DEFINE_RESULT__;

/*! UIkit 2.27.4 | http://www.getuikit.com | (c) 2014 YOOtheme | MIT License */
(function (addon) {

    var component;

    if (window.UIkit2) {
        component = addon(UIkit2);
    }

    if (true) {
        !(__WEBPACK_AMD_DEFINE_ARRAY__ = [__webpack_require__("uikit")], __WEBPACK_AMD_DEFINE_RESULT__ = (function () {
            return component || addon(UIkit2);
        }).apply(exports, __WEBPACK_AMD_DEFINE_ARRAY__),
				__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));
    }
})(function (UI) {

    "use strict";

    var containers = {},
        messages = {},
        notify = function notify(options) {

        if (UI.$.type(options) == 'string') {
            options = { message: options };
        }

        if (arguments[1]) {
            options = UI.$.extend(options, UI.$.type(arguments[1]) == 'string' ? { status: arguments[1] } : arguments[1]);
        }

        return new Message(options).show();
    },
        closeAll = function closeAll(group, instantly) {

        var id;

        if (group) {
            for (id in messages) {
                if (group === messages[id].group) messages[id].close(instantly);
            }
        } else {
            for (id in messages) {
                messages[id].close(instantly);
            }
        }
    };

    var Message = function Message(options) {

        this.options = UI.$.extend({}, Message.defaults, options);

        this.uuid = UI.Utils.uid('notifymsg');
        this.element = UI.$(['<div class="uk-notify-message">', '<a class="uk-close"></a>', '<div></div>', '</div>'].join('')).data("notifyMessage", this);

        this.content(this.options.message);

        // status
        if (this.options.status) {
            this.element.addClass('uk-notify-message-' + this.options.status);
            this.currentstatus = this.options.status;
        }

        this.group = this.options.group;

        messages[this.uuid] = this;

        if (!containers[this.options.pos]) {
            containers[this.options.pos] = UI.$('<div class="uk-notify uk-notify-' + this.options.pos + '"></div>').appendTo('body').on("click", ".uk-notify-message", function () {

                var message = UI.$(this).data('notifyMessage');

                message.element.trigger('manualclose.uk.notify', [message]);
                message.close();
            });
        }
    };

    UI.$.extend(Message.prototype, {

        uuid: false,
        element: false,
        timout: false,
        currentstatus: "",
        group: false,

        show: function show() {

            if (this.element.is(':visible')) return;

            var $this = this;

            containers[this.options.pos].show().prepend(this.element);

            var marginbottom = parseInt(this.element.css('margin-bottom'), 10);

            this.element.css({ opacity: 0, marginTop: -1 * this.element.outerHeight(), marginBottom: 0 }).animate({ opacity: 1, marginTop: 0, marginBottom: marginbottom }, function () {

                if ($this.options.timeout) {

                    var closefn = function closefn() {
                        $this.close();
                    };

                    $this.timeout = setTimeout(closefn, $this.options.timeout);

                    $this.element.hover(function () {
                        clearTimeout($this.timeout);
                    }, function () {
                        $this.timeout = setTimeout(closefn, $this.options.timeout);
                    });
                }
            });

            return this;
        },

        close: function close(instantly) {

            var $this = this,
                finalize = function finalize() {
                $this.element.remove();

                if (!containers[$this.options.pos].children().length) {
                    containers[$this.options.pos].hide();
                }

                $this.options.onClose.apply($this, []);
                $this.element.trigger('close.uk.notify', [$this]);

                delete messages[$this.uuid];
            };

            if (this.timeout) clearTimeout(this.timeout);

            if (instantly) {
                finalize();
            } else {
                this.element.animate({ opacity: 0, marginTop: -1 * this.element.outerHeight(), marginBottom: 0 }, function () {
                    finalize();
                });
            }
        },

        content: function content(html) {

            var container = this.element.find(">div");

            if (!html) {
                return container.html();
            }

            container.html(html);

            return this;
        },

        status: function status(_status) {

            if (!_status) {
                return this.currentstatus;
            }

            this.element.removeClass('uk-notify-message-' + this.currentstatus).addClass('uk-notify-message-' + _status);

            this.currentstatus = _status;

            return this;
        }
    });

    Message.defaults = {
        message: "",
        status: "",
        timeout: 5000,
        group: null,
        pos: 'top-center',
        onClose: function onClose() {}
    };

    UI.notify = notify;
    UI.notify.message = Message;
    UI.notify.closeAll = closeAll;

    return notify;
});

/***/ }),

/***/ "../bower_components/uikit/js/components/pagination.js":
/***/ (function(module, exports, __webpack_require__) {

"use strict";
var __WEBPACK_AMD_DEFINE_ARRAY__, __WEBPACK_AMD_DEFINE_RESULT__;

/*! UIkit 2.27.4 | http://www.getuikit.com | (c) 2014 YOOtheme | MIT License */
/*
 * Based on simplePagination - Copyright (c) 2012 Flavius Matis - http://flaviusmatis.github.com/simplePagination.js/ (MIT)
 */
(function (addon) {

    var component;

    if (window.UIkit2) {
        component = addon(UIkit2);
    }

    if (true) {
        !(__WEBPACK_AMD_DEFINE_ARRAY__ = [__webpack_require__("uikit")], __WEBPACK_AMD_DEFINE_RESULT__ = (function () {
            return component || addon(UIkit2);
        }).apply(exports, __WEBPACK_AMD_DEFINE_ARRAY__),
				__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));
    }
})(function (UI) {

    "use strict";

    UI.component('pagination', {

        defaults: {
            items: 1,
            itemsOnPage: 1,
            pages: 0,
            displayedPages: 7,
            edges: 1,
            currentPage: 0,
            lblPrev: false,
            lblNext: false,
            onSelectPage: function onSelectPage() {}
        },

        boot: function boot() {

            // init code
            UI.ready(function (context) {

                UI.$('[data-uk-pagination]', context).each(function () {
                    var ele = UI.$(this);

                    if (!ele.data('pagination')) {
                        UI.pagination(ele, UI.Utils.options(ele.attr('data-uk-pagination')));
                    }
                });
            });
        },

        init: function init() {

            var $this = this;

            this.pages = this.options.pages ? this.options.pages : Math.ceil(this.options.items / this.options.itemsOnPage) ? Math.ceil(this.options.items / this.options.itemsOnPage) : 1;
            this.currentPage = this.options.currentPage;
            this.halfDisplayed = this.options.displayedPages / 2;

            this.on('click', 'a[data-page]', function (e) {
                e.preventDefault();
                $this.selectPage(UI.$(this).data('page'));
            });

            this._render();
        },

        _getInterval: function _getInterval() {

            return {
                start: Math.ceil(this.currentPage > this.halfDisplayed ? Math.max(Math.min(this.currentPage - this.halfDisplayed, this.pages - this.options.displayedPages), 0) : 0),
                end: Math.ceil(this.currentPage > this.halfDisplayed ? Math.min(this.currentPage + this.halfDisplayed, this.pages) : Math.min(this.options.displayedPages, this.pages))
            };
        },

        render: function render(pages) {
            this.pages = pages ? pages : this.pages;
            this._render();
        },

        selectPage: function selectPage(pageIndex, pages) {
            this.currentPage = pageIndex;
            this.render(pages);

            this.options.onSelectPage.apply(this, [pageIndex]);
            this.trigger('select.uk.pagination', [pageIndex, this]);
        },

        _render: function _render() {

            var o = this.options,
                interval = this._getInterval(),
                i;

            this.element.empty();

            // Generate Prev link
            if (o.lblPrev) this._append(this.currentPage - 1, { text: o.lblPrev });

            // Generate start edges
            if (interval.start > 0 && o.edges > 0) {

                var end = Math.min(o.edges, interval.start);

                for (i = 0; i < end; i++) {
                    this._append(i);
                }if (o.edges < interval.start && interval.start - o.edges != 1) {
                    this.element.append('<li><span>...</span></li>');
                } else if (interval.start - o.edges == 1) {
                    this._append(o.edges);
                }
            }

            // Generate interval links
            for (i = interval.start; i < interval.end; i++) {
                this._append(i);
            } // Generate end edges
            if (interval.end < this.pages && o.edges > 0) {

                if (this.pages - o.edges > interval.end && this.pages - o.edges - interval.end != 1) {
                    this.element.append('<li><span>...</span></li>');
                } else if (this.pages - o.edges - interval.end == 1) {
                    this._append(interval.end++);
                }

                var begin = Math.max(this.pages - o.edges, interval.end);

                for (i = begin; i < this.pages; i++) {
                    this._append(i);
                }
            }

            // Generate Next link (unless option is set for at front)
            if (o.lblNext) this._append(this.currentPage + 1, { text: o.lblNext });
        },

        _append: function _append(pageIndex, opts) {

            var item, options;

            pageIndex = pageIndex < 0 ? 0 : pageIndex < this.pages ? pageIndex : this.pages - 1;
            options = UI.$.extend({ text: pageIndex + 1 }, opts);

            item = pageIndex == this.currentPage ? '<li class="uk-active"><span>' + options.text + '</span></li>' : '<li><a href="#page-' + (pageIndex + 1) + '" data-page="' + pageIndex + '">' + options.text + '</a></li>';

            this.element.append(item);
        }
    });

    return UI.pagination;
});

/***/ }),

/***/ "../bower_components/uikit/js/components/sortable.js":
/***/ (function(module, exports, __webpack_require__) {

"use strict";
var __WEBPACK_AMD_DEFINE_ARRAY__, __WEBPACK_AMD_DEFINE_RESULT__;

/*! UIkit 2.27.4 | http://www.getuikit.com | (c) 2014 YOOtheme | MIT License */
/*
  * Based on nativesortable - Copyright (c) Brian Grinstead - https://github.com/bgrins/nativesortable
  */
(function (addon) {

    var component;

    if (window.UIkit2) {
        component = addon(UIkit2);
    }

    if (true) {
        !(__WEBPACK_AMD_DEFINE_ARRAY__ = [__webpack_require__("uikit")], __WEBPACK_AMD_DEFINE_RESULT__ = (function () {
            return component || addon(UIkit2);
        }).apply(exports, __WEBPACK_AMD_DEFINE_ARRAY__),
				__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));
    }
})(function (UI) {

    "use strict";

    var supportsTouch = 'ontouchstart' in window || 'MSGesture' in window || window.DocumentTouch && document instanceof DocumentTouch,
        draggingPlaceholder,
        currentlyDraggingElement,
        currentlyDraggingTarget,
        dragging,
        moving,
        clickedlink,
        delayIdle,
        touchedlists,
        moved,
        overElement,
        startEvent;

    var POINTER_DOWN = supportsTouch ? 'MSGesture' in window ? 'pointerdown' : 'touchstart' : 'mousedown',
        POINTER_MOVE = supportsTouch ? 'MSGesture' in window ? 'pointermove' : 'touchmove' : 'mousemove',
        POINTER_UP = supportsTouch ? 'MSGesture' in window ? 'pointerup' : 'touchend' : 'mouseup';

    function closestSortable(ele) {

        ele = UI.$(ele);

        do {
            if (ele.data('sortable')) {
                return ele;
            }
            ele = UI.$(ele).parent();
        } while (ele.length);

        return ele;
    }

    UI.component('sortable', {

        defaults: {

            animation: 150,
            threshold: 10,

            childClass: 'uk-sortable-item',
            placeholderClass: 'uk-sortable-placeholder',
            overClass: 'uk-sortable-over',
            draggingClass: 'uk-sortable-dragged',
            dragMovingClass: 'uk-sortable-moving',
            baseClass: 'uk-sortable',
            noDragClass: 'uk-sortable-nodrag',
            emptyClass: 'uk-sortable-empty',
            dragCustomClass: '',
            handleClass: false,
            group: false,

            stop: function stop() {},
            start: function start() {},
            change: function change() {}
        },

        boot: function boot() {

            // auto init
            UI.ready(function (context) {

                UI.$('[data-uk-sortable]', context).each(function () {

                    var ele = UI.$(this);

                    if (!ele.data('sortable')) {
                        UI.sortable(ele, UI.Utils.options(ele.attr('data-uk-sortable')));
                    }
                });
            });

            UI.$html.on(POINTER_MOVE, function (e) {

                if (delayIdle) {

                    var src = e.originalEvent.targetTouches ? e.originalEvent.targetTouches[0] : e;

                    if (Math.abs(src.pageX - delayIdle.pos.x) > delayIdle.threshold || Math.abs(src.pageY - delayIdle.pos.y) > delayIdle.threshold) {
                        delayIdle.apply(src);
                    }
                }

                if (draggingPlaceholder) {

                    if (!moving) {
                        moving = true;
                        draggingPlaceholder.show();

                        draggingPlaceholder.$current.addClass(draggingPlaceholder.$sortable.options.placeholderClass);
                        draggingPlaceholder.$sortable.element.children().addClass(draggingPlaceholder.$sortable.options.childClass);

                        UI.$html.addClass(draggingPlaceholder.$sortable.options.dragMovingClass);
                    }

                    var offset = draggingPlaceholder.data('mouse-offset'),
                        ev = e.originalEvent.touches && e.originalEvent.touches[0] || e.originalEvent,
                        left = parseInt(ev.pageX, 10) + offset.left,
                        top = parseInt(ev.pageY, 10) + offset.top;

                    draggingPlaceholder.css({ left: left, top: top });

                    // adjust document scrolling

                    if (top + draggingPlaceholder.height() / 3 > document.body.offsetHeight) {
                        return;
                    }

                    if (top < UI.$win.scrollTop()) {
                        UI.$win.scrollTop(UI.$win.scrollTop() - Math.ceil(draggingPlaceholder.height() / 3));
                    } else if (top + draggingPlaceholder.height() / 3 > window.innerHeight + UI.$win.scrollTop()) {
                        UI.$win.scrollTop(UI.$win.scrollTop() + Math.ceil(draggingPlaceholder.height() / 3));
                    }
                }
            });

            UI.$html.on(POINTER_UP, function (e) {

                delayIdle = clickedlink = false;

                // dragging?
                if (!currentlyDraggingElement || !draggingPlaceholder) {
                    // completely reset dragging attempt. will cause weird delay behavior elsewise
                    currentlyDraggingElement = draggingPlaceholder = null;
                    return;
                }

                // inside or outside of sortable?
                var sortable = closestSortable(currentlyDraggingElement),
                    component = draggingPlaceholder.$sortable,
                    ev = { type: e.type };

                if (sortable[0]) {
                    component.dragDrop(ev, component.element);
                }
                component.dragEnd(ev, component.element);
            });
        },

        init: function init() {

            var $this = this,
                element = this.element[0];

            touchedlists = [];

            this.checkEmptyList();

            this.element.data('sortable-group', this.options.group ? this.options.group : UI.Utils.uid('sortable-group'));

            var handleDragStart = delegate(function (e) {

                if (e.data && e.data.sortable) {
                    return;
                }

                var $target = UI.$(e.target),
                    $link = $target.is('a[href]') ? $target : $target.parents('a[href]');

                if ($target.is(':input')) {
                    return;
                }

                if ($this.options.handleClass) {
                    var handle = $target.hasClass($this.options.handleClass) ? $target : $target.closest('.' + $this.options.handleClass, $this.element);
                    if (!handle.length) return;
                }

                e.preventDefault();

                if ($link.length) {

                    $link.one('click', function (e) {
                        e.preventDefault();
                    }).one(POINTER_UP, function () {

                        if (!moved) {
                            $link.trigger('click');
                            if (supportsTouch && $link.attr('href').trim()) {
                                location.href = $link.attr('href');
                            }
                        }
                    });
                }

                e.data = e.data || {};

                e.data.sortable = element;

                return $this.dragStart(e, this);
            });

            var handleDragEnter = delegate(UI.Utils.debounce(function (e) {
                return $this.dragEnter(e, this);
            }), 40);

            var handleDragLeave = delegate(function (e) {

                // Prevent dragenter on a child from allowing a dragleave on the container
                var previousCounter = $this.dragenterData(this);
                $this.dragenterData(this, previousCounter - 1);

                // This is a fix for child elements firing dragenter before the parent fires dragleave
                if (!$this.dragenterData(this)) {
                    UI.$(this).removeClass($this.options.overClass);
                    $this.dragenterData(this, false);
                }
            });

            var handleTouchMove = delegate(function (e) {

                if (!currentlyDraggingElement || currentlyDraggingElement === this || currentlyDraggingTarget === this) {
                    return true;
                }

                $this.element.children().removeClass($this.options.overClass);
                currentlyDraggingTarget = this;

                $this.moveElementNextTo(currentlyDraggingElement, this);

                return prevent(e);
            });

            // Bind/unbind standard mouse/touch events as a polyfill.
            function addDragHandlers() {

                if (supportsTouch && startEvent.touches && startEvent.touches.length) {
                    element.addEventListener(POINTER_MOVE, handleTouchMove, false);
                } else {
                    element.addEventListener('mouseover', handleDragEnter, false);
                    element.addEventListener('mouseout', handleDragLeave, false);
                }

                // document.addEventListener("selectstart", prevent, false);
            }

            function removeDragHandlers() {
                if (supportsTouch && startEvent.touches && startEvent.touches.length) {
                    element.removeEventListener(POINTER_MOVE, handleTouchMove, false);
                } else {
                    element.removeEventListener('mouseover', handleDragEnter, false);
                    element.removeEventListener('mouseout', handleDragLeave, false);
                }

                // document.removeEventListener("selectstart", prevent, false);
            }

            this.addDragHandlers = addDragHandlers;
            this.removeDragHandlers = removeDragHandlers;

            function handleDragMove(e) {

                if (!currentlyDraggingElement) {
                    return;
                }

                $this.dragMove(e, $this);
            }

            function delegate(fn) {

                return function (e) {

                    var touch, target, context;

                    startEvent = e;

                    if (e) {
                        touch = e.touches && e.touches[0] || e;
                        target = touch.target || e.target;

                        // Fix event.target for a touch event
                        if (supportsTouch && document.elementFromPoint) {
                            target = document.elementFromPoint(touch.pageX - document.body.scrollLeft, touch.pageY - document.body.scrollTop);
                        }

                        overElement = UI.$(target);
                    }

                    if (UI.$(target).hasClass('.' + $this.options.childClass)) {
                        fn.apply(target, [e]);
                    } else if (target !== element) {

                        // If a child is initiating the event or ending it, then use the container as context for the callback.
                        context = moveUpToChildNode(element, target);

                        if (context) {
                            fn.apply(context, [e]);
                        }
                    }
                };
            }

            window.addEventListener(POINTER_MOVE, handleDragMove, false);
            element.addEventListener(POINTER_DOWN, handleDragStart, false);
        },

        dragStart: function dragStart(e, elem) {

            moved = false;
            moving = false;
            dragging = false;

            var $this = this,
                target = UI.$(e.target);

            if (!supportsTouch && e.button == 2) {
                return;
            }

            if (target.is('.' + $this.options.noDragClass) || target.closest('.' + $this.options.noDragClass).length) {
                return;
            }

            // prevent dragging if taget is a form field
            if (target.is(':input')) {
                return;
            }

            currentlyDraggingElement = elem;

            // init drag placeholder
            if (draggingPlaceholder) {
                draggingPlaceholder.remove();
            }

            var $current = UI.$(currentlyDraggingElement),
                offset = $current.offset(),
                ev = e.touches && e.touches[0] || e;

            delayIdle = {

                pos: { x: ev.pageX, y: ev.pageY },
                threshold: $this.options.handleClass ? 1 : $this.options.threshold,
                apply: function apply(evt) {

                    draggingPlaceholder = UI.$('<div class="' + [$this.options.draggingClass, $this.options.dragCustomClass].join(' ') + '"></div>').css({
                        display: 'none',
                        top: offset.top,
                        left: offset.left,
                        width: $current.width(),
                        height: $current.height(),
                        padding: $current.css('padding')
                    }).data({
                        'mouse-offset': {
                            left: offset.left - parseInt(ev.pageX, 10),
                            top: offset.top - parseInt(ev.pageY, 10)
                        },
                        origin: $this.element,
                        index: $current.index()
                    }).append($current.html()).appendTo('body');

                    draggingPlaceholder.$current = $current;
                    draggingPlaceholder.$sortable = $this;

                    $current.data({
                        'start-list': $current.parent(),
                        'start-index': $current.index(),
                        'sortable-group': $this.options.group
                    });

                    $this.addDragHandlers();

                    $this.options.start(this, currentlyDraggingElement);
                    $this.trigger('start.uk.sortable', [$this, currentlyDraggingElement, draggingPlaceholder]);

                    moved = true;
                    delayIdle = false;
                }
            };
        },

        dragMove: function dragMove(e, elem) {

            overElement = UI.$(document.elementFromPoint(e.pageX - (document.body.scrollLeft || document.scrollLeft || 0), e.pageY - (document.body.scrollTop || document.documentElement.scrollTop || 0)));

            var overRoot = overElement.closest('.' + this.options.baseClass),
                groupOver = overRoot.data('sortable-group'),
                $current = UI.$(currentlyDraggingElement),
                currentRoot = $current.parent(),
                groupCurrent = $current.data('sortable-group'),
                overChild;

            if (overRoot[0] !== currentRoot[0] && groupCurrent !== undefined && groupOver === groupCurrent) {

                overRoot.data('sortable').addDragHandlers();

                touchedlists.push(overRoot);
                overRoot.children().addClass(this.options.childClass);

                // swap root
                if (overRoot.children().length > 0) {
                    overChild = overElement.closest('.' + this.options.childClass);

                    if (overChild.length) {
                        overChild.before($current);
                    } else {
                        overRoot.append($current);
                    }
                } else {
                    // empty list
                    overElement.append($current);
                }

                UI.$doc.trigger('mouseover');
            }

            this.checkEmptyList();
            this.checkEmptyList(currentRoot);
        },

        dragEnter: function dragEnter(e, elem) {

            if (!currentlyDraggingElement || currentlyDraggingElement === elem) {
                return true;
            }

            var previousCounter = this.dragenterData(elem);

            this.dragenterData(elem, previousCounter + 1);

            // Prevent dragenter on a child from allowing a dragleave on the container
            if (previousCounter === 0) {

                var currentlist = UI.$(elem).parent(),
                    startlist = UI.$(currentlyDraggingElement).data('start-list');

                if (currentlist[0] !== startlist[0]) {

                    var groupOver = currentlist.data('sortable-group'),
                        groupCurrent = UI.$(currentlyDraggingElement).data('sortable-group');

                    if ((groupOver || groupCurrent) && groupOver != groupCurrent) {
                        return false;
                    }
                }

                UI.$(elem).addClass(this.options.overClass);
                this.moveElementNextTo(currentlyDraggingElement, elem);
            }

            return false;
        },

        dragEnd: function dragEnd(e, elem) {

            var $this = this;

            // avoid triggering event twice
            if (currentlyDraggingElement) {
                // TODO: trigger on right element?
                this.options.stop(elem);
                this.trigger('stop.uk.sortable', [this]);
            }

            currentlyDraggingElement = null;
            currentlyDraggingTarget = null;

            touchedlists.push(this.element);
            touchedlists.forEach(function (el, i) {
                UI.$(el).children().each(function () {
                    if (this.nodeType === 1) {
                        UI.$(this).removeClass($this.options.overClass).removeClass($this.options.placeholderClass).removeClass($this.options.childClass);
                        $this.dragenterData(this, false);
                    }
                });
            });

            touchedlists = [];

            UI.$html.removeClass(this.options.dragMovingClass);

            this.removeDragHandlers();

            if (draggingPlaceholder) {
                draggingPlaceholder.remove();
                draggingPlaceholder = null;
            }
        },

        dragDrop: function dragDrop(e, elem) {

            if (e.type === 'drop') {

                if (e.stopPropagation) {
                    e.stopPropagation();
                }

                if (e.preventDefault) {
                    e.preventDefault();
                }
            }

            this.triggerChangeEvents();
        },

        triggerChangeEvents: function triggerChangeEvents() {

            // trigger events once
            if (!currentlyDraggingElement) return;

            var $current = UI.$(currentlyDraggingElement),
                oldRoot = draggingPlaceholder.data('origin'),
                newRoot = $current.closest('.' + this.options.baseClass),
                triggers = [],
                el = UI.$(currentlyDraggingElement);

            // events depending on move inside lists or across lists
            if (oldRoot[0] === newRoot[0] && draggingPlaceholder.data('index') != $current.index()) {
                triggers.push({ sortable: this, mode: 'moved' });
            } else if (oldRoot[0] != newRoot[0]) {
                triggers.push({ sortable: UI.$(newRoot).data('sortable'), mode: 'added' }, { sortable: UI.$(oldRoot).data('sortable'), mode: 'removed' });
            }

            triggers.forEach(function (trigger, i) {
                if (trigger.sortable) {
                    trigger.sortable.element.trigger('change.uk.sortable', [trigger.sortable, el, trigger.mode]);
                }
            });
        },

        dragenterData: function dragenterData(element, val) {

            element = UI.$(element);

            if (arguments.length == 1) {
                return parseInt(element.data('child-dragenter'), 10) || 0;
            } else if (!val) {
                element.removeData('child-dragenter');
            } else {
                element.data('child-dragenter', Math.max(0, val));
            }
        },

        moveElementNextTo: function moveElementNextTo(element, elementToMoveNextTo) {

            dragging = true;

            var $this = this,
                list = UI.$(element).parent().css('min-height', ''),
                next = isBelow(element, elementToMoveNextTo) ? elementToMoveNextTo : elementToMoveNextTo.nextSibling,
                children = list.children(),
                count = children.length;

            if (!$this.options.animation) {
                elementToMoveNextTo.parentNode.insertBefore(element, next);
                UI.Utils.checkDisplay($this.element.parent());
                return;
            }

            list.css('min-height', list.height());

            children.stop().each(function () {
                var ele = UI.$(this),
                    offset = ele.position();

                offset.width = ele.width();

                ele.data('offset-before', offset);
            });

            elementToMoveNextTo.parentNode.insertBefore(element, next);

            UI.Utils.checkDisplay($this.element.parent());

            children = list.children().each(function () {
                var ele = UI.$(this);
                ele.data('offset-after', ele.position());
            }).each(function () {
                var ele = UI.$(this),
                    before = ele.data('offset-before');
                ele.css({ position: 'absolute', top: before.top, left: before.left, minWidth: before.width });
            });

            children.each(function () {

                var ele = UI.$(this),
                    before = ele.data('offset-before'),
                    offset = ele.data('offset-after');

                ele.css('pointer-events', 'none').width();

                setTimeout(function () {
                    ele.animate({ 'top': offset.top, 'left': offset.left }, $this.options.animation, function () {
                        ele.css({ position: '', top: '', left: '', minWidth: '', 'pointer-events': '' }).removeClass($this.options.overClass).removeData('child-dragenter');
                        count--;
                        if (!count) {
                            list.css('min-height', '');
                            UI.Utils.checkDisplay($this.element.parent());
                        }
                    });
                }, 0);
            });
        },

        serialize: function serialize() {

            var data = [],
                item,
                attribute;

            this.element.children().each(function (j, child) {
                item = {};
                for (var i = 0, attr, val; i < child.attributes.length; i++) {
                    attribute = child.attributes[i];
                    if (attribute.name.indexOf('data-') === 0) {
                        attr = attribute.name.substr(5);
                        val = UI.Utils.str2json(attribute.value);
                        item[attr] = val || attribute.value == 'false' || attribute.value == '0' ? val : attribute.value;
                    }
                }
                data.push(item);
            });

            return data;
        },

        checkEmptyList: function checkEmptyList(list) {

            list = list ? UI.$(list) : this.element;

            if (this.options.emptyClass) {
                list[!list.children().length ? 'addClass' : 'removeClass'](this.options.emptyClass);
            }
        }
    });

    // helpers

    function isBelow(el1, el2) {

        var parent = el1.parentNode;

        if (el2.parentNode != parent) {
            return false;
        }

        var cur = el1.previousSibling;

        while (cur && cur.nodeType !== 9) {
            if (cur === el2) {
                return true;
            }
            cur = cur.previousSibling;
        }

        return false;
    }

    function moveUpToChildNode(parent, child) {
        var cur = child;
        if (cur == parent) {
            return null;
        }

        while (cur) {
            if (cur.parentNode === parent) {
                return cur;
            }

            cur = cur.parentNode;
            if (!cur || !cur.ownerDocument || cur.nodeType === 11) {
                break;
            }
        }
        return null;
    }

    function prevent(e) {
        if (e.stopPropagation) {
            e.stopPropagation();
        }
        if (e.preventDefault) {
            e.preventDefault();
        }
        e.returnValue = false;
    }

    return UI.sortable;
});

/***/ }),

/***/ "../bower_components/uikit/js/components/tooltip.js":
/***/ (function(module, exports, __webpack_require__) {

"use strict";
var __WEBPACK_AMD_DEFINE_ARRAY__, __WEBPACK_AMD_DEFINE_RESULT__;

/*! UIkit 2.27.4 | http://www.getuikit.com | (c) 2014 YOOtheme | MIT License */
(function (addon) {
    var component;

    if (window.UIkit2) {
        component = addon(UIkit2);
    }

    if (true) {
        !(__WEBPACK_AMD_DEFINE_ARRAY__ = [__webpack_require__("uikit")], __WEBPACK_AMD_DEFINE_RESULT__ = (function () {
            return component || addon(UIkit2);
        }).apply(exports, __WEBPACK_AMD_DEFINE_ARRAY__),
				__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));
    }
})(function (UI) {

    "use strict";

    var $tooltip, // tooltip container
    tooltipdelay, checkIdle;

    UI.component('tooltip', {

        defaults: {
            offset: 5,
            pos: 'top',
            animation: false,
            delay: 0, // in miliseconds
            cls: '',
            activeClass: 'uk-active',
            src: function src(ele) {
                var title = ele.attr('title');

                if (title !== undefined) {
                    ele.data('cached-title', title).removeAttr('title');
                }

                return ele.data("cached-title");
            }
        },

        tip: '',

        boot: function boot() {

            // init code
            UI.$html.on('mouseenter.tooltip.uikit focus.tooltip.uikit', '[data-uk-tooltip]', function (e) {
                var ele = UI.$(this);

                if (!ele.data('tooltip')) {
                    UI.tooltip(ele, UI.Utils.options(ele.attr('data-uk-tooltip')));
                    ele.trigger('mouseenter');
                }
            });
        },

        init: function init() {

            var $this = this;

            if (!$tooltip) {
                $tooltip = UI.$('<div class="uk-tooltip"></div>').appendTo("body");
            }

            this.on({
                focus: function focus(e) {
                    $this.show();
                },
                blur: function blur(e) {
                    $this.hide();
                },
                mouseenter: function mouseenter(e) {
                    $this.show();
                },
                mouseleave: function mouseleave(e) {
                    $this.hide();
                }
            });
        },

        show: function show() {

            this.tip = typeof this.options.src === 'function' ? this.options.src(this.element) : this.options.src;

            if (tooltipdelay) clearTimeout(tooltipdelay);
            if (checkIdle) clearInterval(checkIdle);

            if (typeof this.tip === 'string' ? !this.tip.length : true) return;

            $tooltip.stop().css({ top: -2000, visibility: 'hidden' }).removeClass(this.options.activeClass).show();
            $tooltip.html('<div class="uk-tooltip-inner">' + this.tip + '</div>');

            var $this = this,
                pos = UI.$.extend({}, this.element.offset(), { width: this.element[0].offsetWidth, height: this.element[0].offsetHeight }),
                width = $tooltip[0].offsetWidth,
                height = $tooltip[0].offsetHeight,
                offset = typeof this.options.offset === "function" ? this.options.offset.call(this.element) : this.options.offset,
                position = typeof this.options.pos === "function" ? this.options.pos.call(this.element) : this.options.pos,
                tmppos = position.split("-"),
                tcss = {
                display: 'none',
                visibility: 'visible',
                top: pos.top + pos.height + height,
                left: pos.left
            };

            // prevent strange position
            // when tooltip is in offcanvas etc.
            if (UI.$html.css('position') == 'fixed' || UI.$body.css('position') == 'fixed') {
                var bodyoffset = UI.$('body').offset(),
                    htmloffset = UI.$('html').offset(),
                    docoffset = { top: htmloffset.top + bodyoffset.top, left: htmloffset.left + bodyoffset.left };

                pos.left -= docoffset.left;
                pos.top -= docoffset.top;
            }

            if ((tmppos[0] == 'left' || tmppos[0] == 'right') && UI.langdirection == 'right') {
                tmppos[0] = tmppos[0] == 'left' ? 'right' : 'left';
            }

            var variants = {
                bottom: { top: pos.top + pos.height + offset, left: pos.left + pos.width / 2 - width / 2 },
                top: { top: pos.top - height - offset, left: pos.left + pos.width / 2 - width / 2 },
                left: { top: pos.top + pos.height / 2 - height / 2, left: pos.left - width - offset },
                right: { top: pos.top + pos.height / 2 - height / 2, left: pos.left + pos.width + offset }
            };

            UI.$.extend(tcss, variants[tmppos[0]]);

            if (tmppos.length == 2) tcss.left = tmppos[1] == 'left' ? pos.left : pos.left + pos.width - width;

            var boundary = this.checkBoundary(tcss.left, tcss.top, width, height);

            if (boundary) {

                switch (boundary) {
                    case 'x':

                        if (tmppos.length == 2) {
                            position = tmppos[0] + "-" + (tcss.left < 0 ? 'left' : 'right');
                        } else {
                            position = tcss.left < 0 ? 'right' : 'left';
                        }

                        break;

                    case 'y':
                        if (tmppos.length == 2) {
                            position = (tcss.top < 0 ? 'bottom' : 'top') + '-' + tmppos[1];
                        } else {
                            position = tcss.top < 0 ? 'bottom' : 'top';
                        }

                        break;

                    case 'xy':
                        if (tmppos.length == 2) {
                            position = (tcss.top < 0 ? 'bottom' : 'top') + '-' + (tcss.left < 0 ? 'left' : 'right');
                        } else {
                            position = tcss.left < 0 ? 'right' : 'left';
                        }

                        break;

                }

                tmppos = position.split('-');

                UI.$.extend(tcss, variants[tmppos[0]]);

                if (tmppos.length == 2) tcss.left = tmppos[1] == 'left' ? pos.left : pos.left + pos.width - width;
            }

            tcss.left -= UI.$body.position().left;

            tooltipdelay = setTimeout(function () {

                $tooltip.css(tcss).attr('class', ['uk-tooltip', 'uk-tooltip-' + position, $this.options.cls].join(' '));

                if ($this.options.animation) {
                    $tooltip.css({ opacity: 0, display: 'block' }).addClass($this.options.activeClass).animate({ opacity: 1 }, parseInt($this.options.animation, 10) || 400);
                } else {
                    $tooltip.show().addClass($this.options.activeClass);
                }

                tooltipdelay = false;

                // close tooltip if element was removed or hidden
                checkIdle = setInterval(function () {
                    if (!$this.element.is(':visible')) $this.hide();
                }, 150);
            }, parseInt(this.options.delay, 10) || 0);
        },

        hide: function hide() {

            if (this.element.is('input') && this.element[0] === document.activeElement) return;

            if (tooltipdelay) clearTimeout(tooltipdelay);
            if (checkIdle) clearInterval(checkIdle);

            $tooltip.stop();

            if (this.options.animation) {

                var $this = this;

                $tooltip.fadeOut(parseInt(this.options.animation, 10) || 400, function () {
                    $tooltip.removeClass($this.options.activeClass);
                });
            } else {
                $tooltip.hide().removeClass(this.options.activeClass);
            }
        },

        content: function content() {
            return this.tip;
        },

        checkBoundary: function checkBoundary(left, top, width, height) {

            var axis = "";

            if (left < 0 || left - UI.$win.scrollLeft() + width > window.innerWidth) {
                axis += "x";
            }

            if (top < 0 || top - UI.$win.scrollTop() + height > window.innerHeight) {
                axis += "y";
            }

            return axis;
        }
    });

    return UI.tooltip;
});

/***/ }),

/***/ "../bower_components/uikit/js/uikit.js":
/***/ (function(module, exports, __webpack_require__) {

"use strict";
var __WEBPACK_AMD_DEFINE_RESULT__;

var _typeof2 = __webpack_require__("../node_modules/babel-runtime/helpers/typeof.js");

var _typeof3 = _interopRequireDefault(_typeof2);

var _keys = __webpack_require__("../node_modules/babel-runtime/core-js/object/keys.js");

var _keys2 = _interopRequireDefault(_keys);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

/*! UIkit 2.27.4 | http://www.getuikit.com | (c) 2014 YOOtheme | MIT License */
(function (core) {

    var uikit;

    if (!window.jQuery) {
        throw new Error('UIkit 2.x requires jQuery');
    } else {
        uikit = core(window.jQuery);
    }

    if (true) {
        // AMD

        !(__WEBPACK_AMD_DEFINE_RESULT__ = (function () {

            uikit.load = function (res, req, onload, config) {

                var resources = res.split(','),
                    load = [],
                    i,
                    base = (config.config && config.config.uikit && config.config.uikit.base ? config.config.uikit.base : '').replace(/\/+$/g, '');

                if (!base) {
                    throw new Error('Please define base path to UIkit in the requirejs config.');
                }

                for (i = 0; i < resources.length; i += 1) {
                    var resource = resources[i].replace(/\./g, '/');
                    load.push(base + '/components/' + resource);
                }

                req(load, function () {
                    onload(uikit);
                });
            };

            return uikit;
        }).call(exports, __webpack_require__, exports, module),
				__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));
    }
})(function ($) {

    "use strict";

    if (window.UIkit2) {
        return window.UIkit2;
    }

    var UI = {},
        _UI = window.UIkit || undefined;

    UI.version = '2.27.4';

    UI.noConflict = function () {
        // restore UIkit version
        if (_UI) {
            window.UIkit = _UI;
            $.UIkit = _UI;
            $.fn.uk = _UI.fn;
        }

        return UI;
    };

    window.UIkit2 = UI;

    if (!_UI) {
        window.UIkit = UI;
    }

    // cache jQuery
    UI.$ = $;

    UI.$doc = UI.$(document);
    UI.$win = UI.$(window);
    UI.$html = UI.$('html');

    UI.support = {};
    UI.support.transition = function () {

        var transitionEnd = function () {

            var element = document.body || document.documentElement,
                transEndEventNames = {
                WebkitTransition: 'webkitTransitionEnd',
                MozTransition: 'transitionend',
                OTransition: 'oTransitionEnd otransitionend',
                transition: 'transitionend'
            },
                name;

            for (name in transEndEventNames) {
                if (element.style[name] !== undefined) return transEndEventNames[name];
            }
        }();

        return transitionEnd && { end: transitionEnd };
    }();

    UI.support.animation = function () {

        var animationEnd = function () {

            var element = document.body || document.documentElement,
                animEndEventNames = {
                WebkitAnimation: 'webkitAnimationEnd',
                MozAnimation: 'animationend',
                OAnimation: 'oAnimationEnd oanimationend',
                animation: 'animationend'
            },
                name;

            for (name in animEndEventNames) {
                if (element.style[name] !== undefined) return animEndEventNames[name];
            }
        }();

        return animationEnd && { end: animationEnd };
    }();

    // requestAnimationFrame polyfill
    //https://github.com/darius/requestAnimationFrame
    (function () {

        Date.now = Date.now || function () {
            return new Date().getTime();
        };

        var vendors = ['webkit', 'moz'];
        for (var i = 0; i < vendors.length && !window.requestAnimationFrame; ++i) {
            var vp = vendors[i];
            window.requestAnimationFrame = window[vp + 'RequestAnimationFrame'];
            window.cancelAnimationFrame = window[vp + 'CancelAnimationFrame'] || window[vp + 'CancelRequestAnimationFrame'];
        }
        if (/iP(ad|hone|od).*OS 6/.test(window.navigator.userAgent) // iOS6 is buggy
        || !window.requestAnimationFrame || !window.cancelAnimationFrame) {
            var lastTime = 0;
            window.requestAnimationFrame = function (callback) {
                var now = Date.now();
                var nextTime = Math.max(lastTime + 16, now);
                return setTimeout(function () {
                    callback(lastTime = nextTime);
                }, nextTime - now);
            };
            window.cancelAnimationFrame = clearTimeout;
        }
    })();

    UI.support.touch = 'ontouchstart' in document || window.DocumentTouch && document instanceof window.DocumentTouch || window.navigator.msPointerEnabled && window.navigator.msMaxTouchPoints > 0 || //IE 10
    window.navigator.pointerEnabled && window.navigator.maxTouchPoints > 0 || //IE >=11
    false;

    UI.support.mutationobserver = window.MutationObserver || window.WebKitMutationObserver || null;

    UI.Utils = {};

    UI.Utils.isFullscreen = function () {
        return document.webkitFullscreenElement || document.mozFullScreenElement || document.msFullscreenElement || document.fullscreenElement || false;
    };

    UI.Utils.str2json = function (str, notevil) {
        try {
            if (notevil) {
                return JSON.parse(str
                // wrap keys without quote with valid double quote
                .replace(/([\$\w]+)\s*:/g, function (_, $1) {
                    return '"' + $1 + '":';
                })
                // replacing single quote wrapped ones to double quote
                .replace(/'([^']+)'/g, function (_, $1) {
                    return '"' + $1 + '"';
                }));
            } else {
                return new Function('', 'var json = ' + str + '; return JSON.parse(JSON.stringify(json));')();
            }
        } catch (e) {
            return false;
        }
    };

    UI.Utils.debounce = function (func, wait, immediate) {
        var timeout;
        return function () {
            var context = this,
                args = arguments;
            var later = function later() {
                timeout = null;
                if (!immediate) func.apply(context, args);
            };
            var callNow = immediate && !timeout;
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
            if (callNow) func.apply(context, args);
        };
    };

    UI.Utils.throttle = function (func, limit) {
        var wait = false;
        return function () {
            if (!wait) {
                func.call();
                wait = true;
                setTimeout(function () {
                    wait = false;
                }, limit);
            }
        };
    };

    UI.Utils.removeCssRules = function (selectorRegEx) {
        var idx, idxs, stylesheet, _i, _j, _k, _len, _len1, _len2, _ref;

        if (!selectorRegEx) return;

        setTimeout(function () {
            try {
                _ref = document.styleSheets;
                for (_i = 0, _len = _ref.length; _i < _len; _i++) {
                    stylesheet = _ref[_i];
                    idxs = [];
                    stylesheet.cssRules = stylesheet.cssRules;
                    for (idx = _j = 0, _len1 = stylesheet.cssRules.length; _j < _len1; idx = ++_j) {
                        if (stylesheet.cssRules[idx].type === CSSRule.STYLE_RULE && selectorRegEx.test(stylesheet.cssRules[idx].selectorText)) {
                            idxs.unshift(idx);
                        }
                    }
                    for (_k = 0, _len2 = idxs.length; _k < _len2; _k++) {
                        stylesheet.deleteRule(idxs[_k]);
                    }
                }
            } catch (_error) {}
        }, 0);
    };

    UI.Utils.isInView = function (element, options) {

        var $element = $(element);

        if (!$element.is(':visible')) {
            return false;
        }

        var window_left = UI.$win.scrollLeft(),
            window_top = UI.$win.scrollTop(),
            offset = $element.offset(),
            left = offset.left,
            top = offset.top;

        options = $.extend({ topoffset: 0, leftoffset: 0 }, options);

        if (top + $element.height() >= window_top && top - options.topoffset <= window_top + UI.$win.height() && left + $element.width() >= window_left && left - options.leftoffset <= window_left + UI.$win.width()) {
            return true;
        } else {
            return false;
        }
    };

    UI.Utils.checkDisplay = function (context, initanimation) {

        var elements = UI.$('[data-uk-margin], [data-uk-grid-match], [data-uk-grid-margin], [data-uk-check-display]', context || document),
            animated;

        if (context && !elements.length) {
            elements = $(context);
        }

        elements.trigger('display.uk.check');

        // fix firefox / IE animations
        if (initanimation) {

            if (typeof initanimation != 'string') {
                initanimation = '[class*="uk-animation-"]';
            }

            elements.find(initanimation).each(function () {

                var ele = UI.$(this),
                    cls = ele.attr('class'),
                    anim = cls.match(/uk-animation-(.+)/);

                ele.removeClass(anim[0]).width();

                ele.addClass(anim[0]);
            });
        }

        return elements;
    };

    UI.Utils.options = function (string) {

        if ($.type(string) != 'string') return string;

        if (string.indexOf(':') != -1 && string.trim().substr(-1) != '}') {
            string = '{' + string + '}';
        }

        var start = string ? string.indexOf("{") : -1,
            options = {};

        if (start != -1) {
            try {
                options = UI.Utils.str2json(string.substr(start));
            } catch (e) {}
        }

        return options;
    };

    UI.Utils.animate = function (element, cls) {

        var d = $.Deferred();

        element = UI.$(element);

        element.css('display', 'none').addClass(cls).one(UI.support.animation.end, function () {
            element.removeClass(cls);
            d.resolve();
        });

        element.css('display', '');

        return d.promise();
    };

    UI.Utils.uid = function (prefix) {
        return (prefix || 'id') + new Date().getTime() + "RAND" + Math.ceil(Math.random() * 100000);
    };

    UI.Utils.template = function (str, data) {

        var tokens = str.replace(/\n/g, '\\n').replace(/\{\{\{\s*(.+?)\s*\}\}\}/g, "{{!$1}}").split(/(\{\{\s*(.+?)\s*\}\})/g),
            i = 0,
            toc,
            cmd,
            prop,
            val,
            fn,
            output = [],
            openblocks = 0;

        while (i < tokens.length) {

            toc = tokens[i];

            if (toc.match(/\{\{\s*(.+?)\s*\}\}/)) {
                i = i + 1;
                toc = tokens[i];
                cmd = toc[0];
                prop = toc.substring(toc.match(/^(\^|\#|\!|\~|\:)/) ? 1 : 0);

                switch (cmd) {
                    case '~':
                        output.push('for(var $i=0;$i<' + prop + '.length;$i++) { var $item = ' + prop + '[$i];');
                        openblocks++;
                        break;
                    case ':':
                        output.push('for(var $key in ' + prop + ') { var $val = ' + prop + '[$key];');
                        openblocks++;
                        break;
                    case '#':
                        output.push('if(' + prop + ') {');
                        openblocks++;
                        break;
                    case '^':
                        output.push('if(!' + prop + ') {');
                        openblocks++;
                        break;
                    case '/':
                        output.push('}');
                        openblocks--;
                        break;
                    case '!':
                        output.push('__ret.push(' + prop + ');');
                        break;
                    default:
                        output.push('__ret.push(escape(' + prop + '));');
                        break;
                }
            } else {
                output.push("__ret.push('" + toc.replace(/\'/g, "\\'") + "');");
            }
            i = i + 1;
        }

        fn = new Function('$data', ['var __ret = [];', 'try {', 'with($data){', !openblocks ? output.join('') : '__ret = ["Not all blocks are closed correctly."]', '};', '}catch(e){__ret = [e.message];}', 'return __ret.join("").replace(/\\n\\n/g, "\\n");', "function escape(html) { return String(html).replace(/&/g, '&amp;').replace(/\"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');}"].join("\n"));

        return data ? fn(data) : fn;
    };

    UI.Utils.focus = function (element, extra) {

        element = $(element);

        if (!element.length) {
            return element;
        }

        var autofocus = element.find('[autofocus]:first'),
            tabidx;

        if (autofocus.length) {
            return autofocus.focus();
        }

        autofocus = element.find(':input' + (extra && ',' + extra || '')).first();

        if (autofocus.length) {
            return autofocus.focus();
        }

        if (!element.attr('tabindex')) {
            tabidx = 1000;
            element.attr('tabindex', tabidx);
        }

        element[0].focus();

        if (tabidx) {
            element.attr('tabindex', '');
        }

        return element;
    };

    UI.Utils.events = {};
    UI.Utils.events.click = UI.support.touch ? 'tap' : 'click';

    // deprecated

    UI.fn = function (command, options) {

        var args = arguments,
            cmd = command.match(/^([a-z\-]+)(?:\.([a-z]+))?/i),
            component = cmd[1],
            method = cmd[2];

        if (!UI[component]) {
            $.error('UIkit component [' + component + '] does not exist.');
            return this;
        }

        return this.each(function () {
            var $this = $(this),
                data = $this.data(component);
            if (!data) $this.data(component, data = UI[component](this, method ? undefined : options));
            if (method) data[method].apply(data, Array.prototype.slice.call(args, 1));
        });
    };

    $.UIkit = UI;
    $.fn.uk = UI.fn;

    UI.langdirection = UI.$html.attr("dir") == "rtl" ? "right" : "left";

    UI.components = {};

    UI.component = function (name, def, override) {

        if (UI.components[name] && !override) {
            return UI.components[name];
        }

        var fn = function fn(element, options) {

            var $this = this;

            this.UIkit = UI;
            this.element = element ? UI.$(element) : null;
            this.options = $.extend(true, {}, this.defaults, options);
            this.plugins = {};

            if (this.element) {
                this.element.data(name, this);
            }

            this.init();

            (this.options.plugins.length ? this.options.plugins : (0, _keys2.default)(fn.plugins)).forEach(function (plugin) {

                if (fn.plugins[plugin].init) {
                    fn.plugins[plugin].init($this);
                    $this.plugins[plugin] = true;
                }
            });

            this.trigger('init.uk.component', [name, this]);

            return this;
        };

        fn.plugins = {};

        $.extend(true, fn.prototype, {

            defaults: { plugins: [] },

            boot: function boot() {},
            init: function init() {},

            on: function on(a1, a2, a3) {
                return UI.$(this.element || this).on(a1, a2, a3);
            },

            one: function one(a1, a2, a3) {
                return UI.$(this.element || this).one(a1, a2, a3);
            },

            off: function off(evt) {
                return UI.$(this.element || this).off(evt);
            },

            trigger: function trigger(evt, params) {
                return UI.$(this.element || this).trigger(evt, params);
            },

            find: function find(selector) {
                return UI.$(this.element ? this.element : []).find(selector);
            },

            proxy: function proxy(obj, methods) {

                var $this = this;

                methods.split(' ').forEach(function (method) {
                    if (!$this[method]) $this[method] = function () {
                        return obj[method].apply(obj, arguments);
                    };
                });
            },

            mixin: function mixin(obj, methods) {

                var $this = this;

                methods.split(' ').forEach(function (method) {
                    if (!$this[method]) $this[method] = obj[method].bind($this);
                });
            },

            option: function option() {

                if (arguments.length == 1) {
                    return this.options[arguments[0]] || undefined;
                } else if (arguments.length == 2) {
                    this.options[arguments[0]] = arguments[1];
                }
            }

        }, def);

        this.components[name] = fn;

        this[name] = function () {

            var element, options;

            if (arguments.length) {

                switch (arguments.length) {
                    case 1:

                        if (typeof arguments[0] === 'string' || arguments[0].nodeType || arguments[0] instanceof jQuery) {
                            element = $(arguments[0]);
                        } else {
                            options = arguments[0];
                        }

                        break;
                    case 2:

                        element = $(arguments[0]);
                        options = arguments[1];
                        break;
                }
            }

            if (element && element.data(name)) {
                return element.data(name);
            }

            return new UI.components[name](element, options);
        };

        if (UI.domready) {
            UI.component.boot(name);
        }

        return fn;
    };

    UI.plugin = function (component, name, def) {
        this.components[component].plugins[name] = def;
    };

    UI.component.boot = function (name) {

        if (UI.components[name].prototype && UI.components[name].prototype.boot && !UI.components[name].booted) {
            UI.components[name].prototype.boot.apply(UI, []);
            UI.components[name].booted = true;
        }
    };

    UI.component.bootComponents = function () {

        for (var component in UI.components) {
            UI.component.boot(component);
        }
    };

    // DOM mutation save ready helper function

    UI.domObservers = [];
    UI.domready = false;

    UI.ready = function (fn) {

        UI.domObservers.push(fn);

        if (UI.domready) {
            fn(document);
        }
    };

    UI.on = function (a1, a2, a3) {

        if (a1 && a1.indexOf('ready.uk.dom') > -1 && UI.domready) {
            a2.apply(UI.$doc);
        }

        return UI.$doc.on(a1, a2, a3);
    };

    UI.one = function (a1, a2, a3) {

        if (a1 && a1.indexOf('ready.uk.dom') > -1 && UI.domready) {
            a2.apply(UI.$doc);
            return UI.$doc;
        }

        return UI.$doc.one(a1, a2, a3);
    };

    UI.trigger = function (evt, params) {
        return UI.$doc.trigger(evt, params);
    };

    UI.domObserve = function (selector, fn) {

        if (!UI.support.mutationobserver) return;

        fn = fn || function () {};

        UI.$(selector).each(function () {

            var element = this,
                $element = UI.$(element);

            if ($element.data('observer')) {
                return;
            }

            try {

                var observer = new UI.support.mutationobserver(UI.Utils.debounce(function (mutations) {
                    fn.apply(element, [$element]);
                    $element.trigger('changed.uk.dom');
                }, 50), { childList: true, subtree: true });

                // pass in the target node, as well as the observer options
                observer.observe(element, { childList: true, subtree: true });

                $element.data('observer', observer);
            } catch (e) {}
        });
    };

    UI.init = function (root) {

        root = root || document;

        UI.domObservers.forEach(function (fn) {
            fn(root);
        });
    };

    UI.on('domready.uk.dom', function () {

        UI.init();

        if (UI.domready) UI.Utils.checkDisplay();
    });

    document.addEventListener('DOMContentLoaded', function () {

        var domReady = function domReady() {

            UI.$body = UI.$('body');

            UI.trigger('beforeready.uk.dom');

            UI.component.bootComponents();

            // custom scroll observer
            var rafToken = requestAnimationFrame(function () {

                var memory = { dir: { x: 0, y: 0 }, x: window.pageXOffset, y: window.pageYOffset };

                var fn = function fn() {
                    // reading this (window.page[X|Y]Offset) causes a full page recalc of the layout in Chrome,
                    // so we only want to do this once
                    var wpxo = window.pageXOffset;
                    var wpyo = window.pageYOffset;

                    // Did the scroll position change since the last time we were here?
                    if (memory.x != wpxo || memory.y != wpyo) {

                        // Set the direction of the scroll and store the new position
                        if (wpxo != memory.x) {
                            memory.dir.x = wpxo > memory.x ? 1 : -1;
                        } else {
                            memory.dir.x = 0;
                        }
                        if (wpyo != memory.y) {
                            memory.dir.y = wpyo > memory.y ? 1 : -1;
                        } else {
                            memory.dir.y = 0;
                        }

                        memory.x = wpxo;
                        memory.y = wpyo;

                        // Trigger the scroll event, this could probably be sent using memory.clone() but this is
                        // more explicit and easier to see exactly what is being sent in the event.
                        UI.$doc.trigger('scrolling.uk.document', [{
                            dir: { x: memory.dir.x, y: memory.dir.y }, x: wpxo, y: wpyo
                        }]);
                    }

                    cancelAnimationFrame(rafToken);
                    rafToken = requestAnimationFrame(fn);
                };

                if (UI.support.touch) {
                    UI.$html.on('touchmove touchend MSPointerMove MSPointerUp pointermove pointerup', fn);
                }

                if (memory.x || memory.y) fn();

                return fn;
            }());

            // run component init functions on dom
            UI.trigger('domready.uk.dom');

            if (UI.support.touch) {

                // remove css hover rules for touch devices
                // UI.Utils.removeCssRules(/\.uk-(?!navbar).*:hover/);

                // viewport unit fix for uk-height-viewport - should be fixed in iOS 8
                if (navigator.userAgent.match(/(iPad|iPhone|iPod)/g)) {

                    UI.$win.on('load orientationchange resize', UI.Utils.debounce(function () {

                        var fn = function fn() {
                            $('.uk-height-viewport').css('height', window.innerHeight);
                            return fn;
                        };

                        return fn();
                    }(), 100));
                }
            }

            UI.trigger('afterready.uk.dom');

            // mark that domready is left behind
            UI.domready = true;

            // auto init js components
            if (UI.support.mutationobserver) {

                var initFn = UI.Utils.debounce(function () {
                    requestAnimationFrame(function () {
                        UI.init(document.body);
                    });
                }, 10);

                new UI.support.mutationobserver(function (mutations) {

                    var init = false;

                    mutations.every(function (mutation) {

                        if (mutation.type != 'childList') return true;

                        for (var i = 0, node; i < mutation.addedNodes.length; ++i) {

                            node = mutation.addedNodes[i];

                            if (node.outerHTML && node.outerHTML.indexOf('data-uk-') !== -1) {
                                return (init = true) && false;
                            }
                        }
                        return true;
                    });

                    if (init) initFn();
                }).observe(document.body, { childList: true, subtree: true });
            }
        };

        if (document.readyState == 'complete' || document.readyState == 'interactive') {
            setTimeout(domReady);
        }

        return domReady;
    }());

    // add touch identifier class
    UI.$html.addClass(UI.support.touch ? 'uk-touch' : 'uk-notouch');

    // add uk-hover class on tap to support overlays on touch devices
    if (UI.support.touch) {

        var hoverset = false,
            exclude,
            hovercls = 'uk-hover',
            selector = '.uk-overlay, .uk-overlay-hover, .uk-overlay-toggle, .uk-animation-hover, .uk-has-hover';

        UI.$html.on('mouseenter touchstart MSPointerDown pointerdown', selector, function () {

            if (hoverset) $('.' + hovercls).removeClass(hovercls);

            hoverset = $(this).addClass(hovercls);
        }).on('mouseleave touchend MSPointerUp pointerup', function (e) {

            exclude = $(e.target).parents(selector);

            if (hoverset) {
                hoverset.not(exclude).removeClass(hovercls);
            }
        });
    }

    return UI;
});

//  Based on Zeptos touch.js
//  https://raw.github.com/madrobby/zepto/master/src/touch.js
//  Zepto.js may be freely distributed under the MIT license.

;(function ($) {

    if ($.fn.swipeLeft) {
        return;
    }

    var touch = {},
        touchTimeout,
        tapTimeout,
        swipeTimeout,
        longTapTimeout,
        longTapDelay = 750,
        gesture;
    var hasTouchEvents = 'ontouchstart' in window,
        hasPointerEvents = window.PointerEvent,
        hasTouch = hasTouchEvents || window.DocumentTouch && document instanceof DocumentTouch || navigator.msPointerEnabled && navigator.msMaxTouchPoints > 0 // IE 10
    || navigator.pointerEnabled && navigator.maxTouchPoints > 0; // IE >=11

    function swipeDirection(x1, x2, y1, y2) {
        return Math.abs(x1 - x2) >= Math.abs(y1 - y2) ? x1 - x2 > 0 ? 'Left' : 'Right' : y1 - y2 > 0 ? 'Up' : 'Down';
    }

    function longTap() {
        longTapTimeout = null;
        if (touch.last) {
            if (touch.el !== undefined) touch.el.trigger('longTap');
            touch = {};
        }
    }

    function cancelLongTap() {
        if (longTapTimeout) clearTimeout(longTapTimeout);
        longTapTimeout = null;
    }

    function cancelAll() {
        if (touchTimeout) clearTimeout(touchTimeout);
        if (tapTimeout) clearTimeout(tapTimeout);
        if (swipeTimeout) clearTimeout(swipeTimeout);
        if (longTapTimeout) clearTimeout(longTapTimeout);
        touchTimeout = tapTimeout = swipeTimeout = longTapTimeout = null;
        touch = {};
    }

    function isPrimaryTouch(event) {
        return event.pointerType == event.MSPOINTER_TYPE_TOUCH && event.isPrimary;
    }

    $(function () {
        var now,
            delta,
            deltaX = 0,
            deltaY = 0,
            firstTouch;

        if ('MSGesture' in window) {
            gesture = new MSGesture();
            gesture.target = document.body;
        }

        $(document).on('MSGestureEnd gestureend', function (e) {

            var swipeDirectionFromVelocity = e.originalEvent.velocityX > 1 ? 'Right' : e.originalEvent.velocityX < -1 ? 'Left' : e.originalEvent.velocityY > 1 ? 'Down' : e.originalEvent.velocityY < -1 ? 'Up' : null;

            if (swipeDirectionFromVelocity && touch.el !== undefined) {
                touch.el.trigger('swipe');
                touch.el.trigger('swipe' + swipeDirectionFromVelocity);
            }
        })
        // MSPointerDown: for IE10
        // pointerdown: for IE11
        .on('touchstart MSPointerDown pointerdown', function (e) {

            if (e.type == 'MSPointerDown' && !isPrimaryTouch(e.originalEvent)) return;

            firstTouch = e.type == 'MSPointerDown' || e.type == 'pointerdown' ? e : e.originalEvent.touches[0];

            now = Date.now();
            delta = now - (touch.last || now);
            touch.el = $('tagName' in firstTouch.target ? firstTouch.target : firstTouch.target.parentNode);

            if (touchTimeout) clearTimeout(touchTimeout);

            touch.x1 = firstTouch.pageX;
            touch.y1 = firstTouch.pageY;

            if (delta > 0 && delta <= 250) touch.isDoubleTap = true;

            touch.last = now;
            longTapTimeout = setTimeout(longTap, longTapDelay);

            // adds the current touch contact for IE gesture recognition
            if (e.originalEvent && e.originalEvent.pointerId && gesture && (e.type == 'MSPointerDown' || e.type == 'pointerdown' || e.type == 'touchstart')) {
                gesture.addPointer(e.originalEvent.pointerId);
            }
        })
        // MSPointerMove: for IE10
        // pointermove: for IE11
        .on('touchmove MSPointerMove pointermove', function (e) {

            if (e.type == 'MSPointerMove' && !isPrimaryTouch(e.originalEvent)) return;

            firstTouch = e.type == 'MSPointerMove' || e.type == 'pointermove' ? e : e.originalEvent.touches[0];

            cancelLongTap();
            touch.x2 = firstTouch.pageX;
            touch.y2 = firstTouch.pageY;

            deltaX += Math.abs(touch.x1 - touch.x2);
            deltaY += Math.abs(touch.y1 - touch.y2);
        })
        // MSPointerUp: for IE10
        // pointerup: for IE11
        .on('touchend MSPointerUp pointerup', function (e) {

            if (e.type == 'MSPointerUp' && !isPrimaryTouch(e.originalEvent)) return;

            cancelLongTap();

            // swipe
            if (touch.x2 && Math.abs(touch.x1 - touch.x2) > 30 || touch.y2 && Math.abs(touch.y1 - touch.y2) > 30) {

                swipeTimeout = setTimeout(function () {
                    if (touch.el !== undefined) {
                        touch.el.trigger('swipe');
                        touch.el.trigger('swipe' + swipeDirection(touch.x1, touch.x2, touch.y1, touch.y2));
                    }
                    touch = {};
                }, 0);

                // normal tap
            } else if ('last' in touch) {

                // don't fire tap when delta position changed by more than 30 pixels,
                // for instance when moving to a point and back to origin
                if (isNaN(deltaX) || deltaX < 30 && deltaY < 30) {
                    // delay by one tick so we can cancel the 'tap' event if 'scroll' fires
                    // ('tap' fires before 'scroll')
                    tapTimeout = setTimeout(function () {

                        // trigger universal 'tap' with the option to cancelTouch()
                        // (cancelTouch cancels processing of single vs double taps for faster 'tap' response)
                        var event = $.Event('tap');
                        event.cancelTouch = cancelAll;
                        if (touch.el !== undefined) touch.el.trigger(event);

                        // trigger double tap immediately
                        if (touch.isDoubleTap) {
                            if (touch.el !== undefined) touch.el.trigger('doubleTap');
                            touch = {};
                        }

                        // trigger single tap after 250ms of inactivity
                        else {
                                touchTimeout = setTimeout(function () {
                                    touchTimeout = null;
                                    if (touch.el !== undefined) touch.el.trigger('singleTap');
                                    touch = {};
                                }, 250);
                            }
                    }, 0);
                } else {
                    touch = {};
                }
                deltaX = deltaY = 0;
            }
        })
        // when the browser window loses focus,
        // for example when a modal dialog is shown,
        // cancel all ongoing events
        .on('touchcancel MSPointerCancel pointercancel', function (e) {

            // Ignore pointercancel if the event supports touch events, to prevent pointercancel in swipe gesture
            if (e.type == 'touchcancel' && hasTouchEvents && hasTouch || !hasTouchEvents && e.type == 'pointercancel' && hasPointerEvents) {
                cancelAll();
            }
        });

        // scrolling the window indicates intention of the user
        // to scroll, not tap or swipe, so cancel all ongoing events
        $(window).on('scroll', cancelAll);
    });

    ['swipe', 'swipeLeft', 'swipeRight', 'swipeUp', 'swipeDown', 'doubleTap', 'tap', 'singleTap', 'longTap'].forEach(function (eventName) {
        $.fn[eventName] = function (callback) {
            return $(this).on(eventName, callback);
        };
    });
})(jQuery);

(function (UI) {

    "use strict";

    var stacks = [];

    UI.component('stackMargin', {

        defaults: {
            cls: 'uk-margin-small-top',
            rowfirst: false,
            observe: false
        },

        boot: function boot() {

            // init code
            UI.ready(function (context) {

                UI.$('[data-uk-margin]', context).each(function () {

                    var ele = UI.$(this);

                    if (!ele.data('stackMargin')) {
                        UI.stackMargin(ele, UI.Utils.options(ele.attr('data-uk-margin')));
                    }
                });
            });
        },

        init: function init() {

            var $this = this;

            UI.$win.on('resize orientationchange', function () {

                var fn = function fn() {
                    $this.process();
                };

                UI.$(function () {
                    fn();
                    UI.$win.on('load', fn);
                });

                return UI.Utils.debounce(fn, 20);
            }());

            this.on('display.uk.check', function (e) {
                if (this.element.is(':visible')) this.process();
            }.bind(this));

            if (this.options.observe) {

                UI.domObserve(this.element, function (e) {
                    if ($this.element.is(':visible')) $this.process();
                });
            }

            stacks.push(this);
        },

        process: function process() {

            var $this = this,
                columns = this.element.children();

            UI.Utils.stackMargin(columns, this.options);

            if (!this.options.rowfirst || !columns.length) {
                return this;
            }

            // Mark first column elements
            var group = {},
                minleft = false;

            columns.removeClass(this.options.rowfirst).each(function (offset, $ele) {

                $ele = UI.$(this);

                if (this.style.display != 'none') {
                    offset = $ele.offset().left;
                    ((group[offset] = group[offset] || []) && group[offset]).push(this);
                    minleft = minleft === false ? offset : Math.min(minleft, offset);
                }
            });

            UI.$(group[minleft]).addClass(this.options.rowfirst);

            return this;
        }

    });

    // responsive element e.g. iframes

    (function () {

        var elements = [],
            check = function check(ele) {

            if (!ele.is(':visible')) return;

            var width = ele.parent().width(),
                iwidth = ele.data('width'),
                ratio = width / iwidth,
                height = Math.floor(ratio * ele.data('height'));

            ele.css({ height: width < iwidth ? height : ele.data('height') });
        };

        UI.component('responsiveElement', {

            defaults: {},

            boot: function boot() {

                // init code
                UI.ready(function (context) {

                    UI.$('iframe.uk-responsive-width, [data-uk-responsive]', context).each(function () {

                        var ele = UI.$(this),
                            obj;

                        if (!ele.data('responsiveElement')) {
                            obj = UI.responsiveElement(ele, {});
                        }
                    });
                });
            },

            init: function init() {

                var ele = this.element;

                if (ele.attr('width') && ele.attr('height')) {

                    ele.data({
                        width: ele.attr('width'),
                        height: ele.attr('height')
                    }).on('display.uk.check', function () {
                        check(ele);
                    });

                    check(ele);

                    elements.push(ele);
                }
            }
        });

        UI.$win.on('resize load', UI.Utils.debounce(function () {

            elements.forEach(function (ele) {
                check(ele);
            });
        }, 15));
    })();

    // helper

    UI.Utils.stackMargin = function (elements, options) {

        options = UI.$.extend({
            cls: 'uk-margin-small-top'
        }, options);

        elements = UI.$(elements).removeClass(options.cls);

        var min = false;

        elements.each(function (offset, height, pos, $ele) {

            $ele = UI.$(this);

            if ($ele.css('display') != 'none') {

                offset = $ele.offset();
                height = $ele.outerHeight();
                pos = offset.top + height;

                $ele.data({
                    ukMarginPos: pos,
                    ukMarginTop: offset.top
                });

                if (min === false || offset.top < min.top) {

                    min = {
                        top: offset.top,
                        left: offset.left,
                        pos: pos
                    };
                }
            }
        }).each(function ($ele) {

            $ele = UI.$(this);

            if ($ele.css('display') != 'none' && $ele.data('ukMarginTop') > min.top && $ele.data('ukMarginPos') > min.pos) {
                $ele.addClass(options.cls);
            }
        });
    };

    UI.Utils.matchHeights = function (elements, options) {

        elements = UI.$(elements).css('min-height', '');
        options = UI.$.extend({ row: true }, options);

        var matchHeights = function matchHeights(group) {

            if (group.length < 2) return;

            var max = 0;

            group.each(function () {
                max = Math.max(max, UI.$(this).outerHeight());
            }).each(function () {

                var element = UI.$(this),
                    height = max - (element.css('box-sizing') == 'border-box' ? 0 : element.outerHeight() - element.height());

                element.css('min-height', height + 'px');
            });
        };

        if (options.row) {

            elements.first().width(); // force redraw

            setTimeout(function () {

                var lastoffset = false,
                    group = [];

                elements.each(function () {

                    var ele = UI.$(this),
                        offset = ele.offset().top;

                    if (offset != lastoffset && group.length) {

                        matchHeights(UI.$(group));
                        group = [];
                        offset = ele.offset().top;
                    }

                    group.push(ele);
                    lastoffset = offset;
                });

                if (group.length) {
                    matchHeights(UI.$(group));
                }
            }, 0);
        } else {
            matchHeights(elements);
        }
    };

    (function (cacheSvgs) {

        UI.Utils.inlineSvg = function (selector, root) {

            var images = UI.$(selector || 'img[src$=".svg"]', root || document).each(function () {

                var img = UI.$(this),
                    src = img.attr('src');

                if (!cacheSvgs[src]) {

                    var d = UI.$.Deferred();

                    UI.$.get(src, { nc: Math.random() }, function (data) {
                        d.resolve(UI.$(data).find('svg'));
                    });

                    cacheSvgs[src] = d.promise();
                }

                cacheSvgs[src].then(function (svg) {

                    var $svg = UI.$(svg).clone();

                    if (img.attr('id')) $svg.attr('id', img.attr('id'));
                    if (img.attr('class')) $svg.attr('class', img.attr('class'));
                    if (img.attr('style')) $svg.attr('style', img.attr('style'));

                    if (img.attr('width')) {
                        $svg.attr('width', img.attr('width'));
                        if (!img.attr('height')) $svg.removeAttr('height');
                    }

                    if (img.attr('height')) {
                        $svg.attr('height', img.attr('height'));
                        if (!img.attr('width')) $svg.removeAttr('width');
                    }

                    img.replaceWith($svg);
                });
            });
        };

        // init code
        UI.ready(function (context) {
            UI.Utils.inlineSvg('[data-uk-svg]', context);
        });
    })({});

    UI.Utils.getCssVar = function (name) {

        /* usage in css:  .var-name:before { content:"xyz" } */

        var val,
            doc = document.documentElement,
            element = doc.appendChild(document.createElement('div'));

        element.classList.add('var-' + name);

        try {
            val = JSON.parse(val = getComputedStyle(element, ':before').content.replace(/^["'](.*)["']$/, '$1'));
        } catch (e) {
            val = undefined;
        }

        doc.removeChild(element);

        return val;
    };
})(UIkit2);

(function (UI) {

    "use strict";

    UI.component('smoothScroll', {

        boot: function boot() {

            // init code
            UI.$html.on('click.smooth-scroll.uikit', '[data-uk-smooth-scroll]', function (e) {
                var ele = UI.$(this);

                if (!ele.data('smoothScroll')) {
                    var obj = UI.smoothScroll(ele, UI.Utils.options(ele.attr('data-uk-smooth-scroll')));
                    ele.trigger('click');
                }

                return false;
            });
        },

        init: function init() {

            var $this = this;

            this.on('click', function (e) {
                e.preventDefault();
                scrollToElement(UI.$(this.hash).length ? UI.$(this.hash) : UI.$('body'), $this.options);
            });
        }
    });

    function scrollToElement(ele, options) {

        options = UI.$.extend({
            duration: 1000,
            transition: 'easeOutExpo',
            offset: 0,
            complete: function complete() {}
        }, options);

        // get / set parameters
        var target = ele.offset().top - options.offset,
            docheight = UI.$doc.height(),
            winheight = window.innerHeight;

        if (target + winheight > docheight) {
            target = docheight - winheight;
        }

        // animate to target, fire callback when done
        UI.$('html,body').stop().animate({ scrollTop: target }, options.duration, options.transition).promise().done(options.complete);
    }

    UI.Utils.scrollToElement = scrollToElement;

    if (!UI.$.easing.easeOutExpo) {
        UI.$.easing.easeOutExpo = function (x, t, b, c, d) {
            return t == d ? b + c : c * (-Math.pow(2, -10 * t / d) + 1) + b;
        };
    }
})(UIkit2);

(function (UI) {

    "use strict";

    var $win = UI.$win,
        $doc = UI.$doc,
        scrollspies = [],
        checkScrollSpy = function checkScrollSpy() {
        for (var i = 0; i < scrollspies.length; i++) {
            window.requestAnimationFrame.apply(window, [scrollspies[i].check]);
        }
    };

    UI.component('scrollspy', {

        defaults: {
            target: false,
            cls: 'uk-scrollspy-inview',
            initcls: 'uk-scrollspy-init-inview',
            topoffset: 0,
            leftoffset: 0,
            repeat: false,
            delay: 0
        },

        boot: function boot() {

            // listen to scroll and resize
            $doc.on('scrolling.uk.document', checkScrollSpy);
            $win.on('load resize orientationchange', UI.Utils.debounce(checkScrollSpy, 50));

            // init code
            UI.ready(function (context) {

                UI.$('[data-uk-scrollspy]', context).each(function () {

                    var element = UI.$(this);

                    if (!element.data('scrollspy')) {
                        var obj = UI.scrollspy(element, UI.Utils.options(element.attr('data-uk-scrollspy')));
                    }
                });
            });
        },

        init: function init() {

            var $this = this,
                inviewstate,
                initinview,
                togglecls = this.options.cls.split(/,/),
                fn = function fn() {

                var elements = $this.options.target ? $this.element.find($this.options.target) : $this.element,
                    delayIdx = elements.length === 1 ? 1 : 0,
                    toggleclsIdx = 0;

                elements.each(function (idx) {

                    var element = UI.$(this),
                        inviewstate = element.data('inviewstate'),
                        inview = UI.Utils.isInView(element, $this.options),
                        toggle = element.attr('data-uk-scrollspy-cls') || togglecls[toggleclsIdx].trim();

                    if (inview && !inviewstate && !element.data('scrollspy-idle')) {

                        if (!initinview) {
                            element.addClass($this.options.initcls);
                            $this.offset = element.offset();
                            initinview = true;

                            element.trigger('init.uk.scrollspy');
                        }

                        element.data('scrollspy-idle', setTimeout(function () {

                            element.addClass('uk-scrollspy-inview').toggleClass(toggle).width();
                            element.trigger('inview.uk.scrollspy');

                            element.data('scrollspy-idle', false);
                            element.data('inviewstate', true);
                        }, $this.options.delay * delayIdx));

                        delayIdx++;
                    }

                    if (!inview && inviewstate && $this.options.repeat) {

                        if (element.data('scrollspy-idle')) {
                            clearTimeout(element.data('scrollspy-idle'));
                            element.data('scrollspy-idle', false);
                        }

                        element.removeClass('uk-scrollspy-inview').toggleClass(toggle);
                        element.data('inviewstate', false);

                        element.trigger('outview.uk.scrollspy');
                    }

                    toggleclsIdx = togglecls[toggleclsIdx + 1] ? toggleclsIdx + 1 : 0;
                });
            };

            fn();

            this.check = fn;

            scrollspies.push(this);
        }
    });

    var scrollspynavs = [],
        checkScrollSpyNavs = function checkScrollSpyNavs() {
        for (var i = 0; i < scrollspynavs.length; i++) {
            window.requestAnimationFrame.apply(window, [scrollspynavs[i].check]);
        }
    };

    UI.component('scrollspynav', {

        defaults: {
            cls: 'uk-active',
            closest: false,
            topoffset: 0,
            leftoffset: 0,
            smoothscroll: false
        },

        boot: function boot() {

            // listen to scroll and resize
            $doc.on('scrolling.uk.document', checkScrollSpyNavs);
            $win.on('resize orientationchange', UI.Utils.debounce(checkScrollSpyNavs, 50));

            // init code
            UI.ready(function (context) {

                UI.$('[data-uk-scrollspy-nav]', context).each(function () {

                    var element = UI.$(this);

                    if (!element.data('scrollspynav')) {
                        var obj = UI.scrollspynav(element, UI.Utils.options(element.attr('data-uk-scrollspy-nav')));
                    }
                });
            });
        },

        init: function init() {

            var ids = [],
                links = this.find("a[href^='#']").each(function () {
                if (this.getAttribute('href').trim() !== '#') ids.push(this.getAttribute('href'));
            }),
                targets = UI.$(ids.join(",")),
                clsActive = this.options.cls,
                clsClosest = this.options.closest || this.options.closest;

            var $this = this,
                inviews,
                fn = function fn() {

                inviews = [];

                for (var i = 0; i < targets.length; i++) {
                    if (UI.Utils.isInView(targets.eq(i), $this.options)) {
                        inviews.push(targets.eq(i));
                    }
                }

                if (inviews.length) {

                    var navitems,
                        scrollTop = $win.scrollTop(),
                        target = function () {
                        for (var i = 0; i < inviews.length; i++) {
                            if (inviews[i].offset().top - $this.options.topoffset >= scrollTop) {
                                return inviews[i];
                            }
                        }
                    }();

                    if (!target) return;

                    if ($this.options.closest) {
                        links.blur().closest(clsClosest).removeClass(clsActive);
                        navitems = links.filter("a[href='#" + target.attr('id') + "']").closest(clsClosest).addClass(clsActive);
                    } else {
                        navitems = links.removeClass(clsActive).filter("a[href='#" + target.attr("id") + "']").addClass(clsActive);
                    }

                    $this.element.trigger('inview.uk.scrollspynav', [target, navitems]);
                }
            };

            if (this.options.smoothscroll && UI.smoothScroll) {
                links.each(function () {
                    UI.smoothScroll(this, $this.options.smoothscroll);
                });
            }

            fn();

            this.element.data('scrollspynav', this);

            this.check = fn;
            scrollspynavs.push(this);
        }
    });
})(UIkit2);

(function (UI) {

    "use strict";

    var toggles = [];

    UI.component('toggle', {

        defaults: {
            target: false,
            cls: 'uk-hidden',
            animation: false,
            duration: 200
        },

        boot: function boot() {

            // init code
            UI.ready(function (context) {

                UI.$('[data-uk-toggle]', context).each(function () {
                    var ele = UI.$(this);

                    if (!ele.data('toggle')) {
                        var obj = UI.toggle(ele, UI.Utils.options(ele.attr('data-uk-toggle')));
                    }
                });

                setTimeout(function () {

                    toggles.forEach(function (toggle) {
                        toggle.getToggles();
                    });
                }, 0);
            });
        },

        init: function init() {

            var $this = this;

            this.aria = this.options.cls.indexOf('uk-hidden') !== -1;

            this.on('click', function (e) {

                if ($this.element.is('a[href="#"]')) {
                    e.preventDefault();
                }

                $this.toggle();
            });

            toggles.push(this);
        },

        toggle: function toggle() {

            this.getToggles();

            if (!this.totoggle.length) return;

            if (this.options.animation && UI.support.animation) {

                var $this = this,
                    animations = this.options.animation.split(',');

                if (animations.length == 1) {
                    animations[1] = animations[0];
                }

                animations[0] = animations[0].trim();
                animations[1] = animations[1].trim();

                this.totoggle.css('animation-duration', this.options.duration + 'ms');

                this.totoggle.each(function () {

                    var ele = UI.$(this);

                    if (ele.hasClass($this.options.cls)) {

                        ele.toggleClass($this.options.cls);

                        UI.Utils.animate(ele, animations[0]).then(function () {
                            ele.css('animation-duration', '');
                            UI.Utils.checkDisplay(ele);
                        });
                    } else {

                        UI.Utils.animate(this, animations[1] + ' uk-animation-reverse').then(function () {
                            ele.toggleClass($this.options.cls).css('animation-duration', '');
                            UI.Utils.checkDisplay(ele);
                        });
                    }
                });
            } else {
                this.totoggle.toggleClass(this.options.cls);
                UI.Utils.checkDisplay(this.totoggle);
            }

            this.updateAria();
        },

        getToggles: function getToggles() {
            this.totoggle = this.options.target ? UI.$(this.options.target) : [];
            this.updateAria();
        },

        updateAria: function updateAria() {
            if (this.aria && this.totoggle.length) {
                this.totoggle.not('[aria-hidden]').each(function () {
                    UI.$(this).attr('aria-hidden', UI.$(this).hasClass('uk-hidden'));
                });
            }
        }
    });
})(UIkit2);

(function (UI) {

    "use strict";

    UI.component('alert', {

        defaults: {
            fade: true,
            duration: 200,
            trigger: '.uk-alert-close'
        },

        boot: function boot() {

            // init code
            UI.$html.on('click.alert.uikit', '[data-uk-alert]', function (e) {

                var ele = UI.$(this);

                if (!ele.data('alert')) {

                    var alert = UI.alert(ele, UI.Utils.options(ele.attr('data-uk-alert')));

                    if (UI.$(e.target).is(alert.options.trigger)) {
                        e.preventDefault();
                        alert.close();
                    }
                }
            });
        },

        init: function init() {

            var $this = this;

            this.on('click', this.options.trigger, function (e) {
                e.preventDefault();
                $this.close();
            });
        },

        close: function close() {

            var element = this.trigger('close.uk.alert'),
                removeElement = function () {
                this.trigger('closed.uk.alert').remove();
            }.bind(this);

            if (this.options.fade) {
                element.css('overflow', 'hidden').css("max-height", element.height()).animate({
                    height: 0,
                    opacity: 0,
                    paddingTop: 0,
                    paddingBottom: 0,
                    marginTop: 0,
                    marginBottom: 0
                }, this.options.duration, removeElement);
            } else {
                removeElement();
            }
        }

    });
})(UIkit2);

(function (UI) {

    "use strict";

    UI.component('buttonRadio', {

        defaults: {
            activeClass: 'uk-active',
            target: '.uk-button'
        },

        boot: function boot() {

            // init code
            UI.$html.on('click.buttonradio.uikit', '[data-uk-button-radio]', function (e) {

                var ele = UI.$(this);

                if (!ele.data('buttonRadio')) {

                    var obj = UI.buttonRadio(ele, UI.Utils.options(ele.attr('data-uk-button-radio'))),
                        target = UI.$(e.target);

                    if (target.is(obj.options.target)) {
                        target.trigger('click');
                    }
                }
            });
        },

        init: function init() {

            var $this = this;

            // Init ARIA
            this.find($this.options.target).attr('aria-checked', 'false').filter('.' + $this.options.activeClass).attr('aria-checked', 'true');

            this.on('click', this.options.target, function (e) {

                var ele = UI.$(this);

                if (ele.is('a[href="#"]')) e.preventDefault();

                $this.find($this.options.target).not(ele).removeClass($this.options.activeClass).blur();
                ele.addClass($this.options.activeClass);

                // Update ARIA
                $this.find($this.options.target).not(ele).attr('aria-checked', 'false');
                ele.attr('aria-checked', 'true');

                $this.trigger('change.uk.button', [ele]);
            });
        },

        getSelected: function getSelected() {
            return this.find('.' + this.options.activeClass);
        }
    });

    UI.component('buttonCheckbox', {

        defaults: {
            activeClass: 'uk-active',
            target: '.uk-button'
        },

        boot: function boot() {

            UI.$html.on('click.buttoncheckbox.uikit', '[data-uk-button-checkbox]', function (e) {
                var ele = UI.$(this);

                if (!ele.data('buttonCheckbox')) {

                    var obj = UI.buttonCheckbox(ele, UI.Utils.options(ele.attr('data-uk-button-checkbox'))),
                        target = UI.$(e.target);

                    if (target.is(obj.options.target)) {
                        target.trigger('click');
                    }
                }
            });
        },

        init: function init() {

            var $this = this;

            // Init ARIA
            this.find($this.options.target).attr('aria-checked', 'false').filter('.' + $this.options.activeClass).attr('aria-checked', 'true');

            this.on('click', this.options.target, function (e) {
                var ele = UI.$(this);

                if (ele.is('a[href="#"]')) e.preventDefault();

                ele.toggleClass($this.options.activeClass).blur();

                // Update ARIA
                ele.attr('aria-checked', ele.hasClass($this.options.activeClass));

                $this.trigger('change.uk.button', [ele]);
            });
        },

        getSelected: function getSelected() {
            return this.find('.' + this.options.activeClass);
        }
    });

    UI.component('button', {

        defaults: {},

        boot: function boot() {

            UI.$html.on('click.button.uikit', '[data-uk-button]', function (e) {
                var ele = UI.$(this);

                if (!ele.data('button')) {

                    var obj = UI.button(ele, UI.Utils.options(ele.attr('data-uk-button')));
                    ele.trigger('click');
                }
            });
        },

        init: function init() {

            var $this = this;

            // Init ARIA
            this.element.attr('aria-pressed', this.element.hasClass("uk-active"));

            this.on('click', function (e) {

                if ($this.element.is('a[href="#"]')) e.preventDefault();

                $this.toggle();
                $this.trigger('change.uk.button', [$this.element.blur().hasClass('uk-active')]);
            });
        },

        toggle: function toggle() {
            this.element.toggleClass('uk-active');

            // Update ARIA
            this.element.attr('aria-pressed', this.element.hasClass('uk-active'));
        }
    });
})(UIkit2);

(function (UI) {

    "use strict";

    var active = false,
        hoverIdle,
        flips = {
        x: {
            'bottom-left': 'bottom-right',
            'bottom-right': 'bottom-left',
            'bottom-center': 'bottom-center',
            'top-left': 'top-right',
            'top-right': 'top-left',
            'top-center': 'top-center',
            'left-top': 'right-top',
            'left-bottom': 'right-bottom',
            'left-center': 'right-center',
            'right-top': 'left-top',
            'right-bottom': 'left-bottom',
            'right-center': 'left-center'
        },
        y: {
            'bottom-left': 'top-left',
            'bottom-right': 'top-right',
            'bottom-center': 'top-center',
            'top-left': 'bottom-left',
            'top-right': 'bottom-right',
            'top-center': 'bottom-center',
            'left-top': 'left-bottom',
            'left-bottom': 'left-top',
            'left-center': 'left-center',
            'right-top': 'right-bottom',
            'right-bottom': 'right-top',
            'right-center': 'right-center'
        },
        xy: {
            'bottom-left': 'top-right',
            'bottom-right': 'top-left',
            'bottom-center': 'top-center',
            'top-left': 'bottom-right',
            'top-right': 'bottom-left',
            'top-center': 'bottom-center',
            'left-top': 'right-bottom',
            'left-bottom': 'right-top',
            'left-center': 'right-center',
            'right-top': 'left-bottom',
            'right-bottom': 'left-top',
            'right-center': 'left-center'
        }
    };

    UI.component('dropdown', {

        defaults: {
            mode: 'hover',
            pos: 'bottom-left',
            offset: 0,
            remaintime: 800,
            justify: false,
            boundary: UI.$win,
            delay: 0,
            dropdownSelector: '.uk-dropdown,.uk-dropdown-blank',
            hoverDelayIdle: 250,
            preventflip: false
        },

        remainIdle: false,

        boot: function boot() {

            var triggerevent = UI.support.touch ? 'click' : 'mouseenter';

            // init code
            UI.$html.on(triggerevent + '.dropdown.uikit focus pointerdown', '[data-uk-dropdown]', function (e) {

                var ele = UI.$(this);

                if (!ele.data('dropdown')) {

                    var dropdown = UI.dropdown(ele, UI.Utils.options(ele.attr('data-uk-dropdown')));

                    if (e.type == 'click' || e.type == 'mouseenter' && dropdown.options.mode == 'hover') {
                        dropdown.element.trigger(triggerevent);
                    }

                    if (dropdown.dropdown.length) {
                        e.preventDefault();
                    }
                }
            });
        },

        init: function init() {

            var $this = this;

            this.dropdown = this.find(this.options.dropdownSelector);
            this.offsetParent = this.dropdown.parents().filter(function () {
                return UI.$.inArray(UI.$(this).css('position'), ['relative', 'fixed', 'absolute']) !== -1;
            }).slice(0, 1);

            if (!this.offsetParent.length) {
                this.offsetParent = this.element;
            }

            this.centered = this.dropdown.hasClass('uk-dropdown-center');
            this.justified = this.options.justify ? UI.$(this.options.justify) : false;

            this.boundary = UI.$(this.options.boundary);

            if (!this.boundary.length) {
                this.boundary = UI.$win;
            }

            // legacy DEPRECATED!
            if (this.dropdown.hasClass('uk-dropdown-up')) {
                this.options.pos = 'top-left';
            }
            if (this.dropdown.hasClass('uk-dropdown-flip')) {
                this.options.pos = this.options.pos.replace('left', 'right');
            }
            if (this.dropdown.hasClass('uk-dropdown-center')) {
                this.options.pos = this.options.pos.replace(/(left|right)/, 'center');
            }
            //-- end legacy

            // Init ARIA
            this.element.attr('aria-haspopup', 'true');
            this.element.attr('aria-expanded', this.element.hasClass('uk-open'));
            this.dropdown.attr('aria-hidden', 'true');

            if (this.options.mode == 'click' || UI.support.touch) {

                this.on('click.uk.dropdown', function (e) {

                    var $target = UI.$(e.target);

                    if (!$target.parents($this.options.dropdownSelector).length) {

                        if ($target.is("a[href='#']") || $target.parent().is("a[href='#']") || $this.dropdown.length && !$this.dropdown.is(':visible')) {
                            e.preventDefault();
                        }

                        $target.blur();
                    }

                    if (!$this.element.hasClass('uk-open')) {

                        $this.show();
                    } else {

                        if (!$this.dropdown.find(e.target).length || $target.is('.uk-dropdown-close') || $target.parents('.uk-dropdown-close').length) {
                            $this.hide();
                        }
                    }
                });
            } else {

                this.on('mouseenter', function (e) {

                    $this.trigger('pointerenter.uk.dropdown', [$this]);

                    if ($this.remainIdle) {
                        clearTimeout($this.remainIdle);
                    }

                    if (hoverIdle) {
                        clearTimeout(hoverIdle);
                    }

                    if (active && active == $this) {
                        return;
                    }

                    // pseudo manuAim
                    if (active && active != $this) {

                        hoverIdle = setTimeout(function () {
                            hoverIdle = setTimeout($this.show.bind($this), $this.options.delay);
                        }, $this.options.hoverDelayIdle);
                    } else {

                        hoverIdle = setTimeout($this.show.bind($this), $this.options.delay);
                    }
                }).on('mouseleave', function () {

                    if (hoverIdle) {
                        clearTimeout(hoverIdle);
                    }

                    $this.remainIdle = setTimeout(function () {
                        if (active && active == $this) $this.hide();
                    }, $this.options.remaintime);

                    $this.trigger('pointerleave.uk.dropdown', [$this]);
                }).on('click', function (e) {

                    var $target = UI.$(e.target);

                    if ($this.remainIdle) {
                        clearTimeout($this.remainIdle);
                    }

                    if (active && active == $this) {
                        if (!$this.dropdown.find(e.target).length || $target.is('.uk-dropdown-close') || $target.parents('.uk-dropdown-close').length) {
                            $this.hide();
                        }
                        return;
                    }

                    if ($target.is("a[href='#']") || $target.parent().is("a[href='#']")) {
                        e.preventDefault();
                    }

                    $this.show();
                });
            }
        },

        show: function show() {

            UI.$html.off('click.outer.dropdown');

            if (active && active != this) {
                active.hide(true);
            }

            if (hoverIdle) {
                clearTimeout(hoverIdle);
            }

            this.trigger('beforeshow.uk.dropdown', [this]);

            this.checkDimensions();
            this.element.addClass('uk-open');

            // Update ARIA
            this.element.attr('aria-expanded', 'true');
            this.dropdown.attr('aria-hidden', 'false');

            this.trigger('show.uk.dropdown', [this]);

            UI.Utils.checkDisplay(this.dropdown, true);
            UI.Utils.focus(this.dropdown);
            active = this;

            this.registerOuterClick();
        },

        hide: function hide(force) {

            this.trigger('beforehide.uk.dropdown', [this, force]);

            this.element.removeClass('uk-open');

            if (this.remainIdle) {
                clearTimeout(this.remainIdle);
            }

            this.remainIdle = false;

            // Update ARIA
            this.element.attr('aria-expanded', 'false');
            this.dropdown.attr('aria-hidden', 'true');

            this.trigger('hide.uk.dropdown', [this, force]);

            if (active == this) active = false;
        },

        registerOuterClick: function registerOuterClick() {

            var $this = this;

            UI.$html.off('click.outer.dropdown');

            setTimeout(function () {

                UI.$html.on('click.outer.dropdown', function (e) {

                    if (hoverIdle) {
                        clearTimeout(hoverIdle);
                    }

                    var $target = UI.$(e.target);

                    if (active == $this && !$this.element.find(e.target).length) {
                        $this.hide(true);
                        UI.$html.off('click.outer.dropdown');
                    }
                });
            }, 10);
        },

        checkDimensions: function checkDimensions() {

            if (!this.dropdown.length) return;

            // reset
            this.dropdown.removeClass('uk-dropdown-top uk-dropdown-bottom uk-dropdown-left uk-dropdown-right uk-dropdown-stack uk-dropdown-autoflip').css({
                topLeft: '',
                left: '',
                marginLeft: '',
                marginRight: ''
            });

            if (this.justified && this.justified.length) {
                this.dropdown.css('min-width', '');
            }

            var $this = this,
                pos = UI.$.extend({}, this.offsetParent.offset(), { width: this.offsetParent[0].offsetWidth, height: this.offsetParent[0].offsetHeight }),
                posoffset = this.options.offset,
                dropdown = this.dropdown,
                offset = dropdown.show().offset() || { left: 0, top: 0 },
                width = dropdown.outerWidth(),
                height = dropdown.outerHeight(),
                boundarywidth = this.boundary.width(),
                boundaryoffset = this.boundary[0] !== window && this.boundary.offset() ? this.boundary.offset() : { top: 0, left: 0 },
                dpos = this.options.pos;

            var variants = {
                'bottom-left': { top: 0 + pos.height + posoffset, left: 0 },
                'bottom-right': { top: 0 + pos.height + posoffset, left: 0 + pos.width - width },
                'bottom-center': { top: 0 + pos.height + posoffset, left: 0 + pos.width / 2 - width / 2 },
                'top-left': { top: 0 - height - posoffset, left: 0 },
                'top-right': { top: 0 - height - posoffset, left: 0 + pos.width - width },
                'top-center': { top: 0 - height - posoffset, left: 0 + pos.width / 2 - width / 2 },
                'left-top': { top: 0, left: 0 - width - posoffset },
                'left-bottom': { top: 0 + pos.height - height, left: 0 - width - posoffset },
                'left-center': { top: 0 + pos.height / 2 - height / 2, left: 0 - width - posoffset },
                'right-top': { top: 0, left: 0 + pos.width + posoffset },
                'right-bottom': { top: 0 + pos.height - height, left: 0 + pos.width + posoffset },
                'right-center': { top: 0 + pos.height / 2 - height / 2, left: 0 + pos.width + posoffset }
            },
                css = {},
                pp;

            pp = dpos.split('-');
            css = variants[dpos] ? variants[dpos] : variants['bottom-left'];

            // justify dropdown
            if (this.justified && this.justified.length) {
                justify(dropdown.css({ left: 0 }), this.justified, boundarywidth);
            } else {

                if (this.options.preventflip !== true) {

                    var fdpos;

                    switch (this.checkBoundary(pos.left + css.left, pos.top + css.top, width, height, boundarywidth)) {
                        case "x":
                            if (this.options.preventflip !== 'x') fdpos = flips['x'][dpos] || 'right-top';
                            break;
                        case "y":
                            if (this.options.preventflip !== 'y') fdpos = flips['y'][dpos] || 'top-left';
                            break;
                        case "xy":
                            if (!this.options.preventflip) fdpos = flips['xy'][dpos] || 'right-bottom';
                            break;
                    }

                    if (fdpos) {

                        pp = fdpos.split('-');
                        css = variants[fdpos] ? variants[fdpos] : variants['bottom-left'];
                        dropdown.addClass('uk-dropdown-autoflip');

                        // check flipped
                        if (this.checkBoundary(pos.left + css.left, pos.top + css.top, width, height, boundarywidth)) {
                            pp = dpos.split('-');
                            css = variants[dpos] ? variants[dpos] : variants['bottom-left'];
                        }
                    }
                }
            }

            if (width > boundarywidth) {
                dropdown.addClass('uk-dropdown-stack');
                this.trigger('stack.uk.dropdown', [this]);
            }

            dropdown.css(css).css('display', '').addClass('uk-dropdown-' + pp[0]);
        },

        checkBoundary: function checkBoundary(left, top, width, height, boundarywidth) {

            var axis = "";

            if (left < 0 || left - UI.$win.scrollLeft() + width > boundarywidth) {
                axis += "x";
            }

            if (top - UI.$win.scrollTop() < 0 || top - UI.$win.scrollTop() + height > window.innerHeight) {
                axis += "y";
            }

            return axis;
        }
    });

    UI.component('dropdownOverlay', {

        defaults: {
            justify: false,
            cls: '',
            duration: 200
        },

        boot: function boot() {

            // init code
            UI.ready(function (context) {

                UI.$('[data-uk-dropdown-overlay]', context).each(function () {
                    var ele = UI.$(this);

                    if (!ele.data('dropdownOverlay')) {
                        UI.dropdownOverlay(ele, UI.Utils.options(ele.attr('data-uk-dropdown-overlay')));
                    }
                });
            });
        },

        init: function init() {

            var $this = this;

            this.justified = this.options.justify ? UI.$(this.options.justify) : false;
            this.overlay = this.element.find('uk-dropdown-overlay');

            if (!this.overlay.length) {
                this.overlay = UI.$('<div class="uk-dropdown-overlay"></div>').appendTo(this.element);
            }

            this.overlay.addClass(this.options.cls);

            this.on({

                'beforeshow.uk.dropdown': function beforeshowUkDropdown(e, dropdown) {
                    $this.dropdown = dropdown;

                    if ($this.justified && $this.justified.length) {
                        justify($this.overlay.css({ display: 'block', marginLeft: '', marginRight: '' }), $this.justified, $this.justified.outerWidth());
                    }
                },

                'show.uk.dropdown': function showUkDropdown(e, dropdown) {

                    var h = $this.dropdown.dropdown.outerHeight(true);

                    $this.dropdown.element.removeClass('uk-open');

                    $this.overlay.stop().css('display', 'block').animate({ height: h }, $this.options.duration, function () {

                        $this.dropdown.dropdown.css('visibility', '');
                        $this.dropdown.element.addClass('uk-open');

                        UI.Utils.checkDisplay($this.dropdown.dropdown, true);
                    });

                    $this.pointerleave = false;
                },

                'hide.uk.dropdown': function hideUkDropdown() {
                    $this.overlay.stop().animate({ height: 0 }, $this.options.duration);
                },

                'pointerenter.uk.dropdown': function pointerenterUkDropdown(e, dropdown) {
                    clearTimeout($this.remainIdle);
                },

                'pointerleave.uk.dropdown': function pointerleaveUkDropdown(e, dropdown) {
                    $this.pointerleave = true;
                }
            });

            this.overlay.on({

                'mouseenter': function mouseenter() {
                    if ($this.remainIdle) {
                        clearTimeout($this.dropdown.remainIdle);
                        clearTimeout($this.remainIdle);
                    }
                },

                'mouseleave': function mouseleave() {

                    if ($this.pointerleave && active) {

                        $this.remainIdle = setTimeout(function () {
                            if (active) active.hide();
                        }, active.options.remaintime);
                    }
                }
            });
        }

    });

    function justify(ele, justifyTo, boundarywidth, offset) {

        ele = UI.$(ele);
        justifyTo = UI.$(justifyTo);
        boundarywidth = boundarywidth || window.innerWidth;
        offset = offset || ele.offset();

        if (justifyTo.length) {

            var jwidth = justifyTo.outerWidth();

            ele.css('min-width', jwidth);

            if (UI.langdirection == 'right') {

                var right1 = boundarywidth - (justifyTo.offset().left + jwidth),
                    right2 = boundarywidth - (ele.offset().left + ele.outerWidth());

                ele.css('margin-right', right1 - right2);
            } else {
                ele.css('margin-left', justifyTo.offset().left - offset.left);
            }
        }
    }
})(UIkit2);

(function (UI) {

    "use strict";

    var grids = [];

    UI.component('gridMatchHeight', {

        defaults: {
            target: false,
            row: true,
            ignorestacked: false,
            observe: false
        },

        boot: function boot() {

            // init code
            UI.ready(function (context) {

                UI.$('[data-uk-grid-match]', context).each(function () {
                    var grid = UI.$(this),
                        obj;

                    if (!grid.data('gridMatchHeight')) {
                        obj = UI.gridMatchHeight(grid, UI.Utils.options(grid.attr('data-uk-grid-match')));
                    }
                });
            });
        },

        init: function init() {

            var $this = this;

            this.columns = this.element.children();
            this.elements = this.options.target ? this.find(this.options.target) : this.columns;

            if (!this.columns.length) return;

            UI.$win.on('load resize orientationchange', function () {

                var fn = function fn() {
                    if ($this.element.is(':visible')) $this.match();
                };

                UI.$(function () {
                    fn();
                });

                return UI.Utils.debounce(fn, 50);
            }());

            if (this.options.observe) {

                UI.domObserve(this.element, function (e) {
                    if ($this.element.is(':visible')) $this.match();
                });
            }

            this.on('display.uk.check', function (e) {
                if (this.element.is(':visible')) this.match();
            }.bind(this));

            grids.push(this);
        },

        match: function match() {

            var firstvisible = this.columns.filter(':visible:first');

            if (!firstvisible.length) return;

            var stacked = Math.ceil(100 * parseFloat(firstvisible.css('width')) / parseFloat(firstvisible.parent().css('width'))) >= 100;

            if (stacked && !this.options.ignorestacked) {
                this.revert();
            } else {
                UI.Utils.matchHeights(this.elements, this.options);
            }

            return this;
        },

        revert: function revert() {
            this.elements.css('min-height', '');
            return this;
        }
    });

    UI.component('gridMargin', {

        defaults: {
            cls: 'uk-grid-margin',
            rowfirst: 'uk-row-first'
        },

        boot: function boot() {

            // init code
            UI.ready(function (context) {

                UI.$('[data-uk-grid-margin]', context).each(function () {
                    var grid = UI.$(this),
                        obj;

                    if (!grid.data('gridMargin')) {
                        obj = UI.gridMargin(grid, UI.Utils.options(grid.attr('data-uk-grid-margin')));
                    }
                });
            });
        },

        init: function init() {

            var stackMargin = UI.stackMargin(this.element, this.options);
        }
    });
})(UIkit2);

(function (UI) {

    "use strict";

    var active = false,
        activeCount = 0,
        $html = UI.$html,
        body;

    UI.$win.on('resize orientationchange', UI.Utils.debounce(function () {
        UI.$('.uk-modal.uk-open').each(function () {
            return UI.$(this).data('modal') && UI.$(this).data('modal').resize();
        });
    }, 150));

    UI.component('modal', {

        defaults: {
            keyboard: true,
            bgclose: true,
            minScrollHeight: 150,
            center: false,
            modal: true
        },

        scrollable: false,
        transition: false,
        hasTransitioned: true,

        init: function init() {

            if (!body) body = UI.$('body');

            if (!this.element.length) return;

            var $this = this;

            this.paddingdir = 'padding-' + (UI.langdirection == 'left' ? 'right' : 'left');
            this.dialog = this.find('.uk-modal-dialog');

            this.active = false;

            // Update ARIA
            this.element.attr('aria-hidden', this.element.hasClass('uk-open'));

            this.on('click', '.uk-modal-close', function (e) {
                e.preventDefault();
                $this.hide();
            }).on('click', function (e) {

                var target = UI.$(e.target);

                if (target[0] == $this.element[0] && $this.options.bgclose) {
                    $this.hide();
                }
            });

            UI.domObserve(this.element, function (e) {
                $this.resize();
            });
        },

        toggle: function toggle() {
            return this[this.isActive() ? 'hide' : 'show']();
        },

        show: function show() {

            if (!this.element.length) return;

            var $this = this;

            if (this.isActive()) return;

            if (this.options.modal && active) {
                active.hide(true);
            }

            this.element.removeClass('uk-open').show();
            this.resize(true);

            if (this.options.modal) {
                active = this;
            }

            this.active = true;

            activeCount++;

            if (UI.support.transition) {
                this.hasTransitioned = false;
                this.element.one(UI.support.transition.end, function () {
                    $this.hasTransitioned = true;
                    UI.Utils.focus($this.dialog, 'a[href]');
                }).addClass('uk-open');
            } else {
                this.element.addClass('uk-open');
                UI.Utils.focus(this.dialog, 'a[href]');
            }

            $html.addClass('uk-modal-page').height(); // force browser engine redraw

            // Update ARIA
            this.element.attr('aria-hidden', 'false');

            this.element.trigger('show.uk.modal');

            UI.Utils.checkDisplay(this.dialog, true);

            return this;
        },

        hide: function hide(force) {

            if (!force && UI.support.transition && this.hasTransitioned) {

                var $this = this;

                this.one(UI.support.transition.end, function () {
                    $this._hide();
                }).removeClass('uk-open');
            } else {

                this._hide();
            }

            return this;
        },

        resize: function resize(force) {

            if (!this.isActive() && !force) return;

            var bodywidth = body.width();

            this.scrollbarwidth = window.innerWidth - bodywidth;

            body.css(this.paddingdir, this.scrollbarwidth);

            this.element.css('overflow-y', this.scrollbarwidth ? 'scroll' : 'auto');

            if (!this.updateScrollable() && this.options.center) {

                var dh = this.dialog.outerHeight(),
                    pad = parseInt(this.dialog.css('margin-top'), 10) + parseInt(this.dialog.css('margin-bottom'), 10);

                if (dh + pad < window.innerHeight) {
                    this.dialog.css({ top: window.innerHeight / 2 - dh / 2 - pad });
                } else {
                    this.dialog.css({ top: '' });
                }
            }
        },

        updateScrollable: function updateScrollable() {

            // has scrollable?
            var scrollable = this.dialog.find('.uk-overflow-container:visible:first');

            if (scrollable.length) {

                scrollable.css('height', 0);

                var offset = Math.abs(parseInt(this.dialog.css('margin-top'), 10)),
                    dh = this.dialog.outerHeight(),
                    wh = window.innerHeight,
                    h = wh - 2 * (offset < 20 ? 20 : offset) - dh;

                scrollable.css({
                    maxHeight: h < this.options.minScrollHeight ? '' : h,
                    height: ''
                });

                return true;
            }

            return false;
        },

        _hide: function _hide() {

            this.active = false;
            if (activeCount > 0) activeCount--;else activeCount = 0;

            this.element.hide().removeClass('uk-open');

            // Update ARIA
            this.element.attr('aria-hidden', 'true');

            if (!activeCount) {
                $html.removeClass('uk-modal-page');
                body.css(this.paddingdir, "");
            }

            if (active === this) active = false;

            this.trigger('hide.uk.modal');
        },

        isActive: function isActive() {
            return this.element.hasClass('uk-open');
        }

    });

    UI.component('modalTrigger', {

        boot: function boot() {

            // init code
            UI.$html.on('click.modal.uikit', '[data-uk-modal]', function (e) {

                var ele = UI.$(this);

                if (ele.is('a')) {
                    e.preventDefault();
                }

                if (!ele.data('modalTrigger')) {
                    var modal = UI.modalTrigger(ele, UI.Utils.options(ele.attr('data-uk-modal')));
                    modal.show();
                }
            });

            // close modal on esc button
            UI.$html.on('keydown.modal.uikit', function (e) {

                if (active && e.keyCode === 27 && active.options.keyboard) {
                    // ESC
                    e.preventDefault();
                    active.hide();
                }
            });
        },

        init: function init() {

            var $this = this;

            this.options = UI.$.extend({
                target: $this.element.is('a') ? $this.element.attr('href') : false
            }, this.options);

            this.modal = UI.modal(this.options.target, this.options);

            this.on("click", function (e) {
                e.preventDefault();
                $this.show();
            });

            //methods
            this.proxy(this.modal, 'show hide isActive');
        }
    });

    UI.modal.dialog = function (content, options) {

        var modal = UI.modal(UI.$(UI.modal.dialog.template).appendTo('body'), options);

        modal.on('hide.uk.modal', function () {
            if (modal.persist) {
                modal.persist.appendTo(modal.persist.data('modalPersistParent'));
                modal.persist = false;
            }
            modal.element.remove();
        });

        setContent(content, modal);

        return modal;
    };

    UI.modal.dialog.template = '<div class="uk-modal"><div class="uk-modal-dialog" style="min-height:0;"></div></div>';

    UI.modal.alert = function (content, options) {

        options = UI.$.extend(true, { bgclose: false, keyboard: false, modal: false, labels: UI.modal.labels }, options);

        var modal = UI.modal.dialog(['<div class="uk-margin uk-modal-content">' + String(content) + '</div>', '<div class="uk-modal-footer uk-text-right"><button class="uk-button uk-button-primary uk-modal-close">' + options.labels.Ok + '</button></div>'].join(""), options);

        modal.on('show.uk.modal', function () {
            setTimeout(function () {
                modal.element.find('button:first').focus();
            }, 50);
        });

        return modal.show();
    };

    UI.modal.confirm = function (content, onconfirm, oncancel) {

        var options = arguments.length > 1 && arguments[arguments.length - 1] ? arguments[arguments.length - 1] : {};

        onconfirm = UI.$.isFunction(onconfirm) ? onconfirm : function () {};
        oncancel = UI.$.isFunction(oncancel) ? oncancel : function () {};
        options = UI.$.extend(true, { bgclose: false, keyboard: false, modal: false, labels: UI.modal.labels }, UI.$.isFunction(options) ? {} : options);

        var modal = UI.modal.dialog(['<div class="uk-margin uk-modal-content">' + String(content) + '</div>', '<div class="uk-modal-footer uk-text-right"><button class="uk-button js-modal-confirm-cancel">' + options.labels.Cancel + '</button> <button class="uk-button uk-button-primary js-modal-confirm">' + options.labels.Ok + '</button></div>'].join(""), options);

        modal.element.find(".js-modal-confirm, .js-modal-confirm-cancel").on("click", function () {
            UI.$(this).is('.js-modal-confirm') ? onconfirm() : oncancel();
            modal.hide();
        });

        modal.on('show.uk.modal', function () {
            setTimeout(function () {
                modal.element.find('.js-modal-confirm').focus();
            }, 50);
        });

        return modal.show();
    };

    UI.modal.prompt = function (text, value, onsubmit, options) {

        onsubmit = UI.$.isFunction(onsubmit) ? onsubmit : function (value) {};
        options = UI.$.extend(true, { bgclose: false, keyboard: false, modal: false, labels: UI.modal.labels }, options);

        var modal = UI.modal.dialog([text ? '<div class="uk-modal-content uk-form">' + String(text) + '</div>' : '', '<div class="uk-margin-small-top uk-modal-content uk-form"><p><input type="text" class="uk-width-1-1"></p></div>', '<div class="uk-modal-footer uk-text-right"><button class="uk-button uk-modal-close">' + options.labels.Cancel + '</button> <button class="uk-button uk-button-primary js-modal-ok">' + options.labels.Ok + '</button></div>'].join(""), options),
            input = modal.element.find("input[type='text']").val(value || '').on('keyup', function (e) {
            if (e.keyCode == 13) {
                modal.element.find('.js-modal-ok').trigger('click');
            }
        });

        modal.element.find('.js-modal-ok').on('click', function () {
            if (onsubmit(input.val()) !== false) {
                modal.hide();
            }
        });

        return modal.show();
    };

    UI.modal.blockUI = function (content, options) {

        var modal = UI.modal.dialog(['<div class="uk-margin uk-modal-content">' + String(content || '<div class="uk-text-center">...</div>') + '</div>'].join(""), UI.$.extend({ bgclose: false, keyboard: false, modal: false }, options));

        modal.content = modal.element.find('.uk-modal-content:first');

        return modal.show();
    };

    UI.modal.labels = {
        Ok: 'Ok',
        Cancel: 'Cancel'
    };

    // helper functions
    function setContent(content, modal) {

        if (!modal) return;

        if ((typeof content === 'undefined' ? 'undefined' : (0, _typeof3.default)(content)) === 'object') {

            // convert DOM object to a jQuery object
            content = content instanceof jQuery ? content : UI.$(content);

            if (content.parent().length) {
                modal.persist = content;
                modal.persist.data('modalPersistParent', content.parent());
            }
        } else if (typeof content === 'string' || typeof content === 'number') {
            // just insert the data as innerHTML
            content = UI.$('<div></div>').html(content);
        } else {
            // unsupported data type!
            content = UI.$('<div></div>').html('UIkit2.modal Error: Unsupported data type: ' + (typeof content === 'undefined' ? 'undefined' : (0, _typeof3.default)(content)));
        }

        content.appendTo(modal.element.find('.uk-modal-dialog'));

        return modal;
    }
})(UIkit2);

(function (UI) {

    "use strict";

    UI.component('nav', {

        defaults: {
            toggle: '>li.uk-parent > a[href="#"]',
            lists: '>li.uk-parent > ul',
            multiple: false
        },

        boot: function boot() {

            // init code
            UI.ready(function (context) {

                UI.$('[data-uk-nav]', context).each(function () {
                    var nav = UI.$(this);

                    if (!nav.data('nav')) {
                        var obj = UI.nav(nav, UI.Utils.options(nav.attr('data-uk-nav')));
                    }
                });
            });
        },

        init: function init() {

            var $this = this;

            this.on('click.uk.nav', this.options.toggle, function (e) {
                e.preventDefault();
                var ele = UI.$(this);
                $this.open(ele.parent()[0] == $this.element[0] ? ele : ele.parent("li"));
            });

            this.update();

            UI.domObserve(this.element, function (e) {
                if ($this.element.find($this.options.lists).not('[role]').length) {
                    $this.update();
                }
            });
        },

        update: function update() {

            var $this = this;

            this.find(this.options.lists).each(function () {

                var $ele = UI.$(this).attr('role', 'menu'),
                    parent = $ele.closest('li'),
                    active = parent.hasClass("uk-active");

                if (!parent.data('list-container')) {
                    $ele.wrap('<div style="overflow:hidden;height:0;position:relative;"></div>');
                    parent.data('list-container', $ele.parent()[active ? 'removeClass' : 'addClass']('uk-hidden'));
                }

                // Init ARIA
                parent.attr('aria-expanded', parent.hasClass("uk-open"));

                if (active) $this.open(parent, true);
            });
        },

        open: function open(li, noanimation) {

            var $this = this,
                element = this.element,
                $li = UI.$(li),
                $container = $li.data('list-container');

            if (!this.options.multiple) {

                element.children('.uk-open').not(li).each(function () {

                    var ele = UI.$(this);

                    if (ele.data('list-container')) {
                        ele.data('list-container').stop().animate({ height: 0 }, function () {
                            UI.$(this).parent().removeClass('uk-open').end().addClass('uk-hidden');
                        });
                    }
                });
            }

            $li.toggleClass('uk-open');

            // Update ARIA
            $li.attr('aria-expanded', $li.hasClass('uk-open'));

            if ($container) {

                if ($li.hasClass('uk-open')) {
                    $container.removeClass('uk-hidden');
                }

                if (noanimation) {

                    $container.stop().height($li.hasClass('uk-open') ? 'auto' : 0);

                    if (!$li.hasClass('uk-open')) {
                        $container.addClass('uk-hidden');
                    }

                    this.trigger('display.uk.check');
                } else {

                    $container.stop().animate({
                        height: $li.hasClass('uk-open') ? getHeight($container.find('ul:first')) : 0
                    }, function () {

                        if (!$li.hasClass('uk-open')) {
                            $container.addClass('uk-hidden');
                        } else {
                            $container.css('height', '');
                        }

                        $this.trigger('display.uk.check');
                    });
                }
            }
        }
    });

    // helper

    function getHeight(ele) {

        var $ele = UI.$(ele),
            height = 'auto';

        if ($ele.is(':visible')) {
            height = $ele.outerHeight();
        } else {

            var tmp = {
                position: $ele.css('position'),
                visibility: $ele.css('visibility'),
                display: $ele.css('display')
            };

            height = $ele.css({ position: 'absolute', visibility: 'hidden', display: 'block' }).outerHeight();

            $ele.css(tmp); // reset element
        }

        return height;
    }
})(UIkit2);

(function (UI) {

    "use strict";

    var scrollpos = { x: window.scrollX, y: window.scrollY },
        $win = UI.$win,
        $doc = UI.$doc,
        $html = UI.$html,
        Offcanvas = {

        show: function show(element, options) {

            element = UI.$(element);

            if (!element.length) return;

            options = UI.$.extend({ mode: 'push' }, options);

            var $body = UI.$('body'),
                bar = element.find('.uk-offcanvas-bar:first'),
                rtl = UI.langdirection == 'right',
                flip = bar.hasClass('uk-offcanvas-bar-flip') ? -1 : 1,
                dir = flip * (rtl ? -1 : 1),
                scrollbarwidth = window.innerWidth - $body.width();

            scrollpos = { x: window.pageXOffset, y: window.pageYOffset };

            bar.attr('mode', options.mode);
            element.addClass('uk-active');

            $body.css({ width: window.innerWidth - scrollbarwidth, height: window.innerHeight }).addClass('uk-offcanvas-page');

            if (options.mode == 'push' || options.mode == 'reveal') {
                $body.css(rtl ? 'margin-right' : 'margin-left', (rtl ? -1 : 1) * (bar.outerWidth() * dir));
            }

            if (options.mode == 'reveal') {
                bar.css('clip', 'rect(0, ' + bar.outerWidth() + 'px, 100vh, 0)');
            }

            $html.css('margin-top', scrollpos.y * -1).width(); // .width() - force redraw


            bar.addClass('uk-offcanvas-bar-show');

            this._initElement(element);

            bar.trigger('show.uk.offcanvas', [element, bar]);

            // Update ARIA
            element.attr('aria-hidden', 'false');
        },

        hide: function hide(force) {

            var $body = UI.$('body'),
                panel = UI.$('.uk-offcanvas.uk-active'),
                rtl = UI.langdirection == 'right',
                bar = panel.find('.uk-offcanvas-bar:first'),
                finalize = function finalize() {
                $body.removeClass('uk-offcanvas-page').css({ width: '', height: '', marginLeft: '', marginRight: '' });
                panel.removeClass('uk-active');

                bar.removeClass('uk-offcanvas-bar-show');
                $html.css('margin-top', '');
                window.scrollTo(scrollpos.x, scrollpos.y);
                bar.trigger('hide.uk.offcanvas', [panel, bar]);

                // Update ARIA
                panel.attr('aria-hidden', 'true');
            };

            if (!panel.length) return;
            if (bar.attr('mode') == 'none') force = true;

            if (UI.support.transition && !force) {

                $body.one(UI.support.transition.end, function () {
                    finalize();
                }).css(rtl ? 'margin-right' : 'margin-left', '');

                if (bar.attr('mode') == 'reveal') {
                    bar.css('clip', '');
                }

                setTimeout(function () {
                    bar.removeClass('uk-offcanvas-bar-show');
                }, 0);
            } else {
                finalize();
            }
        },

        _initElement: function _initElement(element) {

            if (element.data('OffcanvasInit')) return;

            element.on('click.uk.offcanvas swipeRight.uk.offcanvas swipeLeft.uk.offcanvas', function (e) {

                var target = UI.$(e.target);

                if (!e.type.match(/swipe/)) {

                    if (!target.hasClass('uk-offcanvas-close')) {
                        if (target.hasClass('uk-offcanvas-bar')) return;
                        if (target.parents('.uk-offcanvas-bar:first').length) return;
                    }
                }

                e.stopImmediatePropagation();
                Offcanvas.hide();
            });

            element.on('click', 'a[href*="#"]', function (e) {

                var link = UI.$(this),
                    href = link.attr('href');

                if (href == '#') {
                    return;
                }

                UI.$doc.one('hide.uk.offcanvas', function () {

                    var target;

                    try {
                        target = UI.$(link[0].hash);
                    } catch (e) {
                        target = '';
                    }

                    if (!target.length) {
                        target = UI.$('[name="' + link[0].hash.replace('#', '') + '"]');
                    }

                    if (target.length && UI.Utils.scrollToElement) {
                        UI.Utils.scrollToElement(target, UI.Utils.options(link.attr('data-uk-smooth-scroll') || '{}'));
                    } else {
                        window.location.href = href;
                    }
                });

                Offcanvas.hide();
            });

            element.data('OffcanvasInit', true);
        }
    };

    UI.component('offcanvasTrigger', {

        boot: function boot() {

            // init code
            $html.on('click.offcanvas.uikit', '[data-uk-offcanvas]', function (e) {

                e.preventDefault();

                var ele = UI.$(this);

                if (!ele.data('offcanvasTrigger')) {
                    var obj = UI.offcanvasTrigger(ele, UI.Utils.options(ele.attr('data-uk-offcanvas')));
                    ele.trigger("click");
                }
            });

            $html.on('keydown.uk.offcanvas', function (e) {

                if (e.keyCode === 27) {
                    // ESC
                    Offcanvas.hide();
                }
            });
        },

        init: function init() {

            var $this = this;

            this.options = UI.$.extend({
                target: $this.element.is('a') ? $this.element.attr('href') : false,
                mode: 'push'
            }, this.options);

            this.on('click', function (e) {
                e.preventDefault();
                Offcanvas.show($this.options.target, $this.options);
            });
        }
    });

    UI.offcanvas = Offcanvas;
})(UIkit2);

(function (UI) {

    "use strict";

    var Animations;

    UI.component('switcher', {

        defaults: {
            connect: false,
            toggle: '>*',
            active: 0,
            animation: false,
            duration: 200,
            swiping: true
        },

        animating: false,

        boot: function boot() {

            // init code
            UI.ready(function (context) {

                UI.$('[data-uk-switcher]', context).each(function () {
                    var switcher = UI.$(this);

                    if (!switcher.data('switcher')) {
                        var obj = UI.switcher(switcher, UI.Utils.options(switcher.attr('data-uk-switcher')));
                    }
                });
            });
        },

        init: function init() {

            var $this = this;

            this.on('click.uk.switcher', this.options.toggle, function (e) {
                e.preventDefault();
                $this.show(this);
            });

            if (!this.options.connect) {
                return;
            }

            this.connect = UI.$(this.options.connect);

            if (!this.connect.length) {
                return;
            }

            this.connect.on('click.uk.switcher', '[data-uk-switcher-item]', function (e) {

                e.preventDefault();

                var item = UI.$(this).attr('data-uk-switcher-item');

                if ($this.index == item) return;

                switch (item) {
                    case 'next':
                    case 'previous':
                        $this.show($this.index + (item == 'next' ? 1 : -1));
                        break;
                    default:
                        $this.show(parseInt(item, 10));
                }
            });

            if (this.options.swiping) {

                this.connect.on('swipeRight swipeLeft', function (e) {
                    e.preventDefault();
                    if (!window.getSelection().toString()) {
                        $this.show($this.index + (e.type == 'swipeLeft' ? 1 : -1));
                    }
                });
            }

            this.update();
        },

        update: function update() {

            this.connect.children().removeClass('uk-active').attr('aria-hidden', 'true');

            var toggles = this.find(this.options.toggle),
                active = toggles.filter('.uk-active');

            if (active.length) {
                this.show(active, false);
            } else {

                if (this.options.active === false) return;

                active = toggles.eq(this.options.active);
                this.show(active.length ? active : toggles.eq(0), false);
            }

            // Init ARIA for toggles
            toggles.not(active).attr('aria-expanded', 'false');
            active.attr('aria-expanded', 'true');
        },

        show: function show(tab, animate) {

            if (this.animating) {
                return;
            }

            var toggles = this.find(this.options.toggle);

            if (isNaN(tab)) {
                tab = UI.$(tab);
            } else {
                tab = tab < 0 ? toggles.length - 1 : tab;
                tab = toggles.eq(toggles[tab] ? tab : 0);
            }

            var $this = this,
                active = UI.$(tab),
                animation = Animations[this.options.animation] || function (current, next) {

                if (!$this.options.animation) {
                    return Animations.none.apply($this);
                }

                var anim = $this.options.animation.split(',');

                if (anim.length == 1) {
                    anim[1] = anim[0];
                }

                anim[0] = anim[0].trim();
                anim[1] = anim[1].trim();

                return coreAnimation.apply($this, [anim, current, next]);
            };

            if (animate === false || !UI.support.animation) {
                animation = Animations.none;
            }

            if (active.hasClass("uk-disabled")) return;

            // Update ARIA for Toggles
            toggles.attr('aria-expanded', 'false');
            active.attr('aria-expanded', 'true');

            toggles.filter(".uk-active").removeClass("uk-active");
            active.addClass("uk-active");

            if (this.options.connect && this.connect.length) {

                this.index = this.find(this.options.toggle).index(active);

                if (this.index == -1) {
                    this.index = 0;
                }

                this.connect.each(function () {

                    var container = UI.$(this),
                        children = UI.$(container.children()),
                        current = UI.$(children.filter('.uk-active')),
                        next = UI.$(children.eq($this.index));

                    $this.animating = true;

                    animation.apply($this, [current, next]).then(function () {

                        current.removeClass("uk-active");
                        next.addClass("uk-active");

                        // Update ARIA for connect
                        current.attr('aria-hidden', 'true');
                        next.attr('aria-hidden', 'false');

                        UI.Utils.checkDisplay(next, true);

                        $this.animating = false;
                    });
                });
            }

            this.trigger("show.uk.switcher", [active]);
        }
    });

    Animations = {

        'none': function none() {
            var d = UI.$.Deferred();
            d.resolve();
            return d.promise();
        },

        'fade': function fade(current, next) {
            return coreAnimation.apply(this, ['uk-animation-fade', current, next]);
        },

        'slide-bottom': function slideBottom(current, next) {
            return coreAnimation.apply(this, ['uk-animation-slide-bottom', current, next]);
        },

        'slide-top': function slideTop(current, next) {
            return coreAnimation.apply(this, ['uk-animation-slide-top', current, next]);
        },

        'slide-vertical': function slideVertical(current, next, dir) {

            var anim = ['uk-animation-slide-top', 'uk-animation-slide-bottom'];

            if (current && current.index() > next.index()) {
                anim.reverse();
            }

            return coreAnimation.apply(this, [anim, current, next]);
        },

        'slide-left': function slideLeft(current, next) {
            return coreAnimation.apply(this, ['uk-animation-slide-left', current, next]);
        },

        'slide-right': function slideRight(current, next) {
            return coreAnimation.apply(this, ['uk-animation-slide-right', current, next]);
        },

        'slide-horizontal': function slideHorizontal(current, next, dir) {

            var anim = ['uk-animation-slide-right', 'uk-animation-slide-left'];

            if (current && current.index() > next.index()) {
                anim.reverse();
            }

            return coreAnimation.apply(this, [anim, current, next]);
        },

        'scale': function scale(current, next) {
            return coreAnimation.apply(this, ['uk-animation-scale-up', current, next]);
        }
    };

    UI.switcher.animations = Animations;

    // helpers

    function coreAnimation(cls, current, next) {

        var d = UI.$.Deferred(),
            clsIn = cls,
            clsOut = cls,
            release;

        if (next[0] === current[0]) {
            d.resolve();
            return d.promise();
        }

        if ((typeof cls === 'undefined' ? 'undefined' : (0, _typeof3.default)(cls)) == 'object') {
            clsIn = cls[0];
            clsOut = cls[1] || cls[0];
        }

        UI.$body.css('overflow-x', 'hidden'); // fix scroll jumping in iOS

        release = function release() {

            if (current) current.hide().removeClass('uk-active ' + clsOut + ' uk-animation-reverse');

            next.addClass(clsIn).one(UI.support.animation.end, function () {

                setTimeout(function () {
                    next.removeClass('' + clsIn + '').css({ opacity: '', display: '' });
                }, 0);

                d.resolve();

                UI.$body.css('overflow-x', '');

                if (current) current.css({ opacity: '', display: '' });
            }.bind(this)).show();
        };

        next.css('animation-duration', this.options.duration + 'ms');

        if (current && current.length) {

            current.css('animation-duration', this.options.duration + 'ms');

            current.css('display', 'none').addClass(clsOut + ' uk-animation-reverse').one(UI.support.animation.end, function () {
                release();
            }.bind(this)).css('display', '');
        } else {
            next.addClass('uk-active');
            release();
        }

        return d.promise();
    }
})(UIkit2);

(function (UI) {

    "use strict";

    UI.component('tab', {

        defaults: {
            target: '>li:not(.uk-tab-responsive, .uk-disabled)',
            connect: false,
            active: 0,
            animation: false,
            duration: 200,
            swiping: true
        },

        boot: function boot() {

            // init code
            UI.ready(function (context) {

                UI.$('[data-uk-tab]', context).each(function () {

                    var tab = UI.$(this);

                    if (!tab.data('tab')) {
                        var obj = UI.tab(tab, UI.Utils.options(tab.attr('data-uk-tab')));
                    }
                });
            });
        },

        init: function init() {

            var $this = this;

            this.current = false;

            this.on('click.uk.tab', this.options.target, function (e) {

                e.preventDefault();

                if ($this.switcher && $this.switcher.animating) {
                    return;
                }

                var current = $this.find($this.options.target).not(this);

                current.removeClass('uk-active').blur();

                $this.trigger('change.uk.tab', [UI.$(this).addClass('uk-active'), $this.current]);

                $this.current = UI.$(this);

                // Update ARIA
                if (!$this.options.connect) {
                    current.attr('aria-expanded', 'false');
                    UI.$(this).attr('aria-expanded', 'true');
                }
            });

            if (this.options.connect) {
                this.connect = UI.$(this.options.connect);
            }

            // init responsive tab
            this.responsivetab = UI.$('<li class="uk-tab-responsive uk-active"><a></a></li>').append('<div class="uk-dropdown uk-dropdown-small"><ul class="uk-nav uk-nav-dropdown"></ul><div>');

            this.responsivetab.dropdown = this.responsivetab.find('.uk-dropdown');
            this.responsivetab.lst = this.responsivetab.dropdown.find('ul');
            this.responsivetab.caption = this.responsivetab.find('a:first');

            if (this.element.hasClass('uk-tab-bottom')) this.responsivetab.dropdown.addClass('uk-dropdown-up');

            // handle click
            this.responsivetab.lst.on('click.uk.tab', 'a', function (e) {

                e.preventDefault();
                e.stopPropagation();

                var link = UI.$(this);

                $this.element.children('li:not(.uk-tab-responsive)').eq(link.data('index')).trigger('click');
            });

            this.on('show.uk.switcher change.uk.tab', function (e, tab) {
                $this.responsivetab.caption.html(tab.text());
            });

            this.element.append(this.responsivetab);

            // init UIkit components
            if (this.options.connect) {

                this.switcher = UI.switcher(this.element, {
                    toggle: '>li:not(.uk-tab-responsive)',
                    connect: this.options.connect,
                    active: this.options.active,
                    animation: this.options.animation,
                    duration: this.options.duration,
                    swiping: this.options.swiping
                });
            }

            UI.dropdown(this.responsivetab, { mode: 'click', preventflip: 'y' });

            // init
            $this.trigger('change.uk.tab', [this.element.find(this.options.target).not('.uk-tab-responsive').filter('.uk-active')]);

            this.check();

            UI.$win.on('resize orientationchange', UI.Utils.debounce(function () {
                if ($this.element.is(':visible')) $this.check();
            }, 100));

            this.on('display.uk.check', function () {
                if ($this.element.is(':visible')) $this.check();
            });
        },

        check: function check() {

            var children = this.element.children('li:not(.uk-tab-responsive)').removeClass('uk-hidden');

            if (!children.length) {
                this.responsivetab.addClass('uk-hidden');
                return;
            }

            var top = children.eq(0).offset().top + Math.ceil(children.eq(0).height() / 2),
                doresponsive = false,
                item,
                link,
                clone;

            this.responsivetab.lst.empty();

            children.each(function () {

                if (UI.$(this).offset().top > top) {
                    doresponsive = true;
                }
            });

            if (doresponsive) {

                for (var i = 0; i < children.length; i++) {

                    item = UI.$(children.eq(i));
                    link = item.find('a');

                    if (item.css('float') != 'none' && !item.attr('uk-dropdown')) {

                        if (!item.hasClass('uk-disabled')) {

                            clone = UI.$(item[0].outerHTML);
                            clone.find('a').data('index', i);

                            this.responsivetab.lst.append(clone);
                        }

                        item.addClass('uk-hidden');
                    }
                }
            }

            this.responsivetab[this.responsivetab.lst.children('li').length ? 'removeClass' : 'addClass']('uk-hidden');
        }
    });
})(UIkit2);

(function (UI) {

    "use strict";

    UI.component('cover', {

        defaults: {
            automute: true
        },

        boot: function boot() {

            // auto init
            UI.ready(function (context) {

                UI.$('[data-uk-cover]', context).each(function () {

                    var ele = UI.$(this);

                    if (!ele.data('cover')) {
                        var plugin = UI.cover(ele, UI.Utils.options(ele.attr('data-uk-cover')));
                    }
                });
            });
        },

        init: function init() {

            this.parent = this.element.parent();

            UI.$win.on('load resize orientationchange', UI.Utils.debounce(function () {
                this.check();
            }.bind(this), 100));

            this.on('display.uk.check', function (e) {
                if (this.element.is(':visible')) this.check();
            }.bind(this));

            this.check();

            if (this.element.is('iframe') && this.options.automute) {

                var src = this.element.attr('src');

                this.element.attr('src', '').on('load', function () {
                    this.contentWindow.postMessage('{ "event": "command", "func": "mute", "method":"setVolume", "value":0}', '*');
                }).attr('src', [src, src.indexOf('?') > -1 ? '&' : '?', 'enablejsapi=1&api=1'].join(''));
            }
        },

        check: function check() {

            this.element.css({ width: '', height: '' });

            this.dimension = { w: this.element.width(), h: this.element.height() };

            if (this.element.attr('width') && !isNaN(this.element.attr('width'))) {
                this.dimension.w = this.element.attr('width');
            }

            if (this.element.attr('height') && !isNaN(this.element.attr('height'))) {
                this.dimension.h = this.element.attr('height');
            }

            this.ratio = this.dimension.w / this.dimension.h;

            var w = this.parent.width(),
                h = this.parent.height(),
                width,
                height;

            // if element height < parent height (gap underneath)
            if (w / this.ratio < h) {

                width = Math.ceil(h * this.ratio);
                height = h;

                // element width < parent width (gap to right)
            } else {

                width = w;
                height = Math.ceil(w / this.ratio);
            }

            this.element.css({ width: width, height: height });
        }
    });
})(UIkit2);

/***/ }),

/***/ "../node_modules/babel-runtime/core-js/object/keys.js":
/***/ (function(module, exports, __webpack_require__) {

module.exports = { "default": __webpack_require__("../node_modules/core-js/library/fn/object/keys.js"), __esModule: true };

/***/ }),

/***/ "../node_modules/babel-runtime/core-js/symbol.js":
/***/ (function(module, exports, __webpack_require__) {

module.exports = { "default": __webpack_require__("../node_modules/core-js/library/fn/symbol/index.js"), __esModule: true };

/***/ }),

/***/ "../node_modules/babel-runtime/core-js/symbol/iterator.js":
/***/ (function(module, exports, __webpack_require__) {

module.exports = { "default": __webpack_require__("../node_modules/core-js/library/fn/symbol/iterator.js"), __esModule: true };

/***/ }),

/***/ "../node_modules/babel-runtime/helpers/typeof.js":
/***/ (function(module, exports, __webpack_require__) {

"use strict";


exports.__esModule = true;

var _iterator = __webpack_require__("../node_modules/babel-runtime/core-js/symbol/iterator.js");

var _iterator2 = _interopRequireDefault(_iterator);

var _symbol = __webpack_require__("../node_modules/babel-runtime/core-js/symbol.js");

var _symbol2 = _interopRequireDefault(_symbol);

var _typeof = typeof _symbol2.default === "function" && typeof _iterator2.default === "symbol" ? function (obj) { return typeof obj; } : function (obj) { return obj && typeof _symbol2.default === "function" && obj.constructor === _symbol2.default && obj !== _symbol2.default.prototype ? "symbol" : typeof obj; };

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

exports.default = typeof _symbol2.default === "function" && _typeof(_iterator2.default) === "symbol" ? function (obj) {
  return typeof obj === "undefined" ? "undefined" : _typeof(obj);
} : function (obj) {
  return obj && typeof _symbol2.default === "function" && obj.constructor === _symbol2.default && obj !== _symbol2.default.prototype ? "symbol" : typeof obj === "undefined" ? "undefined" : _typeof(obj);
};

/***/ }),

/***/ "../node_modules/core-js/library/fn/object/keys.js":
/***/ (function(module, exports, __webpack_require__) {

__webpack_require__("../node_modules/core-js/library/modules/es6.object.keys.js");
module.exports = __webpack_require__("../node_modules/core-js/library/modules/_core.js").Object.keys;


/***/ }),

/***/ "../node_modules/core-js/library/fn/symbol/index.js":
/***/ (function(module, exports, __webpack_require__) {

__webpack_require__("../node_modules/core-js/library/modules/es6.symbol.js");
__webpack_require__("../node_modules/core-js/library/modules/es6.object.to-string.js");
__webpack_require__("../node_modules/core-js/library/modules/es7.symbol.async-iterator.js");
__webpack_require__("../node_modules/core-js/library/modules/es7.symbol.observable.js");
module.exports = __webpack_require__("../node_modules/core-js/library/modules/_core.js").Symbol;


/***/ }),

/***/ "../node_modules/core-js/library/fn/symbol/iterator.js":
/***/ (function(module, exports, __webpack_require__) {

__webpack_require__("../node_modules/core-js/library/modules/es6.string.iterator.js");
__webpack_require__("../node_modules/core-js/library/modules/web.dom.iterable.js");
module.exports = __webpack_require__("../node_modules/core-js/library/modules/_wks-ext.js").f('iterator');


/***/ }),

/***/ "../node_modules/core-js/library/modules/_a-function.js":
/***/ (function(module, exports) {

module.exports = function (it) {
  if (typeof it != 'function') throw TypeError(it + ' is not a function!');
  return it;
};


/***/ }),

/***/ "../node_modules/core-js/library/modules/_add-to-unscopables.js":
/***/ (function(module, exports) {

module.exports = function () { /* empty */ };


/***/ }),

/***/ "../node_modules/core-js/library/modules/_an-object.js":
/***/ (function(module, exports, __webpack_require__) {

var isObject = __webpack_require__("../node_modules/core-js/library/modules/_is-object.js");
module.exports = function (it) {
  if (!isObject(it)) throw TypeError(it + ' is not an object!');
  return it;
};


/***/ }),

/***/ "../node_modules/core-js/library/modules/_array-includes.js":
/***/ (function(module, exports, __webpack_require__) {

// false -> Array#indexOf
// true  -> Array#includes
var toIObject = __webpack_require__("../node_modules/core-js/library/modules/_to-iobject.js");
var toLength = __webpack_require__("../node_modules/core-js/library/modules/_to-length.js");
var toAbsoluteIndex = __webpack_require__("../node_modules/core-js/library/modules/_to-absolute-index.js");
module.exports = function (IS_INCLUDES) {
  return function ($this, el, fromIndex) {
    var O = toIObject($this);
    var length = toLength(O.length);
    var index = toAbsoluteIndex(fromIndex, length);
    var value;
    // Array#includes uses SameValueZero equality algorithm
    // eslint-disable-next-line no-self-compare
    if (IS_INCLUDES && el != el) while (length > index) {
      value = O[index++];
      // eslint-disable-next-line no-self-compare
      if (value != value) return true;
    // Array#indexOf ignores holes, Array#includes - not
    } else for (;length > index; index++) if (IS_INCLUDES || index in O) {
      if (O[index] === el) return IS_INCLUDES || index || 0;
    } return !IS_INCLUDES && -1;
  };
};


/***/ }),

/***/ "../node_modules/core-js/library/modules/_cof.js":
/***/ (function(module, exports) {

var toString = {}.toString;

module.exports = function (it) {
  return toString.call(it).slice(8, -1);
};


/***/ }),

/***/ "../node_modules/core-js/library/modules/_core.js":
/***/ (function(module, exports) {

var core = module.exports = { version: '2.5.1' };
if (typeof __e == 'number') __e = core; // eslint-disable-line no-undef


/***/ }),

/***/ "../node_modules/core-js/library/modules/_ctx.js":
/***/ (function(module, exports, __webpack_require__) {

// optional / simple context binding
var aFunction = __webpack_require__("../node_modules/core-js/library/modules/_a-function.js");
module.exports = function (fn, that, length) {
  aFunction(fn);
  if (that === undefined) return fn;
  switch (length) {
    case 1: return function (a) {
      return fn.call(that, a);
    };
    case 2: return function (a, b) {
      return fn.call(that, a, b);
    };
    case 3: return function (a, b, c) {
      return fn.call(that, a, b, c);
    };
  }
  return function (/* ...args */) {
    return fn.apply(that, arguments);
  };
};


/***/ }),

/***/ "../node_modules/core-js/library/modules/_defined.js":
/***/ (function(module, exports) {

// 7.2.1 RequireObjectCoercible(argument)
module.exports = function (it) {
  if (it == undefined) throw TypeError("Can't call method on  " + it);
  return it;
};


/***/ }),

/***/ "../node_modules/core-js/library/modules/_descriptors.js":
/***/ (function(module, exports, __webpack_require__) {

// Thank's IE8 for his funny defineProperty
module.exports = !__webpack_require__("../node_modules/core-js/library/modules/_fails.js")(function () {
  return Object.defineProperty({}, 'a', { get: function () { return 7; } }).a != 7;
});


/***/ }),

/***/ "../node_modules/core-js/library/modules/_dom-create.js":
/***/ (function(module, exports, __webpack_require__) {

var isObject = __webpack_require__("../node_modules/core-js/library/modules/_is-object.js");
var document = __webpack_require__("../node_modules/core-js/library/modules/_global.js").document;
// typeof document.createElement is 'object' in old IE
var is = isObject(document) && isObject(document.createElement);
module.exports = function (it) {
  return is ? document.createElement(it) : {};
};


/***/ }),

/***/ "../node_modules/core-js/library/modules/_enum-bug-keys.js":
/***/ (function(module, exports) {

// IE 8- don't enum bug keys
module.exports = (
  'constructor,hasOwnProperty,isPrototypeOf,propertyIsEnumerable,toLocaleString,toString,valueOf'
).split(',');


/***/ }),

/***/ "../node_modules/core-js/library/modules/_enum-keys.js":
/***/ (function(module, exports, __webpack_require__) {

// all enumerable object keys, includes symbols
var getKeys = __webpack_require__("../node_modules/core-js/library/modules/_object-keys.js");
var gOPS = __webpack_require__("../node_modules/core-js/library/modules/_object-gops.js");
var pIE = __webpack_require__("../node_modules/core-js/library/modules/_object-pie.js");
module.exports = function (it) {
  var result = getKeys(it);
  var getSymbols = gOPS.f;
  if (getSymbols) {
    var symbols = getSymbols(it);
    var isEnum = pIE.f;
    var i = 0;
    var key;
    while (symbols.length > i) if (isEnum.call(it, key = symbols[i++])) result.push(key);
  } return result;
};


/***/ }),

/***/ "../node_modules/core-js/library/modules/_export.js":
/***/ (function(module, exports, __webpack_require__) {

var global = __webpack_require__("../node_modules/core-js/library/modules/_global.js");
var core = __webpack_require__("../node_modules/core-js/library/modules/_core.js");
var ctx = __webpack_require__("../node_modules/core-js/library/modules/_ctx.js");
var hide = __webpack_require__("../node_modules/core-js/library/modules/_hide.js");
var PROTOTYPE = 'prototype';

var $export = function (type, name, source) {
  var IS_FORCED = type & $export.F;
  var IS_GLOBAL = type & $export.G;
  var IS_STATIC = type & $export.S;
  var IS_PROTO = type & $export.P;
  var IS_BIND = type & $export.B;
  var IS_WRAP = type & $export.W;
  var exports = IS_GLOBAL ? core : core[name] || (core[name] = {});
  var expProto = exports[PROTOTYPE];
  var target = IS_GLOBAL ? global : IS_STATIC ? global[name] : (global[name] || {})[PROTOTYPE];
  var key, own, out;
  if (IS_GLOBAL) source = name;
  for (key in source) {
    // contains in native
    own = !IS_FORCED && target && target[key] !== undefined;
    if (own && key in exports) continue;
    // export native or passed
    out = own ? target[key] : source[key];
    // prevent global pollution for namespaces
    exports[key] = IS_GLOBAL && typeof target[key] != 'function' ? source[key]
    // bind timers to global for call from export context
    : IS_BIND && own ? ctx(out, global)
    // wrap global constructors for prevent change them in library
    : IS_WRAP && target[key] == out ? (function (C) {
      var F = function (a, b, c) {
        if (this instanceof C) {
          switch (arguments.length) {
            case 0: return new C();
            case 1: return new C(a);
            case 2: return new C(a, b);
          } return new C(a, b, c);
        } return C.apply(this, arguments);
      };
      F[PROTOTYPE] = C[PROTOTYPE];
      return F;
    // make static versions for prototype methods
    })(out) : IS_PROTO && typeof out == 'function' ? ctx(Function.call, out) : out;
    // export proto methods to core.%CONSTRUCTOR%.methods.%NAME%
    if (IS_PROTO) {
      (exports.virtual || (exports.virtual = {}))[key] = out;
      // export proto methods to core.%CONSTRUCTOR%.prototype.%NAME%
      if (type & $export.R && expProto && !expProto[key]) hide(expProto, key, out);
    }
  }
};
// type bitmap
$export.F = 1;   // forced
$export.G = 2;   // global
$export.S = 4;   // static
$export.P = 8;   // proto
$export.B = 16;  // bind
$export.W = 32;  // wrap
$export.U = 64;  // safe
$export.R = 128; // real proto method for `library`
module.exports = $export;


/***/ }),

/***/ "../node_modules/core-js/library/modules/_fails.js":
/***/ (function(module, exports) {

module.exports = function (exec) {
  try {
    return !!exec();
  } catch (e) {
    return true;
  }
};


/***/ }),

/***/ "../node_modules/core-js/library/modules/_global.js":
/***/ (function(module, exports) {

// https://github.com/zloirock/core-js/issues/86#issuecomment-115759028
var global = module.exports = typeof window != 'undefined' && window.Math == Math
  ? window : typeof self != 'undefined' && self.Math == Math ? self
  // eslint-disable-next-line no-new-func
  : Function('return this')();
if (typeof __g == 'number') __g = global; // eslint-disable-line no-undef


/***/ }),

/***/ "../node_modules/core-js/library/modules/_has.js":
/***/ (function(module, exports) {

var hasOwnProperty = {}.hasOwnProperty;
module.exports = function (it, key) {
  return hasOwnProperty.call(it, key);
};


/***/ }),

/***/ "../node_modules/core-js/library/modules/_hide.js":
/***/ (function(module, exports, __webpack_require__) {

var dP = __webpack_require__("../node_modules/core-js/library/modules/_object-dp.js");
var createDesc = __webpack_require__("../node_modules/core-js/library/modules/_property-desc.js");
module.exports = __webpack_require__("../node_modules/core-js/library/modules/_descriptors.js") ? function (object, key, value) {
  return dP.f(object, key, createDesc(1, value));
} : function (object, key, value) {
  object[key] = value;
  return object;
};


/***/ }),

/***/ "../node_modules/core-js/library/modules/_html.js":
/***/ (function(module, exports, __webpack_require__) {

var document = __webpack_require__("../node_modules/core-js/library/modules/_global.js").document;
module.exports = document && document.documentElement;


/***/ }),

/***/ "../node_modules/core-js/library/modules/_ie8-dom-define.js":
/***/ (function(module, exports, __webpack_require__) {

module.exports = !__webpack_require__("../node_modules/core-js/library/modules/_descriptors.js") && !__webpack_require__("../node_modules/core-js/library/modules/_fails.js")(function () {
  return Object.defineProperty(__webpack_require__("../node_modules/core-js/library/modules/_dom-create.js")('div'), 'a', { get: function () { return 7; } }).a != 7;
});


/***/ }),

/***/ "../node_modules/core-js/library/modules/_iobject.js":
/***/ (function(module, exports, __webpack_require__) {

// fallback for non-array-like ES3 and non-enumerable old V8 strings
var cof = __webpack_require__("../node_modules/core-js/library/modules/_cof.js");
// eslint-disable-next-line no-prototype-builtins
module.exports = Object('z').propertyIsEnumerable(0) ? Object : function (it) {
  return cof(it) == 'String' ? it.split('') : Object(it);
};


/***/ }),

/***/ "../node_modules/core-js/library/modules/_is-array.js":
/***/ (function(module, exports, __webpack_require__) {

// 7.2.2 IsArray(argument)
var cof = __webpack_require__("../node_modules/core-js/library/modules/_cof.js");
module.exports = Array.isArray || function isArray(arg) {
  return cof(arg) == 'Array';
};


/***/ }),

/***/ "../node_modules/core-js/library/modules/_is-object.js":
/***/ (function(module, exports) {

module.exports = function (it) {
  return typeof it === 'object' ? it !== null : typeof it === 'function';
};


/***/ }),

/***/ "../node_modules/core-js/library/modules/_iter-create.js":
/***/ (function(module, exports, __webpack_require__) {

"use strict";

var create = __webpack_require__("../node_modules/core-js/library/modules/_object-create.js");
var descriptor = __webpack_require__("../node_modules/core-js/library/modules/_property-desc.js");
var setToStringTag = __webpack_require__("../node_modules/core-js/library/modules/_set-to-string-tag.js");
var IteratorPrototype = {};

// 25.1.2.1.1 %IteratorPrototype%[@@iterator]()
__webpack_require__("../node_modules/core-js/library/modules/_hide.js")(IteratorPrototype, __webpack_require__("../node_modules/core-js/library/modules/_wks.js")('iterator'), function () { return this; });

module.exports = function (Constructor, NAME, next) {
  Constructor.prototype = create(IteratorPrototype, { next: descriptor(1, next) });
  setToStringTag(Constructor, NAME + ' Iterator');
};


/***/ }),

/***/ "../node_modules/core-js/library/modules/_iter-define.js":
/***/ (function(module, exports, __webpack_require__) {

"use strict";

var LIBRARY = __webpack_require__("../node_modules/core-js/library/modules/_library.js");
var $export = __webpack_require__("../node_modules/core-js/library/modules/_export.js");
var redefine = __webpack_require__("../node_modules/core-js/library/modules/_redefine.js");
var hide = __webpack_require__("../node_modules/core-js/library/modules/_hide.js");
var has = __webpack_require__("../node_modules/core-js/library/modules/_has.js");
var Iterators = __webpack_require__("../node_modules/core-js/library/modules/_iterators.js");
var $iterCreate = __webpack_require__("../node_modules/core-js/library/modules/_iter-create.js");
var setToStringTag = __webpack_require__("../node_modules/core-js/library/modules/_set-to-string-tag.js");
var getPrototypeOf = __webpack_require__("../node_modules/core-js/library/modules/_object-gpo.js");
var ITERATOR = __webpack_require__("../node_modules/core-js/library/modules/_wks.js")('iterator');
var BUGGY = !([].keys && 'next' in [].keys()); // Safari has buggy iterators w/o `next`
var FF_ITERATOR = '@@iterator';
var KEYS = 'keys';
var VALUES = 'values';

var returnThis = function () { return this; };

module.exports = function (Base, NAME, Constructor, next, DEFAULT, IS_SET, FORCED) {
  $iterCreate(Constructor, NAME, next);
  var getMethod = function (kind) {
    if (!BUGGY && kind in proto) return proto[kind];
    switch (kind) {
      case KEYS: return function keys() { return new Constructor(this, kind); };
      case VALUES: return function values() { return new Constructor(this, kind); };
    } return function entries() { return new Constructor(this, kind); };
  };
  var TAG = NAME + ' Iterator';
  var DEF_VALUES = DEFAULT == VALUES;
  var VALUES_BUG = false;
  var proto = Base.prototype;
  var $native = proto[ITERATOR] || proto[FF_ITERATOR] || DEFAULT && proto[DEFAULT];
  var $default = $native || getMethod(DEFAULT);
  var $entries = DEFAULT ? !DEF_VALUES ? $default : getMethod('entries') : undefined;
  var $anyNative = NAME == 'Array' ? proto.entries || $native : $native;
  var methods, key, IteratorPrototype;
  // Fix native
  if ($anyNative) {
    IteratorPrototype = getPrototypeOf($anyNative.call(new Base()));
    if (IteratorPrototype !== Object.prototype && IteratorPrototype.next) {
      // Set @@toStringTag to native iterators
      setToStringTag(IteratorPrototype, TAG, true);
      // fix for some old engines
      if (!LIBRARY && !has(IteratorPrototype, ITERATOR)) hide(IteratorPrototype, ITERATOR, returnThis);
    }
  }
  // fix Array#{values, @@iterator}.name in V8 / FF
  if (DEF_VALUES && $native && $native.name !== VALUES) {
    VALUES_BUG = true;
    $default = function values() { return $native.call(this); };
  }
  // Define iterator
  if ((!LIBRARY || FORCED) && (BUGGY || VALUES_BUG || !proto[ITERATOR])) {
    hide(proto, ITERATOR, $default);
  }
  // Plug for library
  Iterators[NAME] = $default;
  Iterators[TAG] = returnThis;
  if (DEFAULT) {
    methods = {
      values: DEF_VALUES ? $default : getMethod(VALUES),
      keys: IS_SET ? $default : getMethod(KEYS),
      entries: $entries
    };
    if (FORCED) for (key in methods) {
      if (!(key in proto)) redefine(proto, key, methods[key]);
    } else $export($export.P + $export.F * (BUGGY || VALUES_BUG), NAME, methods);
  }
  return methods;
};


/***/ }),

/***/ "../node_modules/core-js/library/modules/_iter-step.js":
/***/ (function(module, exports) {

module.exports = function (done, value) {
  return { value: value, done: !!done };
};


/***/ }),

/***/ "../node_modules/core-js/library/modules/_iterators.js":
/***/ (function(module, exports) {

module.exports = {};


/***/ }),

/***/ "../node_modules/core-js/library/modules/_library.js":
/***/ (function(module, exports) {

module.exports = true;


/***/ }),

/***/ "../node_modules/core-js/library/modules/_meta.js":
/***/ (function(module, exports, __webpack_require__) {

var META = __webpack_require__("../node_modules/core-js/library/modules/_uid.js")('meta');
var isObject = __webpack_require__("../node_modules/core-js/library/modules/_is-object.js");
var has = __webpack_require__("../node_modules/core-js/library/modules/_has.js");
var setDesc = __webpack_require__("../node_modules/core-js/library/modules/_object-dp.js").f;
var id = 0;
var isExtensible = Object.isExtensible || function () {
  return true;
};
var FREEZE = !__webpack_require__("../node_modules/core-js/library/modules/_fails.js")(function () {
  return isExtensible(Object.preventExtensions({}));
});
var setMeta = function (it) {
  setDesc(it, META, { value: {
    i: 'O' + ++id, // object ID
    w: {}          // weak collections IDs
  } });
};
var fastKey = function (it, create) {
  // return primitive with prefix
  if (!isObject(it)) return typeof it == 'symbol' ? it : (typeof it == 'string' ? 'S' : 'P') + it;
  if (!has(it, META)) {
    // can't set metadata to uncaught frozen object
    if (!isExtensible(it)) return 'F';
    // not necessary to add metadata
    if (!create) return 'E';
    // add missing metadata
    setMeta(it);
  // return object ID
  } return it[META].i;
};
var getWeak = function (it, create) {
  if (!has(it, META)) {
    // can't set metadata to uncaught frozen object
    if (!isExtensible(it)) return true;
    // not necessary to add metadata
    if (!create) return false;
    // add missing metadata
    setMeta(it);
  // return hash weak collections IDs
  } return it[META].w;
};
// add metadata on freeze-family methods calling
var onFreeze = function (it) {
  if (FREEZE && meta.NEED && isExtensible(it) && !has(it, META)) setMeta(it);
  return it;
};
var meta = module.exports = {
  KEY: META,
  NEED: false,
  fastKey: fastKey,
  getWeak: getWeak,
  onFreeze: onFreeze
};


/***/ }),

/***/ "../node_modules/core-js/library/modules/_object-create.js":
/***/ (function(module, exports, __webpack_require__) {

// 19.1.2.2 / 15.2.3.5 Object.create(O [, Properties])
var anObject = __webpack_require__("../node_modules/core-js/library/modules/_an-object.js");
var dPs = __webpack_require__("../node_modules/core-js/library/modules/_object-dps.js");
var enumBugKeys = __webpack_require__("../node_modules/core-js/library/modules/_enum-bug-keys.js");
var IE_PROTO = __webpack_require__("../node_modules/core-js/library/modules/_shared-key.js")('IE_PROTO');
var Empty = function () { /* empty */ };
var PROTOTYPE = 'prototype';

// Create object with fake `null` prototype: use iframe Object with cleared prototype
var createDict = function () {
  // Thrash, waste and sodomy: IE GC bug
  var iframe = __webpack_require__("../node_modules/core-js/library/modules/_dom-create.js")('iframe');
  var i = enumBugKeys.length;
  var lt = '<';
  var gt = '>';
  var iframeDocument;
  iframe.style.display = 'none';
  __webpack_require__("../node_modules/core-js/library/modules/_html.js").appendChild(iframe);
  iframe.src = 'javascript:'; // eslint-disable-line no-script-url
  // createDict = iframe.contentWindow.Object;
  // html.removeChild(iframe);
  iframeDocument = iframe.contentWindow.document;
  iframeDocument.open();
  iframeDocument.write(lt + 'script' + gt + 'document.F=Object' + lt + '/script' + gt);
  iframeDocument.close();
  createDict = iframeDocument.F;
  while (i--) delete createDict[PROTOTYPE][enumBugKeys[i]];
  return createDict();
};

module.exports = Object.create || function create(O, Properties) {
  var result;
  if (O !== null) {
    Empty[PROTOTYPE] = anObject(O);
    result = new Empty();
    Empty[PROTOTYPE] = null;
    // add "__proto__" for Object.getPrototypeOf polyfill
    result[IE_PROTO] = O;
  } else result = createDict();
  return Properties === undefined ? result : dPs(result, Properties);
};


/***/ }),

/***/ "../node_modules/core-js/library/modules/_object-dp.js":
/***/ (function(module, exports, __webpack_require__) {

var anObject = __webpack_require__("../node_modules/core-js/library/modules/_an-object.js");
var IE8_DOM_DEFINE = __webpack_require__("../node_modules/core-js/library/modules/_ie8-dom-define.js");
var toPrimitive = __webpack_require__("../node_modules/core-js/library/modules/_to-primitive.js");
var dP = Object.defineProperty;

exports.f = __webpack_require__("../node_modules/core-js/library/modules/_descriptors.js") ? Object.defineProperty : function defineProperty(O, P, Attributes) {
  anObject(O);
  P = toPrimitive(P, true);
  anObject(Attributes);
  if (IE8_DOM_DEFINE) try {
    return dP(O, P, Attributes);
  } catch (e) { /* empty */ }
  if ('get' in Attributes || 'set' in Attributes) throw TypeError('Accessors not supported!');
  if ('value' in Attributes) O[P] = Attributes.value;
  return O;
};


/***/ }),

/***/ "../node_modules/core-js/library/modules/_object-dps.js":
/***/ (function(module, exports, __webpack_require__) {

var dP = __webpack_require__("../node_modules/core-js/library/modules/_object-dp.js");
var anObject = __webpack_require__("../node_modules/core-js/library/modules/_an-object.js");
var getKeys = __webpack_require__("../node_modules/core-js/library/modules/_object-keys.js");

module.exports = __webpack_require__("../node_modules/core-js/library/modules/_descriptors.js") ? Object.defineProperties : function defineProperties(O, Properties) {
  anObject(O);
  var keys = getKeys(Properties);
  var length = keys.length;
  var i = 0;
  var P;
  while (length > i) dP.f(O, P = keys[i++], Properties[P]);
  return O;
};


/***/ }),

/***/ "../node_modules/core-js/library/modules/_object-gopd.js":
/***/ (function(module, exports, __webpack_require__) {

var pIE = __webpack_require__("../node_modules/core-js/library/modules/_object-pie.js");
var createDesc = __webpack_require__("../node_modules/core-js/library/modules/_property-desc.js");
var toIObject = __webpack_require__("../node_modules/core-js/library/modules/_to-iobject.js");
var toPrimitive = __webpack_require__("../node_modules/core-js/library/modules/_to-primitive.js");
var has = __webpack_require__("../node_modules/core-js/library/modules/_has.js");
var IE8_DOM_DEFINE = __webpack_require__("../node_modules/core-js/library/modules/_ie8-dom-define.js");
var gOPD = Object.getOwnPropertyDescriptor;

exports.f = __webpack_require__("../node_modules/core-js/library/modules/_descriptors.js") ? gOPD : function getOwnPropertyDescriptor(O, P) {
  O = toIObject(O);
  P = toPrimitive(P, true);
  if (IE8_DOM_DEFINE) try {
    return gOPD(O, P);
  } catch (e) { /* empty */ }
  if (has(O, P)) return createDesc(!pIE.f.call(O, P), O[P]);
};


/***/ }),

/***/ "../node_modules/core-js/library/modules/_object-gopn-ext.js":
/***/ (function(module, exports, __webpack_require__) {

// fallback for IE11 buggy Object.getOwnPropertyNames with iframe and window
var toIObject = __webpack_require__("../node_modules/core-js/library/modules/_to-iobject.js");
var gOPN = __webpack_require__("../node_modules/core-js/library/modules/_object-gopn.js").f;
var toString = {}.toString;

var windowNames = typeof window == 'object' && window && Object.getOwnPropertyNames
  ? Object.getOwnPropertyNames(window) : [];

var getWindowNames = function (it) {
  try {
    return gOPN(it);
  } catch (e) {
    return windowNames.slice();
  }
};

module.exports.f = function getOwnPropertyNames(it) {
  return windowNames && toString.call(it) == '[object Window]' ? getWindowNames(it) : gOPN(toIObject(it));
};


/***/ }),

/***/ "../node_modules/core-js/library/modules/_object-gopn.js":
/***/ (function(module, exports, __webpack_require__) {

// 19.1.2.7 / 15.2.3.4 Object.getOwnPropertyNames(O)
var $keys = __webpack_require__("../node_modules/core-js/library/modules/_object-keys-internal.js");
var hiddenKeys = __webpack_require__("../node_modules/core-js/library/modules/_enum-bug-keys.js").concat('length', 'prototype');

exports.f = Object.getOwnPropertyNames || function getOwnPropertyNames(O) {
  return $keys(O, hiddenKeys);
};


/***/ }),

/***/ "../node_modules/core-js/library/modules/_object-gops.js":
/***/ (function(module, exports) {

exports.f = Object.getOwnPropertySymbols;


/***/ }),

/***/ "../node_modules/core-js/library/modules/_object-gpo.js":
/***/ (function(module, exports, __webpack_require__) {

// 19.1.2.9 / 15.2.3.2 Object.getPrototypeOf(O)
var has = __webpack_require__("../node_modules/core-js/library/modules/_has.js");
var toObject = __webpack_require__("../node_modules/core-js/library/modules/_to-object.js");
var IE_PROTO = __webpack_require__("../node_modules/core-js/library/modules/_shared-key.js")('IE_PROTO');
var ObjectProto = Object.prototype;

module.exports = Object.getPrototypeOf || function (O) {
  O = toObject(O);
  if (has(O, IE_PROTO)) return O[IE_PROTO];
  if (typeof O.constructor == 'function' && O instanceof O.constructor) {
    return O.constructor.prototype;
  } return O instanceof Object ? ObjectProto : null;
};


/***/ }),

/***/ "../node_modules/core-js/library/modules/_object-keys-internal.js":
/***/ (function(module, exports, __webpack_require__) {

var has = __webpack_require__("../node_modules/core-js/library/modules/_has.js");
var toIObject = __webpack_require__("../node_modules/core-js/library/modules/_to-iobject.js");
var arrayIndexOf = __webpack_require__("../node_modules/core-js/library/modules/_array-includes.js")(false);
var IE_PROTO = __webpack_require__("../node_modules/core-js/library/modules/_shared-key.js")('IE_PROTO');

module.exports = function (object, names) {
  var O = toIObject(object);
  var i = 0;
  var result = [];
  var key;
  for (key in O) if (key != IE_PROTO) has(O, key) && result.push(key);
  // Don't enum bug & hidden keys
  while (names.length > i) if (has(O, key = names[i++])) {
    ~arrayIndexOf(result, key) || result.push(key);
  }
  return result;
};


/***/ }),

/***/ "../node_modules/core-js/library/modules/_object-keys.js":
/***/ (function(module, exports, __webpack_require__) {

// 19.1.2.14 / 15.2.3.14 Object.keys(O)
var $keys = __webpack_require__("../node_modules/core-js/library/modules/_object-keys-internal.js");
var enumBugKeys = __webpack_require__("../node_modules/core-js/library/modules/_enum-bug-keys.js");

module.exports = Object.keys || function keys(O) {
  return $keys(O, enumBugKeys);
};


/***/ }),

/***/ "../node_modules/core-js/library/modules/_object-pie.js":
/***/ (function(module, exports) {

exports.f = {}.propertyIsEnumerable;


/***/ }),

/***/ "../node_modules/core-js/library/modules/_object-sap.js":
/***/ (function(module, exports, __webpack_require__) {

// most Object methods by ES6 should accept primitives
var $export = __webpack_require__("../node_modules/core-js/library/modules/_export.js");
var core = __webpack_require__("../node_modules/core-js/library/modules/_core.js");
var fails = __webpack_require__("../node_modules/core-js/library/modules/_fails.js");
module.exports = function (KEY, exec) {
  var fn = (core.Object || {})[KEY] || Object[KEY];
  var exp = {};
  exp[KEY] = exec(fn);
  $export($export.S + $export.F * fails(function () { fn(1); }), 'Object', exp);
};


/***/ }),

/***/ "../node_modules/core-js/library/modules/_property-desc.js":
/***/ (function(module, exports) {

module.exports = function (bitmap, value) {
  return {
    enumerable: !(bitmap & 1),
    configurable: !(bitmap & 2),
    writable: !(bitmap & 4),
    value: value
  };
};


/***/ }),

/***/ "../node_modules/core-js/library/modules/_redefine.js":
/***/ (function(module, exports, __webpack_require__) {

module.exports = __webpack_require__("../node_modules/core-js/library/modules/_hide.js");


/***/ }),

/***/ "../node_modules/core-js/library/modules/_set-to-string-tag.js":
/***/ (function(module, exports, __webpack_require__) {

var def = __webpack_require__("../node_modules/core-js/library/modules/_object-dp.js").f;
var has = __webpack_require__("../node_modules/core-js/library/modules/_has.js");
var TAG = __webpack_require__("../node_modules/core-js/library/modules/_wks.js")('toStringTag');

module.exports = function (it, tag, stat) {
  if (it && !has(it = stat ? it : it.prototype, TAG)) def(it, TAG, { configurable: true, value: tag });
};


/***/ }),

/***/ "../node_modules/core-js/library/modules/_shared-key.js":
/***/ (function(module, exports, __webpack_require__) {

var shared = __webpack_require__("../node_modules/core-js/library/modules/_shared.js")('keys');
var uid = __webpack_require__("../node_modules/core-js/library/modules/_uid.js");
module.exports = function (key) {
  return shared[key] || (shared[key] = uid(key));
};


/***/ }),

/***/ "../node_modules/core-js/library/modules/_shared.js":
/***/ (function(module, exports, __webpack_require__) {

var global = __webpack_require__("../node_modules/core-js/library/modules/_global.js");
var SHARED = '__core-js_shared__';
var store = global[SHARED] || (global[SHARED] = {});
module.exports = function (key) {
  return store[key] || (store[key] = {});
};


/***/ }),

/***/ "../node_modules/core-js/library/modules/_string-at.js":
/***/ (function(module, exports, __webpack_require__) {

var toInteger = __webpack_require__("../node_modules/core-js/library/modules/_to-integer.js");
var defined = __webpack_require__("../node_modules/core-js/library/modules/_defined.js");
// true  -> String#at
// false -> String#codePointAt
module.exports = function (TO_STRING) {
  return function (that, pos) {
    var s = String(defined(that));
    var i = toInteger(pos);
    var l = s.length;
    var a, b;
    if (i < 0 || i >= l) return TO_STRING ? '' : undefined;
    a = s.charCodeAt(i);
    return a < 0xd800 || a > 0xdbff || i + 1 === l || (b = s.charCodeAt(i + 1)) < 0xdc00 || b > 0xdfff
      ? TO_STRING ? s.charAt(i) : a
      : TO_STRING ? s.slice(i, i + 2) : (a - 0xd800 << 10) + (b - 0xdc00) + 0x10000;
  };
};


/***/ }),

/***/ "../node_modules/core-js/library/modules/_to-absolute-index.js":
/***/ (function(module, exports, __webpack_require__) {

var toInteger = __webpack_require__("../node_modules/core-js/library/modules/_to-integer.js");
var max = Math.max;
var min = Math.min;
module.exports = function (index, length) {
  index = toInteger(index);
  return index < 0 ? max(index + length, 0) : min(index, length);
};


/***/ }),

/***/ "../node_modules/core-js/library/modules/_to-integer.js":
/***/ (function(module, exports) {

// 7.1.4 ToInteger
var ceil = Math.ceil;
var floor = Math.floor;
module.exports = function (it) {
  return isNaN(it = +it) ? 0 : (it > 0 ? floor : ceil)(it);
};


/***/ }),

/***/ "../node_modules/core-js/library/modules/_to-iobject.js":
/***/ (function(module, exports, __webpack_require__) {

// to indexed object, toObject with fallback for non-array-like ES3 strings
var IObject = __webpack_require__("../node_modules/core-js/library/modules/_iobject.js");
var defined = __webpack_require__("../node_modules/core-js/library/modules/_defined.js");
module.exports = function (it) {
  return IObject(defined(it));
};


/***/ }),

/***/ "../node_modules/core-js/library/modules/_to-length.js":
/***/ (function(module, exports, __webpack_require__) {

// 7.1.15 ToLength
var toInteger = __webpack_require__("../node_modules/core-js/library/modules/_to-integer.js");
var min = Math.min;
module.exports = function (it) {
  return it > 0 ? min(toInteger(it), 0x1fffffffffffff) : 0; // pow(2, 53) - 1 == 9007199254740991
};


/***/ }),

/***/ "../node_modules/core-js/library/modules/_to-object.js":
/***/ (function(module, exports, __webpack_require__) {

// 7.1.13 ToObject(argument)
var defined = __webpack_require__("../node_modules/core-js/library/modules/_defined.js");
module.exports = function (it) {
  return Object(defined(it));
};


/***/ }),

/***/ "../node_modules/core-js/library/modules/_to-primitive.js":
/***/ (function(module, exports, __webpack_require__) {

// 7.1.1 ToPrimitive(input [, PreferredType])
var isObject = __webpack_require__("../node_modules/core-js/library/modules/_is-object.js");
// instead of the ES6 spec version, we didn't implement @@toPrimitive case
// and the second argument - flag - preferred type is a string
module.exports = function (it, S) {
  if (!isObject(it)) return it;
  var fn, val;
  if (S && typeof (fn = it.toString) == 'function' && !isObject(val = fn.call(it))) return val;
  if (typeof (fn = it.valueOf) == 'function' && !isObject(val = fn.call(it))) return val;
  if (!S && typeof (fn = it.toString) == 'function' && !isObject(val = fn.call(it))) return val;
  throw TypeError("Can't convert object to primitive value");
};


/***/ }),

/***/ "../node_modules/core-js/library/modules/_uid.js":
/***/ (function(module, exports) {

var id = 0;
var px = Math.random();
module.exports = function (key) {
  return 'Symbol('.concat(key === undefined ? '' : key, ')_', (++id + px).toString(36));
};


/***/ }),

/***/ "../node_modules/core-js/library/modules/_wks-define.js":
/***/ (function(module, exports, __webpack_require__) {

var global = __webpack_require__("../node_modules/core-js/library/modules/_global.js");
var core = __webpack_require__("../node_modules/core-js/library/modules/_core.js");
var LIBRARY = __webpack_require__("../node_modules/core-js/library/modules/_library.js");
var wksExt = __webpack_require__("../node_modules/core-js/library/modules/_wks-ext.js");
var defineProperty = __webpack_require__("../node_modules/core-js/library/modules/_object-dp.js").f;
module.exports = function (name) {
  var $Symbol = core.Symbol || (core.Symbol = LIBRARY ? {} : global.Symbol || {});
  if (name.charAt(0) != '_' && !(name in $Symbol)) defineProperty($Symbol, name, { value: wksExt.f(name) });
};


/***/ }),

/***/ "../node_modules/core-js/library/modules/_wks-ext.js":
/***/ (function(module, exports, __webpack_require__) {

exports.f = __webpack_require__("../node_modules/core-js/library/modules/_wks.js");


/***/ }),

/***/ "../node_modules/core-js/library/modules/_wks.js":
/***/ (function(module, exports, __webpack_require__) {

var store = __webpack_require__("../node_modules/core-js/library/modules/_shared.js")('wks');
var uid = __webpack_require__("../node_modules/core-js/library/modules/_uid.js");
var Symbol = __webpack_require__("../node_modules/core-js/library/modules/_global.js").Symbol;
var USE_SYMBOL = typeof Symbol == 'function';

var $exports = module.exports = function (name) {
  return store[name] || (store[name] =
    USE_SYMBOL && Symbol[name] || (USE_SYMBOL ? Symbol : uid)('Symbol.' + name));
};

$exports.store = store;


/***/ }),

/***/ "../node_modules/core-js/library/modules/es6.array.iterator.js":
/***/ (function(module, exports, __webpack_require__) {

"use strict";

var addToUnscopables = __webpack_require__("../node_modules/core-js/library/modules/_add-to-unscopables.js");
var step = __webpack_require__("../node_modules/core-js/library/modules/_iter-step.js");
var Iterators = __webpack_require__("../node_modules/core-js/library/modules/_iterators.js");
var toIObject = __webpack_require__("../node_modules/core-js/library/modules/_to-iobject.js");

// 22.1.3.4 Array.prototype.entries()
// 22.1.3.13 Array.prototype.keys()
// 22.1.3.29 Array.prototype.values()
// 22.1.3.30 Array.prototype[@@iterator]()
module.exports = __webpack_require__("../node_modules/core-js/library/modules/_iter-define.js")(Array, 'Array', function (iterated, kind) {
  this._t = toIObject(iterated); // target
  this._i = 0;                   // next index
  this._k = kind;                // kind
// 22.1.5.2.1 %ArrayIteratorPrototype%.next()
}, function () {
  var O = this._t;
  var kind = this._k;
  var index = this._i++;
  if (!O || index >= O.length) {
    this._t = undefined;
    return step(1);
  }
  if (kind == 'keys') return step(0, index);
  if (kind == 'values') return step(0, O[index]);
  return step(0, [index, O[index]]);
}, 'values');

// argumentsList[@@iterator] is %ArrayProto_values% (9.4.4.6, 9.4.4.7)
Iterators.Arguments = Iterators.Array;

addToUnscopables('keys');
addToUnscopables('values');
addToUnscopables('entries');


/***/ }),

/***/ "../node_modules/core-js/library/modules/es6.object.keys.js":
/***/ (function(module, exports, __webpack_require__) {

// 19.1.2.14 Object.keys(O)
var toObject = __webpack_require__("../node_modules/core-js/library/modules/_to-object.js");
var $keys = __webpack_require__("../node_modules/core-js/library/modules/_object-keys.js");

__webpack_require__("../node_modules/core-js/library/modules/_object-sap.js")('keys', function () {
  return function keys(it) {
    return $keys(toObject(it));
  };
});


/***/ }),

/***/ "../node_modules/core-js/library/modules/es6.object.to-string.js":
/***/ (function(module, exports) {



/***/ }),

/***/ "../node_modules/core-js/library/modules/es6.string.iterator.js":
/***/ (function(module, exports, __webpack_require__) {

"use strict";

var $at = __webpack_require__("../node_modules/core-js/library/modules/_string-at.js")(true);

// 21.1.3.27 String.prototype[@@iterator]()
__webpack_require__("../node_modules/core-js/library/modules/_iter-define.js")(String, 'String', function (iterated) {
  this._t = String(iterated); // target
  this._i = 0;                // next index
// 21.1.5.2.1 %StringIteratorPrototype%.next()
}, function () {
  var O = this._t;
  var index = this._i;
  var point;
  if (index >= O.length) return { value: undefined, done: true };
  point = $at(O, index);
  this._i += point.length;
  return { value: point, done: false };
});


/***/ }),

/***/ "../node_modules/core-js/library/modules/es6.symbol.js":
/***/ (function(module, exports, __webpack_require__) {

"use strict";

// ECMAScript 6 symbols shim
var global = __webpack_require__("../node_modules/core-js/library/modules/_global.js");
var has = __webpack_require__("../node_modules/core-js/library/modules/_has.js");
var DESCRIPTORS = __webpack_require__("../node_modules/core-js/library/modules/_descriptors.js");
var $export = __webpack_require__("../node_modules/core-js/library/modules/_export.js");
var redefine = __webpack_require__("../node_modules/core-js/library/modules/_redefine.js");
var META = __webpack_require__("../node_modules/core-js/library/modules/_meta.js").KEY;
var $fails = __webpack_require__("../node_modules/core-js/library/modules/_fails.js");
var shared = __webpack_require__("../node_modules/core-js/library/modules/_shared.js");
var setToStringTag = __webpack_require__("../node_modules/core-js/library/modules/_set-to-string-tag.js");
var uid = __webpack_require__("../node_modules/core-js/library/modules/_uid.js");
var wks = __webpack_require__("../node_modules/core-js/library/modules/_wks.js");
var wksExt = __webpack_require__("../node_modules/core-js/library/modules/_wks-ext.js");
var wksDefine = __webpack_require__("../node_modules/core-js/library/modules/_wks-define.js");
var enumKeys = __webpack_require__("../node_modules/core-js/library/modules/_enum-keys.js");
var isArray = __webpack_require__("../node_modules/core-js/library/modules/_is-array.js");
var anObject = __webpack_require__("../node_modules/core-js/library/modules/_an-object.js");
var toIObject = __webpack_require__("../node_modules/core-js/library/modules/_to-iobject.js");
var toPrimitive = __webpack_require__("../node_modules/core-js/library/modules/_to-primitive.js");
var createDesc = __webpack_require__("../node_modules/core-js/library/modules/_property-desc.js");
var _create = __webpack_require__("../node_modules/core-js/library/modules/_object-create.js");
var gOPNExt = __webpack_require__("../node_modules/core-js/library/modules/_object-gopn-ext.js");
var $GOPD = __webpack_require__("../node_modules/core-js/library/modules/_object-gopd.js");
var $DP = __webpack_require__("../node_modules/core-js/library/modules/_object-dp.js");
var $keys = __webpack_require__("../node_modules/core-js/library/modules/_object-keys.js");
var gOPD = $GOPD.f;
var dP = $DP.f;
var gOPN = gOPNExt.f;
var $Symbol = global.Symbol;
var $JSON = global.JSON;
var _stringify = $JSON && $JSON.stringify;
var PROTOTYPE = 'prototype';
var HIDDEN = wks('_hidden');
var TO_PRIMITIVE = wks('toPrimitive');
var isEnum = {}.propertyIsEnumerable;
var SymbolRegistry = shared('symbol-registry');
var AllSymbols = shared('symbols');
var OPSymbols = shared('op-symbols');
var ObjectProto = Object[PROTOTYPE];
var USE_NATIVE = typeof $Symbol == 'function';
var QObject = global.QObject;
// Don't use setters in Qt Script, https://github.com/zloirock/core-js/issues/173
var setter = !QObject || !QObject[PROTOTYPE] || !QObject[PROTOTYPE].findChild;

// fallback for old Android, https://code.google.com/p/v8/issues/detail?id=687
var setSymbolDesc = DESCRIPTORS && $fails(function () {
  return _create(dP({}, 'a', {
    get: function () { return dP(this, 'a', { value: 7 }).a; }
  })).a != 7;
}) ? function (it, key, D) {
  var protoDesc = gOPD(ObjectProto, key);
  if (protoDesc) delete ObjectProto[key];
  dP(it, key, D);
  if (protoDesc && it !== ObjectProto) dP(ObjectProto, key, protoDesc);
} : dP;

var wrap = function (tag) {
  var sym = AllSymbols[tag] = _create($Symbol[PROTOTYPE]);
  sym._k = tag;
  return sym;
};

var isSymbol = USE_NATIVE && typeof $Symbol.iterator == 'symbol' ? function (it) {
  return typeof it == 'symbol';
} : function (it) {
  return it instanceof $Symbol;
};

var $defineProperty = function defineProperty(it, key, D) {
  if (it === ObjectProto) $defineProperty(OPSymbols, key, D);
  anObject(it);
  key = toPrimitive(key, true);
  anObject(D);
  if (has(AllSymbols, key)) {
    if (!D.enumerable) {
      if (!has(it, HIDDEN)) dP(it, HIDDEN, createDesc(1, {}));
      it[HIDDEN][key] = true;
    } else {
      if (has(it, HIDDEN) && it[HIDDEN][key]) it[HIDDEN][key] = false;
      D = _create(D, { enumerable: createDesc(0, false) });
    } return setSymbolDesc(it, key, D);
  } return dP(it, key, D);
};
var $defineProperties = function defineProperties(it, P) {
  anObject(it);
  var keys = enumKeys(P = toIObject(P));
  var i = 0;
  var l = keys.length;
  var key;
  while (l > i) $defineProperty(it, key = keys[i++], P[key]);
  return it;
};
var $create = function create(it, P) {
  return P === undefined ? _create(it) : $defineProperties(_create(it), P);
};
var $propertyIsEnumerable = function propertyIsEnumerable(key) {
  var E = isEnum.call(this, key = toPrimitive(key, true));
  if (this === ObjectProto && has(AllSymbols, key) && !has(OPSymbols, key)) return false;
  return E || !has(this, key) || !has(AllSymbols, key) || has(this, HIDDEN) && this[HIDDEN][key] ? E : true;
};
var $getOwnPropertyDescriptor = function getOwnPropertyDescriptor(it, key) {
  it = toIObject(it);
  key = toPrimitive(key, true);
  if (it === ObjectProto && has(AllSymbols, key) && !has(OPSymbols, key)) return;
  var D = gOPD(it, key);
  if (D && has(AllSymbols, key) && !(has(it, HIDDEN) && it[HIDDEN][key])) D.enumerable = true;
  return D;
};
var $getOwnPropertyNames = function getOwnPropertyNames(it) {
  var names = gOPN(toIObject(it));
  var result = [];
  var i = 0;
  var key;
  while (names.length > i) {
    if (!has(AllSymbols, key = names[i++]) && key != HIDDEN && key != META) result.push(key);
  } return result;
};
var $getOwnPropertySymbols = function getOwnPropertySymbols(it) {
  var IS_OP = it === ObjectProto;
  var names = gOPN(IS_OP ? OPSymbols : toIObject(it));
  var result = [];
  var i = 0;
  var key;
  while (names.length > i) {
    if (has(AllSymbols, key = names[i++]) && (IS_OP ? has(ObjectProto, key) : true)) result.push(AllSymbols[key]);
  } return result;
};

// 19.4.1.1 Symbol([description])
if (!USE_NATIVE) {
  $Symbol = function Symbol() {
    if (this instanceof $Symbol) throw TypeError('Symbol is not a constructor!');
    var tag = uid(arguments.length > 0 ? arguments[0] : undefined);
    var $set = function (value) {
      if (this === ObjectProto) $set.call(OPSymbols, value);
      if (has(this, HIDDEN) && has(this[HIDDEN], tag)) this[HIDDEN][tag] = false;
      setSymbolDesc(this, tag, createDesc(1, value));
    };
    if (DESCRIPTORS && setter) setSymbolDesc(ObjectProto, tag, { configurable: true, set: $set });
    return wrap(tag);
  };
  redefine($Symbol[PROTOTYPE], 'toString', function toString() {
    return this._k;
  });

  $GOPD.f = $getOwnPropertyDescriptor;
  $DP.f = $defineProperty;
  __webpack_require__("../node_modules/core-js/library/modules/_object-gopn.js").f = gOPNExt.f = $getOwnPropertyNames;
  __webpack_require__("../node_modules/core-js/library/modules/_object-pie.js").f = $propertyIsEnumerable;
  __webpack_require__("../node_modules/core-js/library/modules/_object-gops.js").f = $getOwnPropertySymbols;

  if (DESCRIPTORS && !__webpack_require__("../node_modules/core-js/library/modules/_library.js")) {
    redefine(ObjectProto, 'propertyIsEnumerable', $propertyIsEnumerable, true);
  }

  wksExt.f = function (name) {
    return wrap(wks(name));
  };
}

$export($export.G + $export.W + $export.F * !USE_NATIVE, { Symbol: $Symbol });

for (var es6Symbols = (
  // 19.4.2.2, 19.4.2.3, 19.4.2.4, 19.4.2.6, 19.4.2.8, 19.4.2.9, 19.4.2.10, 19.4.2.11, 19.4.2.12, 19.4.2.13, 19.4.2.14
  'hasInstance,isConcatSpreadable,iterator,match,replace,search,species,split,toPrimitive,toStringTag,unscopables'
).split(','), j = 0; es6Symbols.length > j;)wks(es6Symbols[j++]);

for (var wellKnownSymbols = $keys(wks.store), k = 0; wellKnownSymbols.length > k;) wksDefine(wellKnownSymbols[k++]);

$export($export.S + $export.F * !USE_NATIVE, 'Symbol', {
  // 19.4.2.1 Symbol.for(key)
  'for': function (key) {
    return has(SymbolRegistry, key += '')
      ? SymbolRegistry[key]
      : SymbolRegistry[key] = $Symbol(key);
  },
  // 19.4.2.5 Symbol.keyFor(sym)
  keyFor: function keyFor(sym) {
    if (!isSymbol(sym)) throw TypeError(sym + ' is not a symbol!');
    for (var key in SymbolRegistry) if (SymbolRegistry[key] === sym) return key;
  },
  useSetter: function () { setter = true; },
  useSimple: function () { setter = false; }
});

$export($export.S + $export.F * !USE_NATIVE, 'Object', {
  // 19.1.2.2 Object.create(O [, Properties])
  create: $create,
  // 19.1.2.4 Object.defineProperty(O, P, Attributes)
  defineProperty: $defineProperty,
  // 19.1.2.3 Object.defineProperties(O, Properties)
  defineProperties: $defineProperties,
  // 19.1.2.6 Object.getOwnPropertyDescriptor(O, P)
  getOwnPropertyDescriptor: $getOwnPropertyDescriptor,
  // 19.1.2.7 Object.getOwnPropertyNames(O)
  getOwnPropertyNames: $getOwnPropertyNames,
  // 19.1.2.8 Object.getOwnPropertySymbols(O)
  getOwnPropertySymbols: $getOwnPropertySymbols
});

// 24.3.2 JSON.stringify(value [, replacer [, space]])
$JSON && $export($export.S + $export.F * (!USE_NATIVE || $fails(function () {
  var S = $Symbol();
  // MS Edge converts symbol values to JSON as {}
  // WebKit converts symbol values to JSON as null
  // V8 throws on boxed symbols
  return _stringify([S]) != '[null]' || _stringify({ a: S }) != '{}' || _stringify(Object(S)) != '{}';
})), 'JSON', {
  stringify: function stringify(it) {
    if (it === undefined || isSymbol(it)) return; // IE8 returns string on undefined
    var args = [it];
    var i = 1;
    var replacer, $replacer;
    while (arguments.length > i) args.push(arguments[i++]);
    replacer = args[1];
    if (typeof replacer == 'function') $replacer = replacer;
    if ($replacer || !isArray(replacer)) replacer = function (key, value) {
      if ($replacer) value = $replacer.call(this, key, value);
      if (!isSymbol(value)) return value;
    };
    args[1] = replacer;
    return _stringify.apply($JSON, args);
  }
});

// 19.4.3.4 Symbol.prototype[@@toPrimitive](hint)
$Symbol[PROTOTYPE][TO_PRIMITIVE] || __webpack_require__("../node_modules/core-js/library/modules/_hide.js")($Symbol[PROTOTYPE], TO_PRIMITIVE, $Symbol[PROTOTYPE].valueOf);
// 19.4.3.5 Symbol.prototype[@@toStringTag]
setToStringTag($Symbol, 'Symbol');
// 20.2.1.9 Math[@@toStringTag]
setToStringTag(Math, 'Math', true);
// 24.3.3 JSON[@@toStringTag]
setToStringTag(global.JSON, 'JSON', true);


/***/ }),

/***/ "../node_modules/core-js/library/modules/es7.symbol.async-iterator.js":
/***/ (function(module, exports, __webpack_require__) {

__webpack_require__("../node_modules/core-js/library/modules/_wks-define.js")('asyncIterator');


/***/ }),

/***/ "../node_modules/core-js/library/modules/es7.symbol.observable.js":
/***/ (function(module, exports, __webpack_require__) {

__webpack_require__("../node_modules/core-js/library/modules/_wks-define.js")('observable');


/***/ }),

/***/ "../node_modules/core-js/library/modules/web.dom.iterable.js":
/***/ (function(module, exports, __webpack_require__) {

__webpack_require__("../node_modules/core-js/library/modules/es6.array.iterator.js");
var global = __webpack_require__("../node_modules/core-js/library/modules/_global.js");
var hide = __webpack_require__("../node_modules/core-js/library/modules/_hide.js");
var Iterators = __webpack_require__("../node_modules/core-js/library/modules/_iterators.js");
var TO_STRING_TAG = __webpack_require__("../node_modules/core-js/library/modules/_wks.js")('toStringTag');

var DOMIterables = ('CSSRuleList,CSSStyleDeclaration,CSSValueList,ClientRectList,DOMRectList,DOMStringList,' +
  'DOMTokenList,DataTransferItemList,FileList,HTMLAllCollection,HTMLCollection,HTMLFormElement,HTMLSelectElement,' +
  'MediaList,MimeTypeArray,NamedNodeMap,NodeList,PaintRequestList,Plugin,PluginArray,SVGLengthList,SVGNumberList,' +
  'SVGPathSegList,SVGPointList,SVGStringList,SVGTransformList,SourceBufferList,StyleSheetList,TextTrackCueList,' +
  'TextTrackList,TouchList').split(',');

for (var i = 0; i < DOMIterables.length; i++) {
  var NAME = DOMIterables[i];
  var Collection = global[NAME];
  var proto = Collection && Collection.prototype;
  if (proto && !proto[TO_STRING_TAG]) hide(proto, TO_STRING_TAG, NAME);
  Iterators[NAME] = Iterators.Array;
}


/***/ }),

/***/ "../node_modules/webpack/buildin/global.js":
/***/ (function(module, exports) {

var g;

// This works in non-strict mode
g = (function() {
	return this;
})();

try {
	// This works if eval is allowed (see CSP)
	g = g || Function("return this")() || (1,eval)("this");
} catch(e) {
	// This works if the window reference is available
	if(typeof window === "object")
		g = window;
}

// g can still be undefined, but nothing to do about it...
// We return undefined, instead of nothing here, so it's
// easier to handle this case. if(!global) { ...}

module.exports = g;


/***/ }),

/***/ "jquery":
/***/ (function(module, exports) {

module.exports = $;

/***/ }),

/***/ "uikit":
/***/ (function(module, exports) {

module.exports = UIkit;

/***/ })

/******/ });
//# sourceMappingURL=simple.js.map