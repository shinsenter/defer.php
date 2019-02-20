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

class DeferElement extends DeferBase
{
    public $html;
    public $dom;
    public $startPos;
    public $endPos;

    /**
     * @author Mai Nhut Tan
     * @since  1.0.0
     * @param  $html
     * @param  $dom
     * @param null $startPos
     * @param null $endPos
     * @param null $charset
     */
    public function __construct($html = '', $dom = null, $startPos = null, $endPos = null, $charset = null)
    {
        if ($dom) {
            $dom->normalize();
        }

        $this->html     = $html;
        $this->dom      = $dom;
        $this->startPos = $startPos;
        $this->endPos   = $endPos;

        $this->setCharset($charset);
    }

    public function __toString()
    {
        return "{$this->startPos}:{$this->endPos} " . parent::__toString();
    }

    public function toHtml()
    {
        return parent::__toString();
    }

    /**
     * @author Mai Nhut Tan
     * @since  1.0.0
     * @return mixed
     */
    public function normalizeLinkDom()
    {
        if (is_a($this->dom, 'DOMElement')) {
            if (strtolower($this->dom->getAttribute('media')) == 'all') {
                $this->dom->removeAttribute('media');
            }

            if (strtolower($this->dom->getAttribute('charset')) == strtolower($this->sourceCharset)) {
                $this->dom->removeAttribute('charset');
            }

            if (strtolower($this->dom->getAttribute('property')) == 'stylesheet') {
                $this->dom->removeAttribute('property');
            }

            if (($type = strtolower($this->dom->getAttribute('type'))) == 'text/css' || empty($type)) {
                $this->dom->removeAttribute('type');
            }
        }

        return $this;
    }

    /**
     * @author Mai Nhut Tan
     * @since  1.0.0
     * @return mixed
     */
    public function optimizeLinkDom()
    {
        if (is_a($this->dom, 'DOMElement') &&
            !$this->dom->hasAttribute('onload')) {
            $media = trim(strtolower($this->dom->getAttribute('media'))) ?: 'all';

            if (!in_array($media, ['all', 'screen', 'print'])) {
                $this->dom->setAttribute('media', static::LAZY_CSS_MEDIA);
                $this->dom->setAttribute('onload', "this.media='{$media}'");
            }
        }

        return $this;
    }

    /**
     * @author Mai Nhut Tan
     * @since  1.0.0
     * @return mixed
     */
    public function normalizeStyleDom()
    {
        if (is_a($this->dom, 'DOMElement')) {
            if (strtolower($this->dom->getAttribute('media')) == 'all') {
                $this->dom->removeAttribute('media');
            }

            if (strtolower($this->dom->getAttribute('charset')) == strtolower($this->sourceCharset)) {
                $this->dom->removeAttribute('charset');
            }

            if (($type = strtolower($this->dom->getAttribute('type'))) == 'text/css' || empty($type)) {
                $this->dom->removeAttribute('type');
            }
        }

        return $this;
    }

    /**
     * @author Mai Nhut Tan
     * @since  1.0.0
     * @return mixed
     */
    public function optimizeStyleDom()
    {
        if (is_a($this->dom, 'DOMElement')) {
            $inner = $this->dom->textContent;
            $inner = preg_replace('/\/\*.*?\*\//', '', $inner);
            $inner = str_replace('  ', '', str_replace(["\n", "\r", "\t"], '', $inner));

            $this->dom->textContent = trim($inner);
        }

        return $this;
    }

    /**
     * @author Mai Nhut Tan
     * @since  1.0.0
     * @return mixed
     */
    public function normalizeScriptDom()
    {
        if (is_a($this->dom, 'DOMElement')) {
            if (empty($this->dom->getAttribute('src'))) {
                $this->dom->removeAttribute('defer');
                $this->dom->removeAttribute('async');
                $this->dom->removeAttribute('crossorigin');
            }

            if (strtolower($this->dom->getAttribute('charset')) == strtolower($this->sourceCharset)) {
                $this->dom->removeAttribute('charset');
            }

            $type = strtolower($this->dom->getAttribute('type'));

            if ($type == 'application/javascript' || $type == 'text/javascript' || empty($type)) {
                $this->dom->removeAttribute('type');
            }
        }

        return $this;
    }

    /**
     * @author Mai Nhut Tan
     * @since  1.0.0
     * @return mixed
     */
    public function optimizeScriptDom()
    {
        if (is_a($this->dom, 'DOMElement')) {
            $datalazy = null;

            if ($this->dom->hasAttribute('data-lazy')) {
                $datalazy = (int) $this->dom->getAttribute('data-lazy') ?: 0;
                $this->dom->removeAttribute('data-lazy');
            }

            if (!empty($src = $this->dom->getAttribute('src'))) {
                if (!is_null($datalazy)) {
                    $id = 'lazy-' . preg_replace(['/^.*\//', '/[^a-z0-9]+/i'], ['', '-'], $src);

                    $this->dom->textContent = "deferscript('{$src}', '{$id}', {$datalazy});";
                    $this->dom->removeAttribute('src');
                    $this->dom->removeAttribute('defer');
                    $this->dom->removeAttribute('async');
                } else {
                    $this->dom->setAttribute('defer', true);
                    $this->dom->setAttribute('async', 'async');
                }
            } else {
                $this->dom->textContent = trim(preg_replace('/(^<!--[\t\040]*|[\t\040]*\/\/[\t\040]*-->$)/', '', $this->dom->textContent));

                if (!is_null($datalazy)) {
                    $this->dom->textContent = "defer(function(){ // start defer\n" . $this->dom->textContent . ", {$datalazy}); // end defer";
                }
            }
        }

        return $this;
    }

    /**
     * @author Mai Nhut Tan
     * @since  1.0.0
     * @param  $placeholder
     * @return mixed
     */
    public function normalizeImgDom($placeholder = null)
    {
        if (is_a($this->dom, 'DOMElement')) {
            if (empty($this->dom->getAttribute('alt'))) {
                $this->dom->setAttribute('alt', 'no alt');
            }
        }

        return $this;
    }

    /**
     * @author Mai Nhut Tan
     * @since  1.0.0
     * @param  $placeholder
     * @return mixed
     */
    public function optimizeImgDom($placeholder = null)
    {
        if (is_a($this->dom, 'DOMElement')) {
            if (empty($this->dom->getAttribute('data-src')) &&
                !empty($src = $this->dom->getAttribute('src'))) {
                $this->dom->setAttribute('data-src', $src);

                if (!empty($placeholder)) {
                    $this->dom->setAttribute('src', $placeholder);
                } else {
                    $this->dom->removeAttribute('src');
                }

                $class_names   = explode(' ', $this->dom->getAttribute('class') ?: '');
                $class_names[] = 'deferjs';
                $class_names   = array_unique(array_filter($class_names));
                $this->dom->setAttribute('class', implode(' ', $class_names));
            }

            if (empty($this->dom->getAttribute('data-srcset')) &&
                !empty($srcset = $this->dom->getAttribute('srcset'))) {
                $this->dom->setAttribute('data-srcset', $srcset);
                $this->dom->removeAttribute('srcset');
            }
        }

        return $this;
    }

    /**
     * @author Mai Nhut Tan
     * @since  1.0.0
     * @param  $placeholder
     * @return mixed
     */
    public function normalizeIframeDom($placeholder = null)
    {
        if (is_a($this->dom, 'DOMElement')) {
            if (empty($this->dom->getAttribute('tilte'))) {
                $this->dom->setAttribute('tilte', 'no title');
            }

            if (strtolower($this->dom->getAttribute('charset')) == strtolower($this->sourceCharset)) {
                $this->dom->removeAttribute('charset');
            }
        }

        return $this;
    }

    /**
     * @author Mai Nhut Tan
     * @since  1.0.0
     * @param  $placeholder
     * @return mixed
     */
    public function optimizeIframeDom($placeholder = null)
    {
        if (is_a($this->dom, 'DOMElement')) {
            if (empty($this->dom->getAttribute('data-src')) &&
                !empty($src = $this->dom->getAttribute('src'))) {
                $this->dom->setAttribute('data-src', $src);

                if (!empty($placeholder)) {
                    $this->dom->setAttribute('src', $placeholder);
                } else {
                    $this->dom->removeAttribute('src');
                }

                $class_names   = explode(' ', $this->dom->getAttribute('class') ?: '');
                $class_names[] = 'deferjs';
                $class_names   = array_unique(array_filter($class_names));
                $this->dom->setAttribute('class', implode(' ', $class_names));
            }
        }

        return $this;
    }
}
