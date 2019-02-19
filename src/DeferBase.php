<?php

/**
 * A PHP helper class to efficiently defer JavaScript for your website.
 * (c) 2019 AppSeeds https://appseeds.net/
 *
 * @package   shinsenter/defer.php
 * @since     1.0.0
 * @author    Mai Nhut Tan <shin@shin.company>
 * @copyright 2019 AppSeeds
 * @see       https://github.com/shinsenter/defer.php/blob/develop/README.md
 */

namespace shinsenter;

use DOMDocument;
use DOMXPath;

class DeferBase
{
    public static $document;
    protected $sourceCharset = 'UTF-8';

    /**
     * @author Mai Nhut Tan
     * @since  1.0.0
     * @return mixed
     */
    public function __toString()
    {
        return $this->dom ? $this->DomToHtml($this->dom) : '';
    }

    /**
     * @author Mai Nhut Tan
     * @since  1.0.0
     * @param  $charset
     */
    public function setCharset($charset = null)
    {
        $this->sourceCharset = $charset ?: 'UTF-8';
    }

    /**
     * @author Mai Nhut Tan
     * @since  1.0.0
     * @param  $html
     * @return mixed
     */
    protected function HtmlToDom($html)
    {
        $doc = new DOMDocument();
        @$doc->loadHTML('<defer>' . $html . '</defer>');

        $path = '//defer/' . (strpos($html, '<!--') === 0 ? 'comment()' : '*');

        $xpath = new DOMXPath($doc);
        $nodes = $xpath->query($path);

        if (count($nodes) !== 1 || empty($dom = $nodes->item(0))) {
            return false;
        }

        return $dom;
    }

    /**
     * @author Mai Nhut Tan
     * @since  1.0.0
     * @param  $dom
     */
    protected function DomToHtml($dom)
    {
        if (is_null(static::$document)) {
            static::$document = new DOMDocument();
        }

        $cloned = static::$document->importNode($dom->cloneNode(true), true);
        static::$document->appendChild($cloned);
        $html = static::$document->saveHtml($cloned);
        static::$document->removeChild($cloned);

        return $html;
    }
}
