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

    // Common texts
    var _dataLayer   = 'dataLayer';

    // Common CSS selectors
    var _queryTarget = '.defer-loading:not([data-ignore]):not([lazied])';

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
        node.className = ((' ' + node.className + ' ').
            replace(' ' + find + ' ', ' ') + replace).trim();
    }

    /*
    |--------------------------------------------------------------------------
    | Fallback for external libraries
    |--------------------------------------------------------------------------
    */

    // Fix missing dataLayer (for Google Analytics)
    // See: https://developers.google.com/analytics/devguides/collection/analyticsjs
    window.ga = window.ga || function () {(window.ga.q = window.ga.q || []).push(arguments)}; window.ga.l = Number(Date());
    window[_dataLayer] = window[_dataLayer] || [];

    /*
    |--------------------------------------------------------------------------
    | Define helper object
    |--------------------------------------------------------------------------
    */

    _replaceClass(
        document.documentElement,
        'no-deferjs',
        defer ? 'deferjs' : ''
    );

    // Check if missing defer feature
    if (!defer) {
        return;
    }

    /*
    |--------------------------------------------------------------------------
    | Main
    |--------------------------------------------------------------------------
    */

    // Lazyload all style tags
    defer(function() {
        [].slice.call(document.querySelectorAll('style[defer]')).
            forEach(defer.reveal);
    }, _delay);

    // Lazyload all media
    defer.dom(_queryTarget, _delay, 0, function (node) {
        _replaceClass(node, 'defer-loading', 'defer-loaded');
    }, _options);

    // Copyright
    if (console.log) {
        console.log([
            'Optimized by defer.php',
            '(c) 2021 AppSeeds',
            'Github: https://code.shin.company/defer.php'
        ].join('\n'));
    }

})(this, document, console);
