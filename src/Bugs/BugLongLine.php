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

namespace AppSeeds\Bugs;

use AppSeeds\Contracts\PatchInterface;

/**
 * PHP bugs
 * Lines longer than 1000 characters break DOMDocument::loadHTML()
 *
 * @see https://bugs.php.net/bug.php?id=72288
 */
class BugLongLine implements PatchInterface
{
    private $_bug72288_body;

    /**
     * {@inheritdoc}
     */
    public function before($html)
    {
        $this->_bug72288_body = preg_match('/(<body[^>]*>)/mi', $html, $match) ? $match[1] : null;

        return $html;
    }

    /**
     * {@inheritdoc}
     */
    public function after($html)
    {
        if (!empty($this->_bug72288_body)) {
            $html = preg_replace('/(<body[^>]*>)/mi', $this->_bug72288_body, $html, 1);
        }

        return $html;
    }

    /**
     * {@inheritdoc}
     */
    public function cleanup()
    {
        unset($this->_bug72288_body);
        $this->_bug72288_body = null;
    }
}
