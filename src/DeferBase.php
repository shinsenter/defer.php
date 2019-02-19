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

    public function __toString()
    {
        return $this->dom ? $this->DomToHtml($this->dom) : '';
    }

    public function setCharset($charset = null)
    {
        $this->sourceCharset = $charset ?: 'UTF-8';
    }

    protected function HtmlToDom($html)
    {
        $doc = new DOMDocument();
        @$doc->loadHTML('<defer>' . $html . '</defer>');

        $xpath = new DOMXPath($doc);
        $nodes = $xpath->query('//defer/*');
        $dom   = $nodes->item(0);

        return $dom;
    }

    protected function DomToHtml($dom)
    {
        if (is_null(static::$document)) {
            static::$document = new DOMDocument();
        }

        $cloned = static::$document->importNode($dom->cloneNode(true), true);
        static::$document->appendChild($cloned);
        static::$document->validate();
        $html = static::$document->saveHtml($cloned);
        static::$document->removeChild($cloned);

        return mb_convert_encoding($html, $this->sourceCharset, 'HTML-ENTITIES');
    }
}
