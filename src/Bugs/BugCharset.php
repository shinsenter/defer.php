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
 * Fix document charset.
 */
final class BugCharset implements PatchInterface
{
    /**
     * @var string
     */
    const DEFAULT_CHARSET = 'UTF-8';

    /**
     * @var string
     */
    const ENCODED_CHARSET = 'HTML-ENTITIES';

    /**
     * @var string
     */
    const FIND = '/(&#?[a-z0-9]+;)+/';

    /**
     * @var string|null
     */
    private $charset;

    /**
     * @param string|null $charset
     */
    public function __construct($charset = null)
    {
        if ($charset) {
            $this->charset = $charset;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function before($html, $options)
    {
        if (empty($html)) {
            return '';
        }

        if (empty($this->charset)) {
            $this->charset = $this->detectCharset($html);
        }

        $html = @mb_convert_encoding($html, self::ENCODED_CHARSET, $this->charset);

        return '<?xml version="1.0" encoding="' . $this->charset . '"?>' . $html;
    }

    /**
     * {@inheritdoc}
     */
    public function after($html, $options)
    {
        if (empty($html)) {
            return '';
        }

        $html = preg_replace('/<\?xml[^>]*>/i', '', $html, 1) ?: '';

        $charset       = $this->charset ?: self::DEFAULT_CHARSET;
        $after_charset = @mb_detect_encoding($html, @mb_detect_order(), true);

        if ($after_charset === false || $after_charset === 'ASCII') {
            $after_charset = self::ENCODED_CHARSET;
        }

        if ($after_charset == self::ENCODED_CHARSET) {
            $html = $this->escapeHtmlEntity($html, false);
        }

        $cached = [];
        $html   = preg_replace_callback(
            self::FIND,
            static function ($match) use ($charset, &$cached) {
                $org = $match[0];
                if (isset($cached[$org])) {
                    return $cached[$org];
                }

                return $cached[$org] = @mb_convert_encoding(
                    $org,
                    $charset,
                    self::ENCODED_CHARSET
                );
            },
            $html
        ) ?: '';

        unset($cached);

        if ($after_charset == self::ENCODED_CHARSET) {
            return $this->escapeHtmlEntity($html, true);
        }

        return $html;
    }

    /**
     * {@inheritdoc}
     */
    public function cleanup()
    {
        unset($this->charset);
        $this->charset = null;
    }

    /**
     * Get charset from html.
     *
     * @param string $html
     */
    private function detectCharset($html)
    {
        $charset = null;

        if (preg_match('/<meta.*?charset=["]?([^"\s]+)/im', $html, $matches)) {
            $charset = $matches[1];
        }

        // Detect charset charset
        if (empty($charset)) {
            $after_charset = @mb_detect_encoding($html, @mb_detect_order(), true);

            if ($after_charset === false || $after_charset === 'ASCII') {
                $after_charset = self::DEFAULT_CHARSET;
            }

            $charset = $after_charset;
        }

        return strtoupper($charset ?: self::DEFAULT_CHARSET);
    }

    /**
     * Escape / unescape regular HTML entities.
     *
     * @since  2.0.0
     *
     * @param string $html
     * @param bool   $revert = false
     *
     * @return string
     */
    private function escapeHtmlEntity($html, $revert = false)
    {
        /** @var array|null $__html_mapping */
        static $__html_mapping;

        // Initial HTML entity optimizer
        if (is_null($__html_mapping)) {
            $mapping = array_values(get_html_translation_table(HTML_SPECIALCHARS));

            $__html_mapping = [
                'from' => $mapping,
                'to'   => array_map(static function ($value) {
                    return strtr($value, ['&' => '@&@', ';' => '@;@']);
                }, $mapping),
            ];

            unset($mapping);
        }

        // Process the HTML
        if ($revert) {
            return strtr(
                $html,
                array_combine(
                    $__html_mapping['to'],
                    $__html_mapping['from']
                ) ?: []
            );
        }

        return strtr(
            $html,
            array_combine(
                $__html_mapping['from'],
                $__html_mapping['to']
            ) ?: []
        );
    }
}
