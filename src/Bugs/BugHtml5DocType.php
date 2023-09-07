<?php

/**
 * Defer.php aims to help you concentrate on web performance optimization.
 * (c) 2019-2023 SHIN Company https://shin.company
 *
 * PHP Version >=5.6
 *
 * @category  Web_Performance_Optimization
 * @package   AppSeeds
 * @author    Mai Nhut Tan <shin@shin.company>
 * @copyright 2019-2023 SHIN Company
 * @license   https://code.shin.company/defer.php/blob/master/LICENSE MIT
 * @link      https://code.shin.company/defer.php
 * @see       https://code.shin.company/defer.php/blob/master/README.md
 */

namespace AppSeeds\Bugs;

use AppSeeds\Contracts\PatchInterface;

/**
 * PHP bugs
 * Lines longer than 1000 characters break DocumentNode::loadHTML().
 *
 * @see https://bugs.php.net/bug.php?id=72288
 */
final class BugHtml5DocType implements PatchInterface
{
    /**
     * {@inheritdoc}
     */
    public function before($html, $options)
    {
        return $html ?: '';
    }

    /**
     * {@inheritdoc}
     */
    public function after($html, $options)
    {
        if (empty($html)) {
            return '';
        }

        // Remove XML meta
        $html = preg_replace('/<\?xml[^>]*>/i', '', $html, 1) ?: '';

        return preg_replace('/<!DOCTYPE html[^>]*>/i', '<!DOCTYPE html>', $html, 1) ?: '';
    }

    /**
     * {@inheritdoc}
     */
    public function cleanup()
    {
    }
}
