/**
 *
 * Package shinsenter/defer.php
 * https://github.com/shinsenter/defer.php
 *
 * Minified by UglifyJS3
 * http://lisperator.net/uglifyjs/
 *
 * Released under the MIT license
 * https://raw.githubusercontent.com/shinsenter/defer.js/master/LICENSE
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

(function(window, document, console, name) {

    var NOOP            = Function();
    var GET_ATTRIBUTE   = 'getAttribute';
    var REM_ATTRIBUTE   = 'removeAttribute';
    var IS_CHROME       = typeof window.chrome == 'object' && window.navigator.userAgent.indexOf('Trident/') == -1;

    var COMMON_EXCEPTIONS   = ':not([data-lazied])';
    var COMMON_SELECTOR     = '[data-src]' + COMMON_EXCEPTIONS;

    var IMG_SELECTOR = [
        'img' + COMMON_SELECTOR,
        '[data-style]' + COMMON_EXCEPTIONS
    ].join(',');

    var IFRAME_SELECTOR = [
        'iframe' + COMMON_SELECTOR,
        'frame' + COMMON_SELECTOR,
        'video' + COMMON_SELECTOR
    ].join(',');

    var helper = {
        c: 'defer-lazied',
        l: 'defer-loading',
        d: 'defer-loaded',
        h: document.getElementsByTagName('html').item(0),
        t: 10
    };

    var log         = (console.log || NOOP).bind(console);
    var defer       = window.defer || NOOP;
    var deferimg    = window.deferimg || NOOP;
    var deferiframe = window.deferiframe || NOOP;

    function copyright () {
        var text    = '%c shinsenter %c defer.js ';
        var common  = 'font-size:16px;color:#fff;padding:2px;border-radius:';
        var style1  = common + '4px 0 0 4px;background:#2a313c';
        var style2  = common + '0 4px 4px 0;background:#e61e25';

        if (IS_CHROME) {
            log(text, style1, style2);
        }

        log([
            'This page was optimized with defer.js',
            '(c) 2019 Mai Nhut Tan <shin@shin.company>',
            '',
            'Github:    https://github.com/shinsenter/defer.js/',
            'PHP lib:   https://github.com/shinsenter/defer.php/',
            'WordPress: https://wordpress.org/plugins/shins-pageload-magic/'
        ].join('\n'));
    }

    /**
     * This function aims to provide both function
     * throttling and debouncing in as few bytes as possible.
     *
     * @param   {function}  func        The file URL
     * @param   {integer}   delay       The delay time to create the tag
     * @param   {boolean}   throttle    Set false to debounce, true to throttle
     * @param   {integer}   ticker      Placeholder for holding timer
     * @returns {function}              Return a new function
     */
    function debounce(func, delay, throttle, ticker) {
        return function() {
            var context = this;
            var args    = arguments;

            if (!throttle) {
                clearTimeout(ticker);
            }

            if (!throttle || !ticker) {
                ticker = setTimeout(function() {
                    ticker = null;
                    func.apply(context, args);
                }, delay);
            }
        }
    }

    /*
     * Add/remove element classnames
     */
    function classFilter(haystack, needle) {
        return haystack.split(' ').filter(function(v) {
            return v != '' && v != needle;
        });
    }

    function addClass(element, classname) {
        var c = classFilter(element.className, classname);
        c.push(classname);
        element.className=c.join(' ');
    }

    function removeClass(element, classname) {
        element.className = classFilter(element.className, classname).join(' ');
    }

    /*
     * Lazy-load img and iframe elements
     */
    function mediafilter(media) {
        var timer,
            match,
            src = media[GET_ATTRIBUTE]('data-src'),
            pattern =/(?:youtube(?:-nocookie)?\.com\/(?:[^\/\n\s]+\/\S+\/|(?:v|e(?:mbed)?)\/|\S*?[?&]v=)|youtu\.be\/)([a-zA-Z0-9_-]{11})/;

        addClass(media, helper.l);

        function onload() {
            if (timer) {
                clearTimeout(timer);
                timer = null;
            }

            removeClass(media, helper.l);
            addClass(media, helper.d);
        }

        if ((match = pattern.exec(src)) !== null) {
            media.style.background = 'transparent url(https://img.youtube.com/vi/'+match[1]+'/hqdefault.jpg) 50% 50% / cover no-repeat';
        }

        if (media.hasAttribute('data-ignore') || (src && media.src == src) || (!src && media[GET_ATTRIBUTE]('data-style'))) {
            onload();
        } else {
            media.addEventListener('load', onload);
            timer = setTimeout(onload, 3000);
        }
    }

    function imgloader() {
        deferimg(IMG_SELECTOR, helper.t, helper.c, mediafilter, {rootMargin: '100%'})
    }

    function iframeloader() {
        deferiframe(IFRAME_SELECTOR, helper.t, helper.c, mediafilter, {rootMargin: '200%'})
    }

    function defermedia() {
        imgloader();
        iframeloader();
    }

    function deferscript() {
        if('all' in defer) {
            defer.all();
        }
    }

    // Expose global methods
    helper.copyright    = copyright;
    helper.debounce     = debounce;
    helper.deferscript  = deferscript;
    helper.defermedia   = defermedia;
    helper.addClass     = addClass;
    helper.removeClass  = removeClass;

    removeClass(helper.h, 'no-deferjs');
    addClass(helper.h, 'deferjs');

    defermedia();
    copyright();

    window[name] = helper;

})(this, document, console, 'defer_helper');