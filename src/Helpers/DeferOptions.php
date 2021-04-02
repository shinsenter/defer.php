<?php

/**
 * Defer.php aims to help you concentrate on web performance optimization.
 * (c) 2021 AppSeeds https://appseeds.net/
 *
 * PHP Version >=5.6
 *
 * @category  Web_Performance_Optimization
 * @package   AppSeeds
 * @author    Mai Nhut Tan <shin@shin.company>
 * @copyright 2021 AppSeeds
 * @license   https://code.shin.company/defer.php/blob/master/LICENSE MIT
 * @link      https://code.shin.company/defer.php
 * @see       https://code.shin.company/defer.php/blob/master/README.md
 */

namespace AppSeeds\Helpers;

use Symfony\Component\OptionsResolver\OptionsResolver;

class DeferOptions
{
    protected $resolver;
    protected $options        = [];
    protected $backup_options = [];
    protected $wellknown3rd;

    /**
     * Constructor for library options
     *
     * @since 2.0.0
     */
    public function __construct(array $options = [])
    {
        $this->resolver = new OptionsResolver();
        $this->configureOptions()->setOption($options)->backup();
    }

    /**
     * Option getter
     *
     * @since 2.0.0
     * @param mixed $property
     */
    public function __get($property)
    {
        return $this->getOption($property);
    }

    /**
     * Option setter
     *
     * @since 2.0.0
     * @param mixed      $property
     * @param null|mixed $value
     */
    public function __set($property, $value = null)
    {
        $this->setOption($property, $value);
    }

    /**
     * Get option value by key
     * If no key given, return all options
     *
     * @since  2.0.0
     * @param  null|mixed $key
     * @return mixed
     */
    public function getOptionArray()
    {
        return $this->options;
    }

    /**
     * Get option value by key
     * If no key given, return all options
     *
     * @since  2.0.0
     * @param  null|mixed $key
     * @return mixed
     */
    public function getOption($key = null)
    {
        if (is_null($key)) {
            return $this->getOptionArray();
        }

        if (isset($this->options[$key])) {
            return $this->options[$key];
        }

        return null;
    }

    /**
     * Set option value by given key
     * Set multiple options by give it an array of options
     *
     * @since  2.0.0
     * @param  mixed      $key
     * @param  null|mixed $value
     * @return self
     */
    public function setOption($key, $value = null)
    {
        if (is_array($key)) {
            $options = $key;
        } else {
            $options = [$key => $value];
        }

        if (!empty($this->options)) {
            $options = array_merge($this->options, $options);
        }

        $this->options      = $this->resolver->resolve($options);
        $this->wellknown3rd = null;

        return $this;
    }

    /**
     * Backup options
     *
     * @return self
     */
    public function backup()
    {
        $this->backup_options = $this->options;

        return $this;
    }

    /**
     * Restore previous options from backup
     *
     * @since  2.0.0
     * @return self
     */
    public function restore()
    {
        $this->options = $this->backup_options;

        return $this;
    }

    /**
     * Set options for AMP page
     *
     * @return self
     */
    public function forAmp()
    {
        $this->setOption([
            'manually_add_deferjs'   => true,
            'enable_lazyloading'     => false,
            'enable_preloading'      => false,
            'fix_render_blocking'    => false,
            'defer_third_party'      => false,
            'use_css_fadein_effects' => false,
            'use_color_placeholder'  => false,
            'custom_splash_screen'   => '',
        ]);

        return $this;
    }

    /**
     * Set options from request
     *
     * @param  array $allows
     * @return self
     */
    public function mergeFromRequest($allows = [])
    {
        $flags = array_filter($this->options, function ($value) {
            return is_bool($value);
        });

        if (empty($allows)) {
            $allows = array_keys($flags);
        }

        foreach ($allows as $key) {
            if (isset($flags[$key], $_REQUEST[$key])) {
                $flags[$key] = (bool) $_REQUEST[$key];
            }
        }

        $this->setOption($flags);

        return $this;
    }

    /**
     * Get merged list of well known third-party pattern
     *
     * @param  mixed $useCache
     * @return array
     */
    public function getWellKnown3rd($useCache = true)
    {
        $extended = $this->defer_third_party;

        if ($extended == false) {
            return [];
        }

        if ($useCache && !is_null($this->wellknown3rd)) {
            return $this->wellknown3rd;
        }

        $list = DeferConstant::WELL_KNOWN_THIRDPARTY;

        if (is_array($extended)) {
            $list = array_merge($list, $extended);
        }

        $this->wellknown3rd = array_filter(array_unique($list));

        return $this->wellknown3rd;
    }

    /**
     * Initial default values for options
     *
     *  // Insert debug information inside the output HTML after optimization.
     *  // Debug information will contain outer HTMLs of tags before being optimized.
     *  // Default: false (turn off the debug information)
     *  'debug_mode' => false,
     *
     *  // Although defer.js is the soul of this library,
     *  //   in some regions, you may want to serve defer.js library locally
     *  //   due to The General Data Protection Regulation (EU).
     *  // See: https://en.wikipedia.org/wiki/General_Data_Protection_Regulation
     *  // If you need to manually insert the defer.js library yourself,
     *  //   please enable this option to true.
     *  // Default: false (always automatically insert defer.js library)
     *  'manually_add_deferjs' => false,
     *
     *  // URL to defer.js javascript file.
     *  // Default: https://cdn.jsdelivr.net/npm/@shinsenter/defer.js@2.3.0/dist/defer_plus.min.js
     *  'deferjs_src'  => \AppSeeds\DeferConstant::SRC_DEFERJS_CDN,
     *
     *  // URL to javascript contains fixes.
     *  // for some older browsers that do not support IntersectionObserver feature.
     *  // Default: https://polyfill.io/v3/polyfill.min.js?features=IntersectionObserver
     *  'polyfill_src' => \AppSeeds\DeferConstant::SRC_POLYFILL_CDN,
     *
     *  // Inline the defer.js library to minimize download time in the browser.
     *  // Default: true (always automatically inline defer.js library)
     *  'inline_deferjs' => true,
     *
     *  // ---------------------------------------------------------------------------
     *
     *  // This option moves all stylesheets to bottom of the head tag,
     *  //   and moves script tags to bottom of the body tag
     *  // See: https://web.dev/render-blocking-resources/
     *  // Default: true (always automatically fix render blocking)
     *  'fix_render_blocking' => true,
     *
     *  // Turn on optimization for stylesheets
     *  // This option applies to style and link[rel="stylesheet"] tags.
     *  // Best practices: https://web.dev/extract-critical-css/
     *  // Default: true (automatically optimize stylesheets)
     *  'optimize_css'        => true,
     *
     *  // Optimize script tags (both inline and external scripts).
     *  // Note: The library only minify for inline script tags.
     *  // See: https://web.dev/unminified-javascript/
     *  // Default: true (automatically optimize script tags)
     *  'optimize_scripts'    => true,
     *
     *  // Optimize img, picture, video, audio and source tags.
     *  // See: https://web.dev/browser-level-image-lazy-loading/
     *  // See: https://web.dev/lazy-loading-images/
     *  // Default: true (automatically optimize)
     *  'optimize_images'     => true,
     *
     *  // Optimize iframe, frame, embed tags.
     *  // See: https://web.dev/lazy-loading-video/
     *  // Default: true (automatically optimize)
     *  'optimize_iframes'    => true,
     *
     *  // Optimize tags that containing CSS for loading images from external sources.
     *  // For example, style properties contain background-image:url() etc.
     *  // See: https://web.dev/optimize-css-background-images-with-media-queries/
     *  // Default: true (automatically optimize)
     *  'optimize_background' => true,
     *
     *  // Create noscript tags so lazy-loaded elements can still display
     *  //   even when the browser doesn't have javascript enabled.
     *  // This option applies to all tags that have been lazy-loaded.
     *  // See: https://web.dev/without-javascript/
     *  // Default: true (automatically create fallback noscript tags)
     *  'optimize_fallback'   => true,
     *
     *  // Optimize anchor tags, fix unsafe links to cross-origin destinations
     *  // See: https://web.dev/external-anchors-use-rel-noopener/
     *  // Default: true (automatically optimize)
     *  'optimize_anchors' => true,
     *
     *  // Add missing meta tags such as meta[name="viewport"], meta[charset] etc.
     *  // See: https://web.dev/viewport/
     *  // Default: true (automatically optimize)
     *  'add_missing_meta_tags' => true,
     *
     *  // Preconnect to required URL origins.
     *  // See: https://web.dev/uses-rel-preconnect/
     *  // Default: true (automatically optimize)
     *  'enable_dns_prefetch'   => true,
     *
     *  // Preload key requests such as stylesheets or external scripts.
     *  // See: https://web.dev/uses-rel-preload/
     *  // Default: false (do not apply by default)
     *  'enable_preloading'     => false,
     *
     *  // Lazy-load all elements like images, videos when possible.
     *  // See: https://web.dev/lazy-loading/
     *  // Default: true (automatically optimize)
     *  'enable_lazyloading'    => true,
     *
     *  // Minify HTML output.
     *  // See: https://web.dev/reduce-network-payloads-using-text-compression/
     *  // Default: false (do not minify HTML by default)
     *  'minify_output_html'    => false,
     *
     *  // ---------------------------------------------------------------------------
     *
     *  // Detect and optimize third-party URLs if possible (experiment).
     *  // This option also allows entering an array containing the URL origins to be defered.
     *  // See: https://web.dev/preload-optional-fonts/
     *  // Default: true (automatically optimize)
     *  'defer_third_party' => true,
     *
     *  // Apply fade-in animation to tags after being lazy-loaded.
     *  // Default: false (do not apply by default)
     *  'use_css_fadein_effects' => false,
     *
     *  // Use random background colors for images to be lazy-loaded.
     *  // Set the value to 'grey' if you want to use greyish background colors.
     *  // Default: false (do not apply by default)
     *  'use_color_placeholder'  => false,
     *
     *  // ---------------------------------------------------------------------------
     *
     *  // Default placeholder for lazy-loaded img tags.
     *  // If this value is not set or empty,
     *  //   an SVG image will be used to avoid CLS related problems.
     *  // See: https://web.dev/cls/
     *  // Default: blank string
     *  'img_placeholder'    => '',
     *
     *  // Default placeholder for lazy-loaded iframe tags.
     *  // Default: 'about:blank'
     *  'iframe_placeholder' => 'about:blank',
     *
     *  // ---------------------------------------------------------------------------
     *
     *  // Show custom HTML content (splashscreen)
     *  //   while browser is rendering the page (experiment).
     *  // Default: blank string (no splashscreen)
     *  'custom_splash_screen' => '',
     *
     *  // ---------------------------------------------------------------------------
     *
     *  // Do not lazy-load for URLs containing
     *  //   one of the array's texts (exact match keywords).
     *  // Default: blank array
     *  'ignore_lazyload_paths'  => [],
     *
     *  // Do not lazy-load for tags containing
     *  //   one of the array's texts (exact match keywords).
     *  // Default: blank array
     *  'ignore_lazyload_texts'  => [],
     *
     *  // Do not lazy-load for tags containing
     *  //   one of these CSS class names.
     *  // Default: blank array
     *  'ignore_lazyload_css_class'  => [],
     *
     *  // Do not lazy-load for tags containing
     *  //   one of these CSS selectors.
     *  // See: https://www.w3schools.com/cssref/css_selectors.asp
     *  // Default: blank array
     *  'ignore_lazyload_css_selectors'  => [],
     *
     * @since  2.0.0
     * @return array
     */
    private function defaultOptions()
    {
        return [
            // Disable the library
            'disable' => !empty($_REQUEST[DeferConstant::ARG_NODEFER]),

            // Debug optimized tags (instead of optimized HTML)
            'debug_time' => false,
            'debug_mode' => !empty($_REQUEST[DeferConstant::ARG_DEBUG]),

            // Manually add deferjs
            'manually_add_deferjs'   => false,
            'deferjs_type_attribute' => DeferConstant::TXT_DEFAULT_DEFERJS,

            // Asset sources
            'deferjs_src'  => DeferConstant::SRC_DEFERJS_CDN,
            'polyfill_src' => DeferConstant::SRC_POLYFILL_CDN,

            // Cache directory
            'offline_cache_path' => DeferConstant::SCR_DEFERJS_CACHE,
            'offline_cache_ttl'  => 86400,

            // Copyright
            'copyright'         => DeferConstant::TXT_SHORT_COPYRIGHT,
            'long_copyright'    => DeferConstant::TXT_LONG_COPYRIGHT,
            'console_copyright' => null,

            // Library injection
            'inline_deferjs'     => true,
            'default_defer_time' => 10,

            // Page optimizations
            'add_missing_meta_tags' => true,
            'enable_preloading'     => false,
            'enable_dns_prefetch'   => true,
            'enable_lazyloading'    => true,
            'minify_output_html'    => false,

            // Tag optimizations
            'fix_render_blocking' => true,
            'optimize_css'        => true,
            'optimize_scripts'    => true,
            'optimize_images'     => true,
            'optimize_iframes'    => true,
            'optimize_background' => true,
            'optimize_anchors'    => true,
            'optimize_fallback'   => true,

            // Third-party optimizations
            'defer_third_party' => true,

            // Content placeholders
            'use_css_fadein_effects' => false,
            'use_color_placeholder'  => false,

            // Lazyload placeholder
            'img_placeholder'    => '',
            'iframe_placeholder' => 'about:blank',

            // Splash screen
            'custom_splash_screen' => '',

            // Blacklists
            'ignore_lazyload_paths' => [],
            'ignore_lazyload_texts' => [],

            // Blacklists using CSS class names
            'ignore_lazyload_css_class'     => [],
            'ignore_lazyload_css_selectors' => [],
        ];
    }

    private function configureOptions()
    {
        $this->resolver->setDefaults($this->defaultOptions());

        return $this;
    }
}
