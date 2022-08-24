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

use DOMDocument;
use Symfony\Component\DomCrawler\Crawler;

final class DeferParser extends Crawler
{
    /**
     * Returns DOM document.
     *
     * @return null|DOMDocument
     */
    public function getDocument()
    {
        return $this->getNode(0)->ownerDocument ?? null;
    }
}
