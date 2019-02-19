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

    public function normalizeLinkDom()
    {
        if ($this->dom) {
            if (strtolower($this->dom->getAttribute('media')) == 'all') {
                $this->dom->removeAttribute('media');
            }

            if (strtolower($this->dom->getAttribute('charset')) == 'utf-8') {
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

    public function normalizeStyleDom()
    {
        if ($this->dom) {
            if (strtolower($this->dom->getAttribute('media')) == 'all') {
                $this->dom->removeAttribute('media');
            }

            if (strtolower($this->dom->getAttribute('charset')) == 'utf-8') {
                $this->dom->removeAttribute('charset');
            }

            if (($type = strtolower($this->dom->getAttribute('type'))) == 'text/css' || empty($type)) {
                $this->dom->removeAttribute('type');
            }

            $this->dom->textContent = trim($this->dom->textContent);
        }

        return $this;
    }

    public function normalizeScriptDom()
    {
        if ($this->dom) {
            if (!empty($this->dom->getAttribute('src'))) {
                $this->dom->setAttribute('defer', true);
                $this->dom->setAttribute('async', 'async');
            } else {
                $this->dom->removeAttribute('defer');
                $this->dom->removeAttribute('async');
                $this->dom->removeAttribute('crossorigin');
            }

            if (strtolower($this->dom->getAttribute('charset')) == 'utf-8') {
                $this->dom->removeAttribute('charset');
            }

            $type = strtolower($this->dom->getAttribute('type'));

            if ($type == 'application/javascript' || $type == 'text/javascript' || empty($type)) {
                $this->dom->removeAttribute('type');
            }

            $this->dom->textContent = trim($this->dom->textContent);
        }

        return $this;
    }

    public function normalizeImgDom($placeholder = null)
    {
        if ($this->dom) {
            if (empty($this->dom->getAttribute('alt'))) {
                $this->dom->setAttribute('alt', 'no alt');
            }

            if (empty($this->dom->getAttribute('data-src')) &&
                !empty($src = $this->dom->getAttribute('src'))) {
                $this->dom->setAttribute('data-src', $src);

                if (!empty($placeholder)) {
                    $this->dom->setAttribute('src', $placeholder);
                } else {
                    $this->dom->removeAttribute('src');
                }
            }

            if (empty($this->dom->getAttribute('data-srcset')) &&
                !empty($srcset = $this->dom->getAttribute('srcset'))) {
                $this->dom->setAttribute('data-srcset', $srcset);
                $this->dom->removeAttribute('srcset');
            }
        }

        return $this;
    }

    public function normalizeIframeDom($placeholder = null)
    {
        if ($this->dom) {
            if (empty($this->dom->getAttribute('tilte'))) {
                $this->dom->setAttribute('tilte', 'no title');
            }

            if (empty($this->dom->getAttribute('data-src')) &&
                !empty($src = $this->dom->getAttribute('src'))) {
                $this->dom->setAttribute('data-src', $src);

                if (!empty($placeholder)) {
                    $this->dom->setAttribute('src', $placeholder);
                } else {
                    $this->dom->removeAttribute('src');
                }
            }

            if (strtolower($this->dom->getAttribute('charset')) == 'utf-8') {
                $this->dom->removeAttribute('charset');
            }
        }

        return $this;
    }
}
