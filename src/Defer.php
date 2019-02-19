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

    public $enableDeferScripts = true;
    public $enableDeferCss     = true;
    public $enableDeferImages  = true;
    public $enableDeferIframes = true;

    protected $original_html;

    protected $cacheCommentTags;
    protected $cacheScriptTags;
    protected $cacheStyleTags;
    protected $cacheLinkTags;
    protected $cacheImgTags;
    protected $cacheIframeTags;
    protected $cacheOutput;

    protected $imgPlaceholder;
    protected $iframePlaceholder;

    /**
     * @author Mai Nhut Tan
     * @since  1.0.0
     * @param string $html
     * @param string $charset
     */
    public function __construct(string $html, string $charset = null)
    {
        $this->setHtml($html, $charset);
    }

    /**
     * @author Mai Nhut Tan
     * @since  1.0.0
     * @return mixed
     */
    public function deferHtml()
    {
        if (!is_null($this->cacheOutput)) {
            return $this->cacheOutput;
        }

        $html = $this->original_html;

        $offset = 0;
        $render = $this->sortOffset(array_merge(
            $this->enableDeferImages ? $this->getOptimizedImgTags() : [],
            $this->enableDeferIframes ? $this->getOptimizedIframeTags() : []
        ));

        foreach ($render as $element) {
            $length    = $element->endPos - $element->startPos;
            $replace   = $element->toHtml();
            $newlength = strlen($replace);
            $html      = substr_replace($html, $replace, $element->startPos - $offset, $length);
            $offset += ($length - $newlength);
        }

        $this->original_html = $html;

        $offset = 0;
        $render = $this->sortOffset(array_merge(
            $this->getCommentTags(),
            $this->enableDeferCss ? $this->getLinkTags() : [],
            $this->enableDeferCss ? $this->getStyleTags() : [],
            $this->enableDeferScripts ? $this->getScriptTags() : []
        ));

        foreach ($render as $element) {
            $length = $element->endPos - $element->startPos;
            $html   = substr_replace($html, '', $element->startPos - $offset, $length);
            $offset += $length;
        }

        $endHead = $this->parseTags('</head', '>', $html, true);

        if (count($endHead) > 0) {
            $styles = array_map(function ($element) {
                return $element->toHtml();
            }, array_merge(
                [],
                $this->enableDeferCss ? $this->getLinkTags() : [],
                $this->enableDeferCss ? $this->getStyleTags() : []
            ));

            $styles[] = $this->getDeferJs();

            if (count($styles) > 0) {
                $html = substr_replace($html, "\n" . implode("\n", $styles) . "\n", $endHead[0]->startPos, 0);
            }
        }

        $endBody = $this->parseTags('</body', '>', $html, true);

        if (count($endBody) > 0) {
            $scripts = array_map(function ($element) {
                return $element->toHtml();
            }, array_merge(
                [],
                $this->enableDeferScripts ? $this->getScriptTags() : []
            ));

            if (count($scripts) > 0) {
                $html = substr_replace($html, "\n" . implode("\n", $scripts) . "\n", $endBody[0]->startPos, 0);
            }
        }

        $this->cacheOutput = mb_convert_encoding($html, $this->sourceCharset, 'HTML-ENTITIES');

        return $this->cacheOutput;
    }

    /**
     * @author Mai Nhut Tan
     * @since  1.0.0
     * @param  string $html
     * @param  string $charset
     * @return mixed
     */
    public function setHtml(string $html, string $charset = null)
    {
        $this->reset();
        $this->setCharset($charset);
        $this->original_html = trim(mb_convert_encoding($html, 'HTML-ENTITIES', $this->sourceCharset));

        return $this;
    }

    /**
     * @author Mai Nhut Tan
     * @since  1.0.0
     * @return mixed
     */
    public function getHtml()
    {
        return $this->original_html ?: '';
    }

    /**
     * @author Mai Nhut Tan
     * @since  1.0.0
     * @param  $placeholder
     */
    public function setImgPlaceholder($placeholder)
    {
        $this->imgPlaceholder = $placeholder;
    }

    /**
     * @author Mai Nhut Tan
     * @since  1.0.0
     * @param  $placeholder
     */
    public function setIframePlaceholder($placeholder)
    {
        $this->iframePlaceholder = $placeholder;
    }

    /**
     * @author Mai Nhut Tan
     * @since  1.0.0
     * @param  $closure
     */
    public function getOptimizedCommentTags($closure = null)
    {
        if (!is_callable($closure)) {
            $closure = [$this, 'optimizedCommentTags'];
        }

        return call_user_func_array($closure, [$this->getCommentTags()]);
    }

    /**
     * @author Mai Nhut Tan
     * @since  1.0.0
     * @return mixed
     */
    public function getCommentTags()
    {
        return $this->parseAndCacheCommentTags();
    }

    /**
     * @author Mai Nhut Tan
     * @since  1.0.0
     * @param  $closure
     */
    public function getOptimizedLinkTags($closure = null)
    {
        if (!is_callable($closure)) {
            $closure = [$this, 'optimizedLinkTags'];
        }

        return call_user_func_array($closure, [$this->getLinkTags()]);
    }

    /**
     * @author Mai Nhut Tan
     * @since  1.0.0
     * @return mixed
     */
    public function getLinkTags()
    {
        return $this->parseAndCacheLinkTags();
    }

    /**
     * @author Mai Nhut Tan
     * @since  1.0.0
     * @param  $closure
     */
    public function getOptimizedStyleTags($closure = null)
    {
        if (!is_callable($closure)) {
            $closure = [$this, 'optimizedStyleTags'];
        }

        return call_user_func_array($closure, [$this->getStyleTags()]);
    }

    /**
     * @author Mai Nhut Tan
     * @since  1.0.0
     * @return mixed
     */
    public function getStyleTags()
    {
        return $this->parseAndCacheStyleTags();
    }

    /**
     * @author Mai Nhut Tan
     * @since  1.0.0
     * @param  $closure
     */
    public function getOptimizedScriptTags($closure = null)
    {
        if (!is_callable($closure)) {
            $closure = [$this, 'optimizedScriptTags'];
        }

        return call_user_func_array($closure, [$this->getScriptTags()]);
    }

    /**
     * @author Mai Nhut Tan
     * @since  1.0.0
     * @return mixed
     */
    public function getScriptTags()
    {
        return $this->parseAndCacheScriptTags();
    }

    /**
     * @author Mai Nhut Tan
     * @since  1.0.0
     * @param  $closure
     */
    public function getOptimizedImgTags($closure = null)
    {
        if (!is_callable($closure)) {
            $closure = [$this, 'optimizedImgTags'];
        }

        return call_user_func_array($closure, [$this->getImgTags()]);
    }

    /**
     * @author Mai Nhut Tan
     * @since  1.0.0
     * @return mixed
     */
    public function getImgTags()
    {
        return $this->parseAndCacheImgTags();
    }

    /**
     * @author Mai Nhut Tan
     * @since  1.0.0
     * @param  $closure
     */
    public function getOptimizedIframeTags($closure = null)
    {
        if (!is_callable($closure)) {
            $closure = [$this, 'optimizedIframeTags'];
        }

        return call_user_func_array($closure, [$this->getIframeTags()]);
    }

    /**
     * @author Mai Nhut Tan
     * @since  1.0.0
     * @return mixed
     */
    public function getIframeTags()
    {
        return $this->parseAndCacheIframeTags();
    }

    /**
     * @author Mai Nhut Tan
     * @since  1.0.0
     * @return mixed
     */
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
            $script = @file_get_contents(static::DEFER_PLUS_SRC);

            if (!empty($script)) {
                $scripts = [
                    '<script id="deferjs" type="text/javascript">' . $script . '</script>',
                    '<script id="deferjs-lazy">deferiframe(\'deferjs\', 500);deferimg(\'deferjs\', 500);</script>',
                ];
                static::$deferJs = implode("\n", $scripts);
            }
        }

        return static::$deferJs;
    }

    /**
     * @author Mai Nhut Tan
     * @since  1.0.0
     * @return mixed
     */
    public function reset()
    {
        static::$document = null;

        $this->original_html = '';

        $this->cacheCommentTags = null;
        $this->cacheScriptTags  = null;
        $this->cacheStyleTags   = null;
        $this->cacheLinkTags    = null;
        $this->cacheImgTags     = null;
        $this->cacheIframeTags  = null;
        $this->cacheOutput      = null;

        $this->imgPlaceholder    = 'data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==';
        $this->iframePlaceholder = 'about:blank';

        return $this;
    }

    /**
     * @author Mai Nhut Tan
     * @since  1.0.0
     * @param  $list
     * @return mixed
     */
    public function optimizedCommentTags($list)
    {
        return $list;
    }

    /**
     * @author Mai Nhut Tan
     * @since  1.0.0
     * @param  $list
     */
    public function optimizedLinkTags($list)
    {
        if ($this->enableDeferCss) {
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

    /**
     * @author Mai Nhut Tan
     * @since  1.0.0
     * @param  $list
     */
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
                !empty($styles = $element->dom->textContent)) {
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
                if ($this->enableDeferCss && !$element->dom->hasAttribute('onload')) {
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

    /**
     * @author Mai Nhut Tan
     * @since  1.0.0
     * @param  $list
     * @return mixed
     */
    public function optimizedScriptTags($list)
    {
        return $list;
    }

    /**
     * @author Mai Nhut Tan
     * @since  1.0.0
     * @param  $list
     * @return mixed
     */
    public function optimizedImgTags($list)
    {
        return $list;
    }

    /**
     * @author Mai Nhut Tan
     * @since  1.0.0
     * @param  $list
     * @return mixed
     */
    public function optimizedIframeTags($list)
    {
        return $list;
    }

    /**
     * @author Mai Nhut Tan
     * @since  1.0.0
     * @param  $startToken
     * @param  $endToken
     * @param  $source
     * @param  null  $no_dom
     * @return mixed
     */
    protected function parseTags($startToken, $endToken, $source = null, $no_dom = false)
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
            $dom  = $no_dom ? null : $this->HtmlToDom($html);

            if (is_null($dom) ||
                is_a($dom, 'DOMComment') ||
                (is_a($dom, 'DOMElement') && !$dom->hasAttribute(static::IGNORE_ATTRIBUTE))) {
                $matches[] = new DeferElement($html, $dom, $startPos, $endPos, $this->sourceCharset);
            }

            unset($html, $dom);
            $startFrom = $endPos;
            $startPos  = stripos($source, $startToken, $startFrom);
        }

        return $matches;
    }

    /**
     * @author Mai Nhut Tan
     * @since  1.0.0
     * @return mixed
     */
    protected function parseAndCacheCommentTags()
    {
        if (is_null($this->cacheCommentTags)) {
            $parsed = $this->parseTags('<!--', '-->');

            $parsed = array_filter($parsed, function ($element) {
                switch (true) {
                    case preg_match('/(<!--[\t\040]*\[if|\/\/[\t\040]*-->$)/i', $element->html):
                        return false;
                    default:break;
                }

                return true;
            });

            $this->cacheCommentTags = $parsed;
        }

        return $this->cacheCommentTags;
    }

    /**
     * @author Mai Nhut Tan
     * @since  1.0.0
     * @return mixed
     */
    protected function parseAndCacheLinkTags()
    {
        if (is_null($this->cacheLinkTags)) {
            $parsed = $this->parseTags('<link', '>');

            $parsed = array_filter($parsed, function ($element) {
                switch (true) {
                    case strtolower($element->dom->getAttribute('rel')) != 'stylesheet':
                    case empty($element->dom->getAttribute('href')):
                        return false;
                    default:break;
                }

                return true;
            });

            $parsed = array_map(function ($element) {
                return $element->normalizeLinkDom();
            }, $parsed);

            $this->cacheLinkTags = $parsed;
        }

        return $this->cacheLinkTags;
    }

    /**
     * @author Mai Nhut Tan
     * @since  1.0.0
     * @return mixed
     */
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

    /**
     * @author Mai Nhut Tan
     * @since  1.0.0
     * @return mixed
     */
    protected function parseAndCacheScriptTags()
    {
        if (is_null($this->cacheScriptTags)) {
            $parsed = $this->parseTags('<script', '</script>');

            $parsed = array_filter($parsed, function ($element) {
                $type = strtolower($element->dom->getAttribute('type'));

                switch (true) {
                    case !empty($type) && strpos($type, 'javascript') === false:
                    case strpos($element->html, 'document.write(') !== false:
                        return false;
                    default:break;
                }

                return true;
            });

            $parsed = array_map(function ($element) {
                return $element->normalizeScriptDom();
            }, $parsed);

            $this->cacheScriptTags = $parsed;
        }

        return $this->cacheScriptTags;
    }

    /**
     * @author Mai Nhut Tan
     * @since  1.0.0
     * @return mixed
     */
    protected function parseAndCacheImgTags()
    {
        if (is_null($this->cacheImgTags)) {
            $parsed = $this->parseTags('<img', '>');

            $parsed = array_filter($parsed, function ($element) {
                switch (true) {
                    case !empty($element->dom->getAttribute('data-src')):
                    case empty($element->dom->getAttribute('src')):
                        return false;
                    default:break;
                }

                return true;
            });

            $parsed = array_map(function ($element) {
                return $element->normalizeImgDom($this->imgPlaceholder);
            }, $parsed);

            $this->cacheImgTags = $parsed;
        }

        return $this->cacheImgTags;
    }

    /**
     * @author Mai Nhut Tan
     * @since  1.0.0
     * @return mixed
     */
    protected function parseAndCacheIframeTags()
    {
        if (is_null($this->cacheIframeTags)) {
            $parsed = $this->parseTags('<iframe', '</iframe>');

            $parsed = array_filter($parsed, function ($element) {
                switch (true) {
                    case !empty($element->dom->getAttribute('data-src')):
                    case empty($element->dom->getAttribute('src')):
                        return false;
                    default:break;
                }

                return true;
            });

            // TODO: tag manipulation
            $parsed = array_map(function ($element) {
                return $element->normalizeIframeDom($this->iframePlaceholder);
            }, $parsed);

            $this->cacheIframeTags = $parsed;
        }

        return $this->cacheIframeTags;
    }

    /**
     * @author Mai Nhut Tan
     * @since  1.0.0
     * @param  $list
     * @return mixed
     */
    protected function sortOffset($list)
    {
        usort($list, function ($a, $b) {
            return $a->startPos - $b->startPos;
        });

        return $list;
    }

    /**
     * @author Mai Nhut Tan
     * @since  1.0.0
     * @param  $arr
     */
    protected function List2Html($arr)
    {
        return array_map(function ($element) {
            return (string) $element;
        }, (array) $arr);
    }
}
