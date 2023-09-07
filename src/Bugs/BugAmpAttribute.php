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
 * Fix AMP attribute in HTML tag.
 */
final class BugAmpAttribute implements PatchInterface
{
    /**
     * @var array<string,string>
     */
    private $_tag_backups = [];

    /**
     * {@inheritdoc}
     */
    public function before($html, $options)
    {
        if (empty($html)) {
            return '';
        }

        $find  = implode('|', [preg_quote('&#x26A1;', '@'), '⚡', 'amp']);
        $regex = '@(<html[^>]*)(' . $find . ')([^>]*>)@iu';

        if (!empty(preg_match($regex, $html, $matches))) {
            $html = preg_replace($regex, '$1amp$3', $html) ?: '';
        }

        return preg_replace_callback(
            '/<(amp-[^\s>]+)[^>]*>.*?(<\/\1>)/si',
            function ($matches) {
                $placeholder                      = '<amp>' . uniqid('@@@AMP@@@:') . '</amp>';
                $this->_tag_backups[$placeholder] = $matches[0];

                return $placeholder;
            },
            $html
        ) ?: '';
    }

    /**
     * {@inheritdoc}
     */
    public function after($html, $options)
    {
        if (empty($html)) {
            return '';
        }

        // Restore scripts from backup
        if (!empty($this->_tag_backups)) {
            return strtr($html, $this->_tag_backups);
        }

        return $html;
    }

    /**
     * {@inheritdoc}
     */
    public function cleanup()
    {
        unset($this->_tag_backups);
        $this->_tag_backups = [];
    }
}
