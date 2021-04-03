# defer.php

🚀 A PHP library that aims to help you concentrate on web performance optimization.

- **Package**: [@shinsenter/defer.php](https://packagist.org/packages/shinsenter/defer.php)
- **Version**: 2.2.0
- **Author**: Mai Nhut Tan <shin@shin.company>
- **Copyright**: 2021 AppSeeds <https://code.shin.company/>
- **License**: [MIT](https://raw.githubusercontent.com/shinsenter/defer.php/master/LICENSE)

[![GitHub](https://img.shields.io/github/license/shinsenter/defer.php.svg)](https://github.com/shinsenter/defer.php)
[![GitHub Release Date](https://img.shields.io/github/release-date/shinsenter/defer.php.svg)](https://github.com/shinsenter/defer.php/releases)
[![CodeFactor Grade](https://img.shields.io/codefactor/grade/github/shinsenter/defer.php)](https://www.codefactor.io/repository/github/shinsenter/defer.php)
[![Post an issue](https://img.shields.io/badge/contributions-welcome-brightgreen.svg?style=flat)](https://github.com/shinsenter/defer.php/issues)
[![GitHub issues](https://img.shields.io/github/issues-raw/shinsenter/defer.php.svg)](https://github.com/shinsenter/defer.php/issues/new)

* * *

🥇 Powered by [defer.js](https://github.com/shinsenter/defer.js) - A super small, super efficient library that helps you lazy load almost everything like images, video, audio, iframes as well as stylesheets, and JavaScript.

* * *


## Features

- [x] Simplify library options
- [x] Embed defer.js library
- [x] Normalize DOM elements
- [x] Fix missing meta tags
- [x] Fix missing media attributes
- [x] Preconnect to required origins
- [x] Preload key requests
- [x] Prefetch key requests
- [x] Browser-level image lazy-loading for the web
- [x] Lazy-load offscreen and hidden iframes
- [x] Lazy-load offscreen and hidden videos
- [x] Lazy-load offscreen and hidden images
- [x] Lazy-load CSS background images
- [x] Reduce the impact of JavaScript
- [x] Defer non-critical CSS requests
- [x] Defer third-party assets
- [x] Add fallback `<noscript>` tags for lazy-loaded objects
- [x] Add custom HTML while browser is rendering the page (splashscreen)
- [x] Attribute to ignore optimizing the element
- [x] Attribute to ignore lazyloading the element
- [x] Optimize AMP document
- [x] Minify HTML output


## Installation


### Install with composer

```bash
composer require shinsenter/defer.php
```


### Load the library into your program

```php
// Include the library
require_once __DIR__ . '/vendor/autoload.php';

// TODO: your code is from here

```


### Requirements

This library requires PHP 5.6 or above so you need this version or the latest version of PHP installed on your system.

It recommends that the server is running PHP version 7.3+ or above for better performance and supports.

Library options from v2.x are not backward compatible with previous release's options. Please read [library manual](#options) for more details.


## Usages


### Basic usage

```php
// Include the library
require_once __DIR__ . '/vendor/autoload.php';

// Create a Defer object
$defer = new \AppSeeds\Defer();

// Read HTML source from file
$html_source = file_get_contents('mypage.html');

// Then get the optimized output
$result = $defer->fromHtml($html_source)->toHtml($html);
var_dump($result);

// You can use the same instance to keep loading another HTML and optimize it
$result2 = $defer->fromHtml(file_get_contents('otherpage.html'))->toHtml();
var_dump($result2);
```


### Options

```php
// Include the library
require_once __DIR__ . '/vendor/autoload.php';

// Declare the options
$options = [
  // Insert debug information inside the output HTML after optimization.
  // Debug information will contain outer HTMLs of tags before being optimized.
  // Default: false (turn off the debug information)
  'debug_mode' => false,

  // Although defer.js is the soul of this library,
  //   in some regions, you may want to serve defer.js library locally
  //   due to The General Data Protection Regulation (EU).
  // See: https://en.wikipedia.org/wiki/General_Data_Protection_Regulation
  // If you need to manually insert the defer.js library yourself,
  //   please enable this option to true.
  // Default: false (always automatically insert defer.js library)
  'manually_add_deferjs' => false,

  // URL to defer.js javascript file.
  // Default: https://cdn.jsdelivr.net/npm/@shinsenter/defer.js@2.4.0/dist/defer_plus.min.js
  'deferjs_src'  => \AppSeeds\DeferConstant::SRC_DEFERJS_CDN,

  // URL to javascript contains fixes.
  // for some older browsers that do not support IntersectionObserver feature.
  // Default: https://polyfill.io/v3/polyfill.min.js?features=IntersectionObserver
  'polyfill_src' => \AppSeeds\DeferConstant::SRC_POLYFILL_CDN,

  // Inline the defer.js library to minimize download time in the browser.
  // Default: true (always automatically inline defer.js library)
  'inline_deferjs' => true,

  // ---------------------------------------------------------------------------

  // This option moves all stylesheets to bottom of the head tag,
  //   and moves script tags to bottom of the body tag
  // See: https://web.dev/render-blocking-resources/
  // Default: true (always automatically fix render blocking)
  'fix_render_blocking' => true,

  // Turn on optimization for stylesheets
  // This option applies to style and link[rel="stylesheet"] tags.
  // Best practices: https://web.dev/extract-critical-css/
  // Default: true (automatically optimize stylesheets)
  'optimize_css'        => true,

  // Optimize script tags (both inline and external scripts).
  // Note: The library only minify for inline script tags.
  // See: https://web.dev/unminified-javascript/
  // Default: true (automatically optimize script tags)
  'optimize_scripts'    => true,

  // Optimize img, picture, video, audio and source tags.
  // See: https://web.dev/browser-level-image-lazy-loading/
  // See: https://web.dev/lazy-loading-images/
  // Default: true (automatically optimize)
  'optimize_images'     => true,

  // Optimize iframe, frame, embed tags.
  // See: https://web.dev/lazy-loading-video/
  // Default: true (automatically optimize)
  'optimize_iframes'    => true,

  // Optimize tags that containing CSS for loading images from external sources.
  // For example, style properties contain background-image:url() etc.
  // See: https://web.dev/optimize-css-background-images-with-media-queries/
  // Default: true (automatically optimize)
  'optimize_background' => true,

  // Create noscript tags so lazy-loaded elements can still display
  //   even when the browser doesn't have javascript enabled.
  // This option applies to all tags that have been lazy-loaded.
  // See: https://web.dev/without-javascript/
  // Default: true (automatically create fallback noscript tags)
  'optimize_fallback'   => true,

  // Optimize anchor tags, fix unsafe links to cross-origin destinations
  // See: https://web.dev/external-anchors-use-rel-noopener/
  // Default: true (automatically optimize)
  'optimize_anchors' => true,

  // Add missing meta tags such as meta[name="viewport"], meta[charset] etc.
  // See: https://web.dev/viewport/
  // Default: true (automatically optimize)
  'add_missing_meta_tags' => true,

  // Preconnect to required URL origins.
  // See: https://web.dev/uses-rel-preconnect/
  // Default: true (automatically optimize)
  'enable_dns_prefetch'   => true,

  // Preload key requests such as stylesheets or external scripts.
  // See: https://web.dev/uses-rel-preload/
  // Default: false (do not apply by default)
  'enable_preloading'     => false,

  // Lazy-load all elements like images, videos when possible.
  // See: https://web.dev/lazy-loading/
  // Default: true (automatically optimize)
  'enable_lazyloading'    => true,

  // Minify HTML output.
  // See: https://web.dev/reduce-network-payloads-using-text-compression/
  // Default: false (do not minify HTML by default)
  'minify_output_html'    => false,

  // ---------------------------------------------------------------------------

  // Detect and optimize third-party URLs if possible (experiment).
  // This option also allows entering an array containing the URL origins to be defered.
  // See: https://web.dev/preload-optional-fonts/
  // Default: true (automatically optimize)
  'defer_third_party' => true,

  // Apply fade-in animation to tags after being lazy-loaded.
  // Default: false (do not apply by default)
  'use_css_fadein_effects' => false,

  // Use random background colors for images to be lazy-loaded.
  // Set the value to 'grey' if you want to use greyish background colors.
  // Default: false (do not apply by default)
  'use_color_placeholder'  => false,

  // ---------------------------------------------------------------------------

  // Default placeholder for lazy-loaded img tags.
  // If this value is not set or empty,
  //   an SVG image will be used to avoid CLS related problems.
  // See: https://web.dev/cls/
  // Default: blank string
  'img_placeholder'    => '',

  // Default placeholder for lazy-loaded iframe tags.
  // Default: 'about:blank'
  'iframe_placeholder' => 'about:blank',

  // ---------------------------------------------------------------------------

  // Show custom HTML content (splashscreen)
  //   while browser is rendering the page (experiment).
  // Default: blank string (no splashscreen)
  'custom_splash_screen' => '',

  // ---------------------------------------------------------------------------

  // Do not lazy-load for URLs containing
  //   one of the array's texts (exact match keywords).
  // Default: blank array
  'ignore_lazyload_paths'  => [],

  // Do not lazy-load for tags containing
  //   one of the array's texts (exact match keywords).
  // Default: blank array
  'ignore_lazyload_texts'  => [],

  // Do not lazy-load for tags containing
  //   one of these CSS class names.
  // Default: blank array
  'ignore_lazyload_css_class'  => [],

  // Do not lazy-load for tags containing
  //   one of these CSS selectors.
  // See: https://www.w3schools.com/cssref/css_selectors.asp
  // Default: blank array
  'ignore_lazyload_css_selectors'  => [],
];

// Create a Defer object
$defer  = new \AppSeeds\Defer($options);
$result = $defer->fromHtml(file_get_contents('mypage.html'))->toHtml();
var_dump($result);

// Change library options
$defer->options()->debug = true;
$defer->options()->minify_output_html = true;

// Keep loading another HTML and optimize it with new options
$result2 = $defer->fromHtml(file_get_contents('otherpage.html'))->toHtml();
var_dump($result2);

```


### Optimize final output HTML of a website

You also can capture the final output generated by PHP and optimize it before giving it back to browser.

```php
// Include the library
require_once __DIR__ . '/vendor/autoload.php';

// Create a callback function
function ob_deferphp($html) {
  // Create a Defer object
  $defer = new \AppSeeds\Defer([
    /* declare options here */
  ]);

  return $defer->fromHtml($html)->toHtml();
}

// Call ob_start() function to create an output buffer
//   and pass above callback function name as its argument.
// This function should be called before any other process to print the content.
ob_start('ob_deferphp');

// .......... (place your PHP code here)

// And call this to flush optimized output HTML
//   right before you send the final HTML to browser.
ob_end_flush();
```


### Ignore optimization for some elements

Add an `data-ignore` attribute to element that you don't want it to be optimized by the library.
This attribute can be used for all HTML elements.


```html
<!-- Example for add data-ignore for a script tag -->
<script data-ignore>var MY_IMPORTANT_VARIABLE = 'important value';</script>

<!-- Example for add data-ignore for an img tag -->
<img data-ignore src="my_photo.jpeg" alt="Awesome photo" />
```


Add an `data-nolazy` attribute to element that you don't want it to be lazy-loaded by the library.
Other optimizations for that element will still be applied except lazy-load.
This attribute can be used for all `<img>`, `<picture>`, `<video>`, `<audio>`, `<iframe>` and also `<link rel="stylesheet">` elements.

```html
<!-- Example for add data-nolazy for an img tag -->
<img data-nolazy src="my_photo.jpeg" alt="Awesome photo" />
```

### Stylesheets and JavaScript

This library supports a more efficient lazy-load method for stylesheets and JavaScript tags that contain the `defer` attribute.

```html
<!-- Example for defer an stylesheet -->
<link defer rel="stylesheet" href="some/heavy/fonts.css">

<!-- Examples for defer some heavy script tags -->
<script defer src="some/heavy/libraries.js"></script>
<script defer>someHeavyTask();</script>
```

Even when the library is turned off, above tags that contain `defer` attribute are backwards compatible and work well in most modern browsers. You can use it with peace of mind.


### AMP page

Only few options of this library are applicable to AMP pages (minifying HTML content for example).


## My works

### Defer.js

[https://github.com/shinsenter/defer.js/](https://github.com/shinsenter/defer.js/)

🥇 A super small, super efficient library that helps you lazy load almost everything like images, video, audio, iframes as well as stylesheets, and JavaScript.


### Wordpress plugin

[https://github.com/shinsenter/defer-wordpress/](https://github.com/shinsenter/defer-wordpress/)

⚡️ A native, blazing fast lazy loader. ✅ Legacy browsers support (IE9+). 💯 SEO friendly. 🧩 Lazy-load everything.


### Laravel package

Under development.


## Support my activities

[![Donate via Paypal](https://img.shields.io/badge/Donate-Paypal-blue)](https://www.paypal.me/shinsenter)
[![Become a sponsor](https://img.shields.io/badge/Donate-Patreon-orange)](https://www.patreon.com/appseeds)
[![Become a stargazer](https://img.shields.io/badge/Support-Stargazer-yellow)](https://github.com/shinsenter/defer.php/stargazers)
[![Report an issue](https://img.shields.io/badge/Support-Issues-red)](https://github.com/shinsenter/defer.php/issues/new)


* * *

From Vietnam 🇻🇳 with love.
