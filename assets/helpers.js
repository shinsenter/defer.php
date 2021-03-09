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

    // HTML element
    var _domHtml = document.documentElement;

    // Backup jQuery.ready
    var _jqueryReady;

    // Youtube ID parser
    var _regexYoutubeId = /(?:youtube(?:-nocookie)?\.com\/(?:[^/\n\s]+\/\S+\/|(?:v|e(?:mbed)?)\/|\S*?[?&]v=)|youtu\.be\/)([a-zA-Z0-9_-]{11})/;

    // Common texts
    var _txtAttribute   = 'Attribute';
    var _txtDataLayer   = 'dataLayer';
    var _txtDataPrefix  = 'data-';
    var _txtDeferClass  = 'deferjs';
    var _txtDeferPrefix = 'defer-';
    var _txtLazied      = 'lazied';
    var _txtMedia       = 'media';

    // Common attributes
    var _attrClassName  = 'className';
    var _attrDataIgnore = 'data-ignore';

    // Common CSS selectors
    var _queryIgnore = ':not([' + _attrDataIgnore + ']):not([lazied])';
    var _queryTarget =
        '[' + _txtDataPrefix + 'src]' + _queryIgnore + ',' +
        '[' + _txtDataPrefix + 'srcset]' + _queryIgnore + ',' +
        '[' + _txtDataPrefix + 'style]' + _queryIgnore;

    // Common class names
    var _classLazied  = _txtDeferPrefix + _txtLazied;
    var _classLoaded  = _txtDeferPrefix + 'loaded';
    var _classLoading = _txtDeferPrefix + 'loading';

    // Common method names
    var _addEventListener = 'addEventListener';
    var _getAttribute = 'get' + _txtAttribute;
    var _hasAttribute = 'has' + _txtAttribute;

    /*
    |--------------------------------------------------------------------------
    | Check for defer.js
    |--------------------------------------------------------------------------
    */

    var defer    = window.Defer;
    var _delay   = window.DEFERJS_DELAY || 8;
    var _options = window.DEFERJS_OPTIONS || {rootMargin: '150%'};

    /*
    |--------------------------------------------------------------------------
    | Internal functions
    |--------------------------------------------------------------------------
    */

    function _getClass(node, find) {
        return node[_attrClassName].
            split(' ').
            filter(function (name) {
                return name != '' && name != find;
            });
    }

    function _addClass(node, name, _tmp) {
        _tmp = _getClass(node, name);
        _tmp.push(name);
        node[_attrClassName] = _tmp.join(' ');
    }

    function _removeClass(node, name) {
        node[_attrClassName] = _getClass(node, name).join(' ');
    }

    function _lazyload() {
        defer.dom(_queryTarget, 0, _classLazied, function (element, _match, _loaded, _src, _placeholder) {
            // Loading state
            _addClass(element, _classLoading);

            // Add youtube placeholder
            _src = element[_getAttribute](_txtDataPrefix + 'src');
            _placeholder = element[_getAttribute]('src');
            if (_src && (_match = _regexYoutubeId.exec(_src))) {
                element.style.background =
                    'transparent url(https://img.youtube.com/vi/' +
                    _match[1] +
                    '/hqdefault.jpg) 50% 50% / cover no-repeat';
            }

            function _onLoad() {
                if (!_loaded) {
                    _loaded = true;
                    defer.reveal(element);
                    _removeClass(element, _classLoading);
                    _addClass(element, _classLoaded);
                }
            }

            // Update loaded state
            if (element[_hasAttribute](_attrDataIgnore) ||
                _src == _placeholder ||
                !_src) {
                _onLoad();
            } else {
                defer(_onLoad, 3000);
                element[_addEventListener]('error', _onLoad);
                element[_addEventListener]('load', _onLoad);
            }
        }, _options);

        [].slice.call(document.querySelectorAll('style[defer]')).
            forEach(function(node){
                node[_txtMedia] = node[_getAttribute](_txtDataPrefix + _txtMedia) || 'all';
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
        _copyright();
        defer(_lazyload, _delay);
    }

    /*
    |--------------------------------------------------------------------------
    | Define helper object
    |--------------------------------------------------------------------------
    */

    // Remove no-deferjs class
    _removeClass(_domHtml, 'no-' + _txtDeferClass);

    // Check if missing defer feature
    if (!defer) {return;}

    // Fallback for older versions
    window.defer_helper = {defermedia: _lazyload};

    /*
    |--------------------------------------------------------------------------
    | Fallback for external libraries
    |--------------------------------------------------------------------------
    */

    // Fix missing dataLayer (for Google Analytics)
    // See: https://developers.google.com/analytics/devguides/collection/analyticsjs
    window.ga = window.ga || function () {(window.ga.q = window.ga.q || []).push(arguments)}; window.ga.l = Number(new Date());
    window[_txtDataLayer] = window[_txtDataLayer] || [];

    // Fake jQuery.ready, if jQuery loaded
    defer(function (jquery) {
        if (!_jqueryReady && (jquery = window.jQuery) && jquery.fn) {
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

    _addClass(_domHtml, _txtDeferClass);
    _boot();

})(this, document, console);
