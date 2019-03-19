# defer.php

🔌 defer.php is a PHP helper class helping you to integrate my beloved [defer.js](https://github.com/shinsenter/defer.js) library into your websites.

defer.js is a super tiny, native performant library for lazy-loading JS, CSS, images, iframes...

Easily speed up your website! Hope you guys like it.



## How to use



### Install with composer

```bash
composer require shinsenter/defer.php:dev-master
```



### Library usage

```php
// Include the library
require_once __DIR__ . '/vendor/shinsenter/defer.php/defer.php';

// Library options
$options = [
    // Library injection
    'append_defer_js'      => true,
    'default_defer_time'   => 500,

    // Page optimizations
    'enable_preloading'    => true,
    'enable_dns_prefetch'  => true,
    'fix_render_blocking'  => true,
    'minify_output_html'   => true,

    // Tag optimizations
    'enable_defer_css'     => true,
    'enable_defer_scripts' => true,
    'enable_defer_images'  => true,
    'enable_defer_iframes' => true,

    // Web-font optimizations
    'defer_web_fonts'      => true,
];

// Read HTML source from file
$html_source = file_get_contents('mypage.html');

// Create a Defer object, then get the output
$defer = new \shinsenter\Defer($html_source, $options);
$result = $defer->toHtml();
var_dump($result);

// Load another HTML without creating new object
// You can write all methods in one line like this
$result = $defer->fromHtml(file_get_contents('otherpage.html'))->toHtml();
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
