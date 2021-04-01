/**
 * Package shinsenter/defer.php
 * https://github.com/shinsenter/defer.php
 *
 * Released under the MIT license
 * https://raw.githubusercontent.com/shinsenter/defer.php/master/LICENSE
 *
 * MIT License
 *
 * Copyright (c) 2019 Mai Nhut Tan <shin@shin.company>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 */

(function (window, document, console) {

    /*
    |--------------------------------------------------------------------------
    | Define variables and constants
    |--------------------------------------------------------------------------
    */

    // Backup jQuery.ready
    var _jqueryReady;

    // Common texts
    var _dataLayer   = 'dataLayer';
    var _deferClass  = 'deferjs';
    var _deferPrefix = 'defer-';
    var _lazied      = 'lazied';
    var _dataPrefix  = 'data-';
    var _media       = 'media';

    // Common class names
    var _classLazied  = _deferPrefix + _lazied;
    var _classLoaded  = _deferPrefix + 'loaded';
    var _classLoading = _deferPrefix + 'loading';

    // Common attributes
    var _attrClassName  = 'className';
    var _attrDataIgnore = 'data-ignore';

    // Common CSS selectors
    var _queryTarget = '.' + _classLoading + ':not([' + _attrDataIgnore + ']):not([lazied])';

    /*
    |--------------------------------------------------------------------------
    | Check for defer.js
    |--------------------------------------------------------------------------
    */

    var defer    = window.Defer;
    var _delay   = window.DEFERJS_DELAY || 8;
    var _options = window.DEFERJS_OPTIONS || {'rootMargin': '150%'};

    /*
    |--------------------------------------------------------------------------
    | Internal functions
    |--------------------------------------------------------------------------
    */

    function _replaceClass(node, find, replace) {
        node[_attrClassName] = (' ' + node[_attrClassName] + ' ').
            replace(' ' + find + ' ',  ' ' + replace + ' ').trim();
    }

    function _lazyload() {
        defer.dom(_queryTarget, 0, _classLazied, function (node) {
            _replaceClass(node, _classLoading, _classLoaded);
        }, _options);

        [].slice.call(document.querySelectorAll('style[defer]')).
            forEach(function(node) {
                node[_media] = node.getAttribute(_dataPrefix + _media) || 'all';
            });
    }

    function _copyright(_copyText) {
        if (console.log) {
            console.log(_copyText || [
                'Optimized by defer.php',
                '(c) 2021 AppSeeds',
                'Github: https://code.shin.company/defer.php'
            ].join('\n'));
        }
    }

    function _boot() {
        defer(_lazyload, _delay);
        _replaceClass(document.documentElement, 'no-' + _deferClass, _deferClass);
        _copyright();
    }

    /*
    |--------------------------------------------------------------------------
    | Define helper object
    |--------------------------------------------------------------------------
    */

    // Check if missing defer feature
    if (!defer) {return;}

    // Fallback for older versions
    window.defer_helper = {'defermedia': _lazyload};

    /*
    |--------------------------------------------------------------------------
    | Fallback for external libraries
    |--------------------------------------------------------------------------
    */

    // Fix missing dataLayer (for Google Analytics)
    // See: https://developers.google.com/analytics/devguides/collection/analyticsjs
    window.ga = window.ga || function () {(window.ga.q = window.ga.q || []).push(arguments)}; window.ga.l = Number(Date());
    window[_dataLayer] = window[_dataLayer] || [];

    // Fake jQuery.ready, if jQuery loaded
    defer(function (jquery) {
        if (_jqueryReady) {
            return;
        }

        jquery = window.jQuery;

        if (jquery && jquery.fn) {
            _jqueryReady = jquery.fn.ready;
            jquery.fn.ready = function (callback) {
                defer(function () {_jqueryReady(callback)}, _delay);
            }
        }
    });

    /*
    |--------------------------------------------------------------------------
    | Main
    |--------------------------------------------------------------------------
    */


    _boot();

})(this, document, console);
