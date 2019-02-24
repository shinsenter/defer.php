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
    const IGNORE_ATTRIBUTE = 'data-ignore';
    const LAZY_CSS_MEDIA   = 'screen and (max-width: 1px)';
    const DOMAIN_PARSER    = '/^((https?\:)?\/\/[^\/\?\#]+).*$/i';

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

    /**
     * @author Mai Nhut Tan
     * @since  1.0.0
     * @param  mixed $src
     * @param  mixed $crossorigin
     * @param  mixed $preconnect
     * @return mixed
     */
    protected function createLinkDnsPrefetch($src, $preconnect = true)
    {
        $output = [];

        if ($src = $this->getDnsPrefetch($src)) {
            $crossorigin = $crossorigin ? 'crossorigin' : '';
            $output[]    = "<link rel=\"dns-prefetch\" href=\"{$src}/\">";

            if ($preconnect) {
                $output[] = "<link rel=\"preconnect\"   href=\"{$src}/\" crossorigin>\n";
            }
        }

        return $output;
    }

    /**
     * @author Mai Nhut Tan
     * @since  1.0.0
     * @param  mixed $src
     * @param  mixed $type
     * @return mixed
     */
    protected function createLinkPreload($src, $type = 'document')
    {
        $output = [];

        if (!empty($src)) {
            $output[] = "<link rel=\"preload\" href=\"{$src}\" as=\"{$type}\">";
        }

        return $output;
    }

    /**
     * @author Mai Nhut Tan
     * @since  1.0.0
     * @param  mixed $url
     * @return mixed
     */
    protected function getDnsPrefetch($url)
    {
        if (preg_match(static::DOMAIN_PARSER, $url)) {
            return preg_replace(static::DOMAIN_PARSER, '$1', $url);
        }

        return null;
    }
}
