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

class Defer
{
    const DEFER_PLUS_SRC = 'https://raw.githubusercontent.com/shinsenter/defer.js/master/defer_plus.min.js';
    public static $deferJs;

    protected $original_html;
    protected $document;

    protected $cacheScriptTags;
    protected $cacheStyleTags;
    protected $cacheLinkTags;
    protected $cacheImgTags;
    protected $cacheIframeTags;
    protected $cacheOutput;

    public function __construct(string $html)
    {
        $this->setHtml($html);
    }

    public function setHtml($html)
    {
        $this->original_html = trim(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        $this->reset();

        return $this;
    }

    public function getHtml()
    {
        return $this->original_html ?: '';
    }

    public function deferHtml()
    {
        $render = array_merge(
            [],
            $this->parseAndCacheLinkTags(),
            $this->parseAndCacheStyleTags(),
            $this->parseAndCacheScriptTags(),
            []
        );

        return $render;
    }

    public function toString()
    {
        if (is_null($this->cacheOutput)) {
            $this->cacheOutput = $this->deferHtml();
        }

        return $this->cacheOutput;
    }

    public function getDeferJs()
    {
        if (is_null(static::$deferJs)) {
            $script          = @file_get_contents(static::DEFER_PLUS_SRC);
            static::$deferJs = '<script type="text/javascript">' . $script . '</script>';
        }

        return static::$deferJs;
    }

    public function reset()
    {
        $this->cacheScriptTags = null;
        $this->cacheStyleTags  = null;
        $this->cacheLinkTags   = null;
        $this->cacheImgTags    = null;
        $this->cacheIframeTags = null;
        $this->cacheOutput     = null;

        if (!is_null($this->document)) {
            $this->document = null;
        }

        $this->document = new DOMDocument();

        return $this;
    }

    protected function parseTags($startToken, $endToken, $source = null)
    {
        $source  = $source ?: $this->original_html;
        $matches = [];

        $startTokenLength = strlen($startToken);
        $endTokenLength   = strlen($endToken);

        $startFrom = 0;
        $startPos  = 0;
        $endPos    = 0;

        $startPos = stripos($source, $startToken, $startFrom);
        while ($startPos !== false) {
            $endPos = stripos($source, $endToken, $startPos + $startTokenLength);

            if (false === $endPos) {
                break;
            }

            $endPos += $endTokenLength;

            $match     = substr($source, $startPos, $endPos - $startPos);
            $dom       = $this->HtmlToDom($match);
            $matches[] = compact('match', 'dom', 'startPos', 'endPos');
            $startFrom = $endPos;

            unset($match, $dom);
            $startPos = stripos($source, $startToken, $startFrom);
        }

        return $matches;
    }

    protected function parseAndCacheScriptTags()
    {
        if (is_null($this->cacheScriptTags)) {
            $this->cacheScriptTags = $this->parseTags('<script', '</script>');
        }

        return $this->cacheScriptTags;
    }

    protected function parseAndCacheStyleTags()
    {
        if (is_null($this->cacheStyleTags)) {
            $this->cacheStyleTags = $this->parseTags('<style', '</style>');
        }

        return $this->cacheStyleTags;
    }

    protected function parseAndCacheLinkTags()
    {
        if (is_null($this->cacheLinkTags)) {
            $this->cacheLinkTags = $this->parseTags('<link', '>');
        }

        return $this->cacheLinkTags;
    }

    protected function parseAndCacheImgTags()
    {
        if (is_null($this->cacheImgTags)) {
            $this->cacheImgTags = $this->parseTags('<img', '>');
        }

        return $this->cacheImgTags;
    }

    protected function parseAndCacheIframeTags()
    {
        if (is_null($this->cacheIframeTags)) {
            $this->cacheIframeTags = $this->parseTags('<iframe', '</iframe>');
        }

        return $this->cacheIframeTags;
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
        $cloned = $this->document->importNode($dom->cloneNode(true), true);
        $this->document->appendChild($cloned);
        $html = $this->document->saveHtml($cloned);
        $this->document->removeChild($cloned);

        return $html;
    }
}
