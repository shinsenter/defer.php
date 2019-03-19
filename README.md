# defer.php

🔌 defer.php is a PHP helper class helping you to integrate my beloved [defer.js](https://github.com/shinsenter/defer.js) library into your websites.

defer.js is a super tiny, native performant library for lazy-loading JS, CSS, images, iframes...

Easily speed up your website! Hope you guys like it.



## How to use



### Install with composer

```bash
composer require shinsenter/defer.php:dev-master
```



### Basic usage

```php
// Include the library
require_once __DIR__ . '/vendor/shinsenter/defer.php/defer.php';

// Create a Defer object
$defer = new \shinsenter\Defer();

// Process the HTML
$response->setContent($defer->from()->toHtml($html));

// Read HTML source from file
$html_source = file_get_contents('mypage.html');

// Then get the optimized output
$result = $defer->fromHtml($html_source)->toHtml($html);
var_dump($result);

// Load another HTML without creating new object
// You can write all methods in one line like this
$result = $defer->fromHtml(file_get_contents('otherpage.html'))->toHtml();
var_dump($result);
```



### Library's options

```php
// Include the library
require_once __DIR__ . '/vendor/shinsenter/defer.php/defer.php';

// Create a Defer object
$defer = new \shinsenter\Defer();

// Turn off warning and debug
$defer->debug_mode            = false;
$defer->hide_warnings         = true;

// Library injection
$defer->append_defer_js       = true;
$defer->default_defer_time    = 100;

// Page optimizations
$defer->enable_preloading     = true;
$defer->enable_dns_prefetch   = true;
$defer->fix_render_blocking   = true;
$defer->minify_output_html    = true;

// Tag optimizations
$defer->enable_defer_css      = true;
$defer->enable_defer_scripts  = false;
$defer->enable_defer_images   = true;
$defer->enable_defer_iframes  = true;

// Web-font optimizations
$defer->defer_web_fonts       = true;

// Image and iframe placeholders
$defer->empty_gif             = '';
$defer->empty_src             = '';
$defer->use_color_placeholder = true;

// Blacklist
$defer->do_not_optimize = [];

// Then get the optimized output
$result = $defer->fromHtml(file_get_contents('mypage.html'))->toHtml();
var_dump($result);
```



## [defer-wordpress](https://github.com/shinsenter/defer-wordpress/releases)

WordPress remains one of the most popular CMS platform until now. This is a WordPress plugin. Hope you guys like it.



## Keep in touch

[![Become a sponsor](https://c5.patreon.com/external/logo/become_a_patron_button@2x.png)](https://www.patreon.com/appseeds)


- Become a stargazer:
  https://github.com/shinsenter/defer.php/stargazers
- Report an issue:
  https://github.com/shinsenter/defer.php/issues
- Keep up-to-date with new releases:
  https://github.com/shinsenter/defer.php/releases



## Follow my defer.js project:

https://github.com/shinsenter/defer.js/releases

https://github.com/shinsenter/defer.js/stargazers

---

Released under the MIT license.
https://appseeds.net/defer.php/LICENSE

Copyright (c) 2019 Mai Nhut Tan &lt;[shin@shin.company](mailto:shin@shin.company)&gt;
