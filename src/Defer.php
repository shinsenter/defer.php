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

class Defer extends DeferBase
{
    const DEFER_PLUS_SRC   = 'https://raw.githubusercontent.com/shinsenter/defer.js/master/defer_plus.min.js';
    const IGNORE_ATTRIBUTE = 'data-ignore';
    const LAZY_CSS_MEDIA   = 'screen and (max-width: 1px)';

    public static $deferJs;

    public $enableDeferInlineJs = true;
    public $enableDeferLinkCss  = true;
    public $enableDeferStyleCss = true;
    public $enableDeferImages   = true;
    public $enableDeferIframes  = true;

    protected $original_html;

    protected $cacheScriptTags;
    protected $cacheStyleTags;
    protected $cacheLinkTags;
    protected $cacheImgTags;
    protected $cacheIframeTags;
    protected $cacheOutput;

    protected $imgPlaceholder;
    protected $iframePlaceholder;

    public function __construct(string $html, string $charset = null)
    {
        $this->setHtml($html, $charset);
    }

    public function setHtml(string $html, string $charset = null)
    {
        $this->reset();
        $this->setCharset($charset);
        $this->original_html = trim(mb_convert_encoding($html, 'HTML-ENTITIES', $this->sourceCharset));

        return $this;
    }

    public function getHtml()
    {
        return $this->original_html ?: '';
    }

    public function setImgPlaceholder($placeholder)
    {
        $this->imgPlaceholder = $placeholder;
    }

    public function setIframePlaceholder($placeholder)
    {
        $this->iframePlaceholder = $placeholder;
    }

    public function getOptimizedScriptTags($closure = null)
    {
        if (!is_callable($closure)) {
            $closure = [$this, 'optimizedScriptTags'];
        }

        return call_user_func_array($closure, [$this->getScriptTags()]);
    }

    public function getScriptTags()
    {
        return $this->parseAndCacheScriptTags();
    }

    public function getOptimizedStyleTags($closure = null)
    {
        if (!is_callable($closure)) {
            $closure = [$this, 'optimizedStyleTags'];
        }

        return call_user_func_array($closure, [$this->getStyleTags()]);
    }

    public function getStyleTags()
    {
        return $this->parseAndCacheStyleTags();
    }

    public function getOptimizedLinkTags($closure = null)
    {
        if (!is_callable($closure)) {
            $closure = [$this, 'optimizedLinkTags'];
        }

        return call_user_func_array($closure, [$this->getLinkTags()]);
    }

    public function getLinkTags()
    {
        return $this->parseAndCacheLinkTags();
    }

    public function getOptimizedImgTags($closure = null)
    {
        if (!is_callable($closure)) {
            $closure = [$this, 'optimizedImgTags'];
        }

        return call_user_func_array($closure, [$this->getImgTags()]);
    }

    public function getImgTags()
    {
        return $this->parseAndCacheImgTags();
    }

    public function getOptimizedIframeTags($closure = null)
    {
        if (!is_callable($closure)) {
            $closure = [$this, 'optimizedIframeTags'];
        }

        return call_user_func_array($closure, [$this->getIframeTags()]);
    }

    public function getIframeTags()
    {
        return $this->parseAndCacheIframeTags();
    }

    public function deferHtml()
    {
        $render = array_merge(
            [],
            $this->getOptimizedLinkTags(),
            $this->getOptimizedStyleTags(),
            $this->getOptimizedScriptTags(),
            // $this->getOptimizedImgTags(),
            // $this->getOptimizedIframeTags(),
            []
        );

        return $this->List2Html($render);
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
        static::$document = null;

        $this->original_html   = '';
        $this->cacheScriptTags = null;
        $this->cacheStyleTags  = null;
        $this->cacheLinkTags   = null;
        $this->cacheImgTags    = null;
        $this->cacheIframeTags = null;
        $this->cacheOutput     = null;

        $this->imgPlaceholder    = 'data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==';
        $this->iframePlaceholder = 'about:blank';

        return $this;
    }

    public function optimizedLinkTags($list)
    {
        if ($this->enableDeferLinkCss) {
            foreach ($list as $element) {
                if ($element->dom->hasAttribute('onload')) {
                    continue;
                }

                $media = trim(strtolower($element->dom->getAttribute('media'))) ?: 'all';

                if (!in_array($media, ['all', 'screen', 'print'])) {
                    $element->dom->setAttribute('media', static::LAZY_CSS_MEDIA);
                    $element->dom->setAttribute('onload', "this.media='{$media}'");
                }
            }
        }

        return $list;
    }

    public function optimizedStyleTags($list)
    {
        // return $list;
        $group = [
            'all'    => [],
            'screen' => [],
            'print'  => [],
            'others' => [],
        ];

        $main_charset = strtolower($this->sourceCharset);

        foreach ($list as $element) {
            $media = trim(strtolower($element->dom->getAttribute('media'))) ?: 'all';

            if (in_array($media, ['all', 'screen', 'print']) &&
                !empty($styles = $this->minifyCss($element->dom->textContent))) {
                if (!empty($charset = $element->dom->getAttribute('charset')) &&
                    $main_charset != strtolower($charset)) {
                    $styles = mb_convert_encoding($html, $this->sourceCharset, $charset);
                }

                $group[$media][] = $styles;
                unset($styles);
            } else {
                $group['others'][] = $element;
            }
        }

        $results = [];

        if (count($group['all']) > 0) {
            $styles    = '<style>' . implode("\n", $group['all']) . '</style>';
            $results[] = new DeferElement($styles, $this->HtmlToDom($styles), null, null, $this->sourceCharset);
            unset($styles);
        }

        if (count($group['screen']) > 0) {
            $styles    = '<style media="screen">' . implode("\n", $group['screen']) . '</style>';
            $results[] = new DeferElement($styles, $this->HtmlToDom($styles), null, null, $this->sourceCharset);
            unset($styles);
        }

        if (count($group['others']) > 0) {
            foreach ($group['others'] as $element) {
                if ($this->enableDeferLinkCss && !$element->dom->hasAttribute('onload')) {
                    $media = $element->dom->getAttribute('media');
                    $element->dom->setAttribute('media', static::LAZY_CSS_MEDIA);
                    $element->dom->setAttribute('onload', "this.media='{$media}';");
                }

                $results[] = $element;
            }
        }

        if (count($group['print']) > 0) {
            $styles    = '<style media="print">' . implode("\n", $group['print']) . '</style>';
            $results[] = new DeferElement($styles, $this->HtmlToDom($styles), null, null, $this->sourceCharset);
            unset($styles);
        }

        return $results;
    }

    public function optimizedScriptTags($list)
    {
        return $list;
    }

    public function optimizedImgTags($list)
    {
        return $list;
    }

    public function optimizedIframeTags($list)
    {
        return $list;
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

            $html = substr($source, $startPos, $endPos - $startPos);
            $dom  = $this->HtmlToDom($html);

            if (!$dom->hasAttribute(static::IGNORE_ATTRIBUTE)) {
                $matches[] = new DeferElement($html, $dom, $startPos, $endPos, $this->sourceCharset);
            }

            unset($html, $dom);
            $startFrom = $endPos;
            $startPos  = stripos($source, $startToken, $startFrom);
        }

        return $matches;
    }

    protected function parseAndCacheLinkTags()
    {
        if (is_null($this->cacheLinkTags)) {
            $parsed = $this->parseTags('<link', '>');

            $parsed = array_filter($parsed, function ($element) {
                return strtolower($element->dom->getAttribute('rel')) == 'stylesheet' &&
                !empty($element->dom->getAttribute('href'));
            });

            $parsed = array_map(function ($element) {
                return $element->normalizeLinkDom();
            }, $parsed);

            $this->cacheLinkTags = $parsed;
        }

        return $this->cacheLinkTags;
    }

    protected function parseAndCacheStyleTags()
    {
        if (is_null($this->cacheStyleTags)) {
            $parsed = $this->parseTags('<style', '</style>');

            $parsed = array_map(function ($element) {
                return $element->normalizeStyleDom();
            }, $parsed);

            $this->cacheStyleTags = $parsed;
        }

        return $this->cacheStyleTags;
    }

    protected function parseAndCacheScriptTags()
    {
        if (is_null($this->cacheScriptTags)) {
            $parsed = $this->parseTags('<script', '</script>');

            $parsed = array_filter($parsed, function ($element) {
                return empty($type = strtolower($element->dom->getAttribute('type'))) ||
                strstr($type, 'javascript') !== false;
            });

            $parsed = array_map(function ($element) {
                return $element->normalizeScriptDom();
            }, $parsed);

            $this->cacheScriptTags = $parsed;
        }

        return $this->cacheScriptTags;
    }

    protected function parseAndCacheImgTags()
    {
        if (is_null($this->cacheImgTags)) {
            $parsed = $this->parseTags('<img', '>');

            $parsed = array_filter($parsed, function ($element) {
                return !empty(empty($element->dom->getAttribute('src')))
                    && empty($element->dom->getAttribute('data-src'));
            });

            $parsed = array_map(function ($element) {
                return $element->normalizeImgDom($this->imgPlaceholder);
            }, $parsed);

            $this->cacheImgTags = $parsed;
        }

        return $this->cacheImgTags;
    }

    protected function parseAndCacheIframeTags()
    {
        if (is_null($this->cacheIframeTags)) {
            $parsed = $this->parseTags('<iframe', '</iframe>');

            $parsed = array_filter($parsed, function ($element) {
                return !empty(empty($element->dom->getAttribute('src')))
                    && empty($element->dom->getAttribute('data-src'));
            });

            // TODO: tag manipulation
            $parsed = array_map(function ($element) {
                return $element->normalizeIframeDom($this->iframePlaceholder);
            }, $parsed);

            $this->cacheIframeTags = $parsed;
        }

        return $this->cacheIframeTags;
    }

    protected function minifyCss($styles)
    {
        $styles = str_replace('  ', '', str_replace(["\n", "\r", "\t"], '', $styles));

        return trim($styles);
    }

    protected function minifyScript($script)
    {
        return trim($script);
    }

    protected function List2Html($arr)
    {
        return array_map(function ($element) {
            return (string) $element;
        }, (array) $arr);
    }
}
