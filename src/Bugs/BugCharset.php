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
 * Fix document charset
 */
class BugCharset implements PatchInterface
{
    const DEFAULT_CHARSET = 'UTF-8';
    const ENCODED_CHARSET = 'HTML-ENTITIES';

    protected $charset;

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
        if (empty($this->charset)) {
            $this->charset = $this->detectCharset($html);
        }

        $html = mb_convert_encoding($html, self::ENCODED_CHARSET, $this->charset);
        $html = '<?xml version="1.0" encoding="' . $this->charset . '"?>' . $html;

        return $html;
    }

    /**
     * {@inheritdoc}
     */
    public function after($html, $options)
    {
        $html = preg_replace('/<\?xml[^>]*>/i', '', $html, 1);

        $charset       = $this->charset ?: self::DEFAULT_CHARSET;
        $after_charset = mb_detect_encoding($html, mb_detect_order(), true);

        if ($after_charset === false || $after_charset === 'ASCII') {
            $after_charset = self::ENCODED_CHARSET;
        }

        if ($after_charset == self::ENCODED_CHARSET) {
            $html = $this->escapeHtmlEntity($html, false);
        }

        $cached = [];
        $find   = '/(&#?[a-z0-9]+;)+/';
        $html   = preg_replace_callback(
            $find,
            function ($match) use ($charset, &$cached) {
                $org = $match[0];

                if (isset($cached[$org])) {
                    return $cached[$org];
                }

                return $cached[$org] = mb_convert_encoding(
                    $org,
                    $charset,
                    self::ENCODED_CHARSET
                );
            },
            $html
        );

        unset($cached);

        if ($after_charset == self::ENCODED_CHARSET) {
            $html = $this->escapeHtmlEntity($html, true);
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
     * Get charset from html
     * @param mixed $html
     */
    protected function detectCharset($html)
    {
        $charset = null;

        if (preg_match('@<meta.*?charset=["]?([^"\s]+)@im', $html, $matches)) {
            $charset = $matches[1];
        }

        // Detect charset charset
        if (empty($charset)) {
            $after_charset = mb_detect_encoding($html, mb_detect_order(), true);

            if ($after_charset === false || $after_charset === 'ASCII') {
                $after_charset = self::DEFAULT_CHARSET;
            }

            $charset = $after_charset;
        }

        return strtoupper($charset ?: self::DEFAULT_CHARSET);
    }

    /**
     * Escape / unescape regular HTML entities
     *
     * @since  2.0.0
     * @param  string $html
     * @param  bool   $revert = false
     * @return string
     */
    protected function escapeHtmlEntity($html, $revert = false)
    {
        static $__html_mapping;

        // Initial HTML entity optimizer
        if (is_null($__html_mapping)) {
            $mapping = array_values(get_html_translation_table(HTML_SPECIALCHARS));

            $__html_mapping = [
                'from' => $mapping,
                'to'   => array_map(function ($value) {
                    return strtr($value, ['&' => '@&@', ';' => '@;@']);
                }, $mapping),
            ];

            unset($mapping);
        }

        // Process the HTML
        if ($revert) {
            $html = strtr(
                $html,
                array_combine(
                    $__html_mapping['to'],
                    $__html_mapping['from']
                )
            );
        } else {
            $html = strtr(
                $html,
                array_combine(
                    $__html_mapping['from'],
                    $__html_mapping['to']
                )
            );
        }

        return $html;
    }
}
