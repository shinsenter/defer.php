<?php

/**
 * A PHP helper class to efficiently defer JavaScript for your website.
 * (c) 2019 AppSeeds https://appseeds.net/
 *
 * @package   shinsenter/defer.php
 * @since     1.0.0
 * @author    Mai Nhut Tan <shin@shin.company>
 * @copyright 2019 AppSeeds
 * @see       https://github.com/shinsenter/defer.php/blob/develop/README.md
 */

namespace shinsenter;

abstract class DeferInterface
{
    // Library constants
    const DEFERJS_EXPIRY = 86400;
    const DEFERJS_URL    = 'https://cdn.jsdelivr.net/npm/@shinsenter/defer.js@latest/dist/defer_plus.min.js';
    const POLYFILL_URL   = 'https://cdn.jsdelivr.net/npm/@shinsenter/defer.js@latest/dist/polyfill.min.js';
    const DEFERJS_CACHE  = __DIR__ . '/../cache/deferjs.php';

    // Library's fingerprint
    const FINGERPRINT_URL   = 'https://raw.githubusercontent.com/shinsenter/defer.js/master/src/fingerprint';
    const FINGERPRINT_CACHE = __DIR__ . '/../cache/fingerprint.php';

    // Simple fade-in effect
    const FADEIN_EFFECT = 'html.no-deferjs img[data-src]{display:none!important}' .
        '[data-src],[data-srcset]{min-width:1px;min-height:1px;display:inline-block;max-width:100%}' .
        '[data-lazied]{opacity:.1!important;background-color:transparent!important;transition:opacity .15s linear}' .
        '[data-lazied].in{opacity:1!important}';

    // Content tags
    const AUDIO_TAG    = 'audio';
    const EMBED_TAG    = 'embed';
    const FRAME_TAG    = 'frame';
    const IFRAME_TAG   = 'iframe';
    const IMG_TAG      = 'img';
    const LINK_TAG     = 'link';
    const NOSCRIPT_TAG = 'noscript';
    const SCRIPT_TAG   = 'script';
    const STYLE_TAG    = 'style';
    const VIDEO_TAG    = 'video';

    // LINK tag's rel attribute
    const REL_DNSPREFETCH = 'dns-prefetch';
    const REL_PRECONNECT  = 'preconnect';
    const REL_PRELOAD     = 'preload';
    const REL_STYLESHEET  = 'stylesheet';

    // Types of content can be preloaded
    const PRELOAD_AUDIO    = 'audio';
    const PRELOAD_DOCUMENT = 'document';
    const PRELOAD_EMBED    = 'embed';
    const PRELOAD_FETCH    = 'fetch';
    const PRELOAD_FONT     = 'font';
    const PRELOAD_IMAGE    = 'image';
    const PRELOAD_OBJECT   = 'object';
    const PRELOAD_SCRIPT   = 'script';
    const PRELOAD_STYLE    = 'style';
    const PRELOAD_TRACK    = 'track';
    const PRELOAD_VIDEO    = 'video';
    const PRELOAD_WORKER   = 'worker';

    // Tag attributes
    const ATTR_ALT         = 'alt';
    const ATTR_AS          = 'as';
    const ATTR_ASYNC       = 'async';
    const ATTR_CLASS       = 'class';
    const ATTR_CROSSORIGIN = 'crossorigin';
    const ATTR_DATA_IGNORE = 'data-ignore';
    const ATTR_DATA_SRC    = 'data-src';
    const ATTR_DATA_SRCSET = 'data-srcset';
    const ATTR_DEFER       = 'defer';
    const ATTR_HREF        = 'href';
    const ATTR_ID          = 'id';
    const ATTR_MEDIA       = 'media';
    const ATTR_ONLOAD      = 'onload';
    const ATTR_REL         = 'rel';
    const ATTR_SRC         = 'src';
    const ATTR_SRCSET      = 'srcset';
    const ATTR_TITLE       = 'title';
    const ATTR_TYPE        = 'type';

    // Xpath query expressions
    const COMMENT_XPATH = '//comment()[not(contains(.,"[if ")) and not(contains(.,"[endif]"))]';
    const DNSCONN_XPATH = '//link[@rel="dns-prefetch" or @rel="preconnect"]';
    const PRELOAD_XPATH = '//link[@rel="preload"]';
    const STYLE_XPATH   = '//style[not(@data-ignore)]|//link[not(@data-ignore) and @rel="stylesheet"]';
    const SCRIPT_XPATH  = '//script[not(@data-ignore) and (not(@type) or contains(@type,"javascript"))]';
    const IMG_XPATH     = '//*[(local-name()="img" or local-name()="video" or local-name()="source") and not(@data-ignore) and not(@data-src)]';
    const IFRAME_XPATH  = '//*[(local-name()="iframe" or local-name()="frame" or local-name()="embed") and not(@data-ignore) and not(@data-src)]';

    // Variable holders
    public static $deferjs_script = null;
    public static $fingerprint    = null;
    public static $loader_scripts = [];
}
