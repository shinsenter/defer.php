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
 * Escape script tags contain UI templates that break DocumentNode::loadHTML().
 */
final class BugTemplateScripts implements PatchInterface
{
    /**
     * @var array<string,string>
     */
    private $_script_backups = [];

    /**
     * {@inheritdoc}
     */
    public function before($html, $options)
    {
        if (empty($html)) {
            return '';
        }

        $type = $options->deferjs_type_attribute;

        return preg_replace_callback(
            '/(<script[^>]*>)(.*?)(<\/script>)/si',
            function ($matches) use ($type) {
                $open    = strtolower($matches[1]);
                $content = $matches[2];

                // Escape invalid syntax from javascript
                if (strstr($open, ' type=') === false
                || strstr($open, '/javascript') !== false
                || strstr($open, ' type="' . $type . '"') !== false) {
                    $content = preg_replace([
                        // Remove HTML comment from script
                        '/(^\s*<!--\s*|\s*\/\/\s*-->\s*$|\s*\/\/$)/',

                        // Fixed HTMLEntity
                        '/&(#?[a-z0-9]+);/',

                        // Fix closing HTML tags inside script
                        '/<\/([^>]*)>/',

                        // Fix yen symbols to backslashes
                        '/\\\/',
                    ], ['', '&#38;$1;', '<&#92;/$1>', '&#92;'], trim($content)) ?: '';
                }

                // Backup all scripts contain html-like content
                if (preg_match('/<\/([^>]*)>/', $content)) {
                    $placeholder                         = '/** ' . uniqid('@@@SCRIPT@@@:') . ' **/';
                    $this->_script_backups[$placeholder] = $content;
                    $content                             = $placeholder;
                }

                // Return modified tag
                return sprintf('%s%s%s', $matches[1], $content, $matches[3]);
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
        if (!empty($this->_script_backups)) {
            return strtr($html, $this->_script_backups);
        }

        return $html;
    }

    /**
     * {@inheritdoc}
     */
    public function cleanup()
    {
        unset($this->_script_backups);
        $this->_script_backups = [];
    }
}
