<?php

/**
 * Defer.php is a PHP library that aims to help you
 * concentrate on webpage performance optimization.
 *
 * Copyright (c) 2022, AppSeeds (https://appseeds.net/)
 *
 * PHP Version >=7.3
 *
 * @package   AppSeeds\Defer
 * @category  core_web_vitals
 * @author    Mai Nhut Tan <shin@shin.company>
 * @copyright Copyright (c) 2022, AppSeeds (https://appseeds.net/)
 * @license   https://code.shin.company/defer.php/blob/master/LICENSE MIT
 * @link      https://code.shin.company/defer.php
 * @example   https://code.shin.company/defer.php/blob/master/README.md
 */

namespace AppSeeds\Defer\Utilities;

use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @property bool        $debug_mode
 * @property string|null $current_uri
 * @property string|null $deferjs_src
 * @property string|null $polyfill_src
 * @property bool        $inline_deferjs
 * @property bool        $manually_add_deferjs
 * @property bool        $add_missing_meta_tags
 * @property bool        $enable_dns_prefetch
 * @property bool        $enable_preloading
 * @property bool        $enable_lazyloading
 * @property bool        $fix_render_blocking
 * @property bool        $optimize_css
 * @property bool        $optimize_scripts
 * @property bool        $optimize_images
 * @property bool        $optimize_iframes
 * @property bool        $optimize_background
 * @property bool        $optimize_fallback
 * @property bool        $optimize_anchors
 * @property bool        $defer_third_party
 * @property bool        $use_css_fadein_effects
 * @property string|null $use_color_placeholder
 * @property string|null $img_placeholder
 * @property string|null $iframe_placeholder
 * @property string|null $custom_splash_screen
 * @property array|null  $ignore_lazyload_paths
 * @property array|null  $ignore_lazyload_texts
 * @property array|null  $ignore_lazyload_css_class
 * @property array|null  $ignore_lazyload_css_selectors
 * @property bool        $minify_output_html
 */
final class DeferOptions
{
    /**
     * @var mixed[]
     */
    public const DEFAULTS = [
        // the URI of the current request
        'current_uri' => null,

        // Insert debug information inside the output HTML after optimization.
        // Debug information will contain the outer HTML of tags before being optimized.
        // Default: false (turn off the debug information)
        'debug_mode' => false,

        // The URL of the Defer.js library JavaScript file
        // Default: https://cdn.jsdelivr.net/npm/@shinsenter/defer.js@3/dist/defer.min.js
        'deferjs_src' => 'https://cdn.jsdelivr.net/npm/@shinsenter/defer.js@3/dist/defer.min.js',

        // The URL of the IntersectionObserver polyfill JavaScript file
        // Default: https://polyfill.io/v3/polyfill.min.js?features=IntersectionObserver
        'polyfill_src' => 'https://polyfill.io/v3/polyfill.min.js?features=IntersectionObserver',

        // Inline the Defer.js library to minimize download time in the browser.
        // Default: true (always automatically inline the Defer.js library)
        'inline_deferjs' => true,

        // Although the Defer.js is the heart of this library,
        //   in some regions, you may want to serve the Defer.js library locally
        //   due to The General Data Protection Regulation (EU).
        // See: https://en.wikipedia.org/wiki/General_Data_Protection_Regulation
        // If you need to manually insert the Defer.js library yourself,
        //   please enable this option to true.
        // Default: false (always automatically insert the Defer.js library)
        'manually_add_deferjs' => false,

        // ---------------------------------------------------------------------

        // Add missing meta tags such as meta[name="viewport"], meta[charset] etc.
        // See: https://web.dev/viewport/
        // Default: true (automatically optimize)
        'add_missing_meta_tags' => true,

        // Preconnect to required URL origins.
        // See: https://web.dev/uses-rel-preconnect/
        // Default: true (automatically optimize)
        'enable_dns_prefetch' => true,

        // Preload key requests such as stylesheets or external scripts.
        // See: https://web.dev/uses-rel-preload/
        // Default: false (do not apply by default)
        'enable_preloading' => false,

        // Lazy-load all elements like images, and videos when possible.
        // See: https://web.dev/lazy-loading/
        // Default: true (automatically optimize)
        'enable_lazyloading' => true,

        // This option moves all stylesheets to the bottom of the head tag,
        //   and moves script tags to the bottom of the body tag
        // See: https://web.dev/render-blocking-resources/
        // Default: true (always automatically fix render-blocking issues)
        'fix_render_blocking' => true,

        // Turn on optimization for stylesheets
        // This option applies to style and link[rel="stylesheet"] tags.
        // Best practices: https://web.dev/extract-critical-css/
        // Default: true (automatically optimize stylesheets)
        'optimize_css' => true,

        // Optimize script tags (both inline and external scripts).
        // Note: The library only minifies for inline script tags.
        // See: https://web.dev/unminified-javascript/
        // Default: true (automatically optimize script tags)
        'optimize_scripts' => true,

        // Optimize IMG, PICTURE, VIDEO, AUDIO, and SOURCE tags.
        // See: https://web.dev/browser-level-image-lazy-loading/
        // See: https://web.dev/lazy-loading-images/
        // Default: true (automatically optimize)
        'optimize_images' => true,

        // Optimize IFRAME, FRAME, and EMBED tags.
        // See: https://web.dev/lazy-loading-video/
        // Default: true (automatically optimize)
        'optimize_iframes' => true,

        // Optimize tags that contain inline CSS links to external media.
        // For example, style properties contain background-image:url() etc.
        // See: https://web.dev/optimize-css-background-images-with-media-queries/
        // Default: true (automatically optimize)
        'optimize_background' => true,

        // Create NOSCRIPT tags so lazy-loaded elements can still display
        //   even when the browser doesn't have JavaScript enabled.
        // This option applies to all tags that have been lazy-loaded.
        // See: https://web.dev/without-javascript/
        // Default: true (automatically create fallback NOSCRIPT tags)
        'optimize_fallback' => true,

        // Optimize anchor tags, fix unsafe links to cross-origin destinations
        // See: https://web.dev/external-anchors-use-rel-noopener/
        // Default: true (automatically optimize)
        'optimize_anchors' => true,

        // ---------------------------------------------------------------------

        // Detect and optimize third-party URLs if possible (experiment).
        // This option also allows entering an array containing the URL origins to be deferred.
        // See: https://web.dev/preload-optional-fonts/
        // Default: true (automatically optimize)
        'defer_third_party' => true,

        // Apply fade-in animation to tags after being lazy-loaded.
        // Default: false (do not apply by default)
        'use_css_fadein_effects' => false,

        // Use random background colors for images to be lazy-loaded.
        // Set the value to 'grey' if you want to use greyish background colors.
        // Default: false (do not apply by default)
        'use_color_placeholder' => false,

        // ---------------------------------------------------------------------

        // Default placeholder for lazy-loaded IMG tags.
        // If this value is not set or empty,
        //   an SVG image will be used to avoid CLS-related problems.
        // See: https://web.dev/cls/
        // Default: blank string
        'img_placeholder' => '',

        // Default placeholder for lazy-loaded iframe tags.
        // Default: 'about:blank'
        'iframe_placeholder' => 'about:blank',

        // ---------------------------------------------------------------------

        // Show custom HTML for the splash screen
        //   while the browser is loading the web page (experiment).
        // Default: blank string (no splash screen)
        'custom_splash_screen' => '',

        // ---------------------------------------------------------------------

        // Do not lazy-load for URLs containing
        //   one of the array's texts (exact match keywords).
        // Default: blank array
        'ignore_lazyload_paths' => [],

        // Do not lazy-load for tags containing
        //   one of the array's texts (exact match keywords).
        // Default: blank array
        'ignore_lazyload_texts' => [],

        // Do not lazy-load for tags containing one of these CSS class names.
        // Default: blank array
        'ignore_lazyload_css_class' => [],

        // Do not lazy-load for tags matching one of these CSS selectors.
        // See: https://www.w3schools.com/cssref/css_selectors.asp
        // Default: blank array
        'ignore_lazyload_css_selectors' => [
            // 'header img',
            // 'img#logo',
        ],

        // ---------------------------------------------------------------------

        // Minify HTML output.
        // See: https://web.dev/reduce-network-payloads-using-text-compression/
        // Default: false (do not minify HTML by default)
        'minify_output_html' => false,
    ];

    /**
     * @var OptionsResolver|null
     */
    private $resolver;

    /**
     * @var array The libarary options
     */
    private $options = [];

    /*
    |--------------------------------------------------------------------------
    | Constructor
    |--------------------------------------------------------------------------
    */

    public function __construct(array $options = [])
    {
        $this->setOptions($options);
    }

    public function __call($name, $arguments)
    {
        if (false !== preg_match('/^([gs]et)([a-z0-9_])(option)?$/i', $name, $matched)) {
            $callback = [$this, 'set' == strtolower($matched[1]) ? 'setOption' : 'getOption'];
            $name     = $matched[2];
            array_unshift($arguments, $name);

            return call_user_func_array($callback, $arguments);
        }

        return $this;
    }

    public function __get($name)
    {
        return $this->getOption($name);
    }

    public function __set($name, $value)
    {
        return $this->setOption($name, $value);
    }

    /*
    |--------------------------------------------------------------------------
    | Public Methods
    |--------------------------------------------------------------------------
    */

    public function resolver()
    {
        if (empty($this->resolver)) {
            $resolver = new OptionsResolver();
            $resolver->setDefaults(static::DEFAULTS);
            $this->resolver = $resolver;
        }

        return $this->resolver;
    }

    public function setOptions(array $newOptions)
    {
        $this->options = $this->resolver()->resolve($newOptions);

        return $this;
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function setOption($name, $value)
    {
        $newOptions = [];

        if (is_array($name)) {
            $newOptions = $name;
        } elseif (is_string($name)) {
            $name       = $this->normalizeOptionName($name);
            $newOptions = [$name => $value];
        }

        $this->setOptions(array_merge($this->options, $newOptions));

        return $this;
    }

    public function getOption($name = null, $default = null)
    {
        $name = $this->normalizeOptionName($name);

        return isset($this->options[$name]) ? $this->options[$name] : $default;
    }

    public function hasOption($name = null)
    {
        $name = $this->normalizeOptionName($name);

        return isset($this->options[$name]);
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    private function normalizeOptionName($name)
    {
        if (!ctype_lower($name)) {
            return strtolower(preg_replace('/(.)(?=[A-Z])/u', '$1_', $name));
        }

        return $name;
    }
}
