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

namespace AppSeeds\Resolvers;

use AppSeeds\Contracts\DeferLazyable;
use AppSeeds\Contracts\DeferMinifyable;
use AppSeeds\Contracts\DeferNormalizable;
use AppSeeds\Contracts\DeferPreloadable;
use AppSeeds\Contracts\DeferReorderable;
use AppSeeds\Helpers\DeferAssetUtil;
use AppSeeds\Helpers\DeferConstant;
use AppSeeds\Helpers\DeferJs;
use AppSeeds\Helpers\DeferMinifier;

class ScriptResolver extends DeferResolver implements
    DeferNormalizable,
    DeferReorderable,
    DeferMinifyable,
    DeferLazyable,
    DeferPreloadable
{
    /*
    |--------------------------------------------------------------------------
    | Internal methods
    |--------------------------------------------------------------------------
     */

    public function isDeferJs()
    {
        $id = $this->node->getAttribute('id');

        return in_array($id, [DeferJs::DEFERJS_ID, DeferJs::POLYFILL_ID, DeferJs::HELPERS_JS]);
    }

    /**
     * Return true if it is a JSON tag
     *
     * @return bool
     */
    public function isJson()
    {
        $type = strtolower($this->node->getAttribute('type')) ?: '';

        // Check script type
        if (in_array($type, ['application/json', 'application/ld+json'])) {
            return true;
        }

        return false;
    }

    /**
     * Return true if it is a JavaScript tag
     *
     * @return bool
     */
    public function isJavascript()
    {
        $type = strtolower($this->node->getAttribute('type')) ?: '';

        if (empty($type)
        || strstr($type, '/javascript') !== false
        || $type === $this->options->deferjs_type_attribute) {
            return true;
        }

        return false;
    }

    /**
     * Return true if JavaScript contains eval() or document.write()
     *
     * @return bool
     */
    public function isCriticalJavascript()
    {
        if ($this->isJavascript()) {
            $text = $this->node->getText();

            if (!empty($text)) {
                $check = [
                    'eval(',
                    'document.write(',
                    'dataLayer.push(',
                ];

                foreach ($check as $bad_word) {
                    if (strstr($text, $bad_word) !== false) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Return true if it is a async JavaScript tag
     *
     * @return bool
     */
    public function isSrcJavascript()
    {
        return $this->isJavascript()
            && $this->node->hasAttribute('src');
    }

    /*
    |--------------------------------------------------------------------------
    | DeferNormalizable functions
    |--------------------------------------------------------------------------
     */

    /**
     * {@inheritdoc}
     */
    public function normalize()
    {
        $src = $this->node->getAttribute('src');

        if (!empty($src)) {
            $normalized = DeferAssetUtil::normalizeUrl($src);

            if ($normalized != $src) {
                $this->node->setAttribute('src', $normalized);
            }
        }

        if ($this->isJavascript()) {
            $this->node->removeAttribute('type');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | DeferReorderable functions
    |--------------------------------------------------------------------------
     */

    /**
     * {@inheritdoc}
     */
    public function reposition()
    {
        if ($this->isDeferJs()) {
            $this->node->detach();
            $this->title()->follow($this->node);

            return;
        }

        if (!$this->isJavascript() || $this->isCriticalJavascript()) {
            return;
        }

        if ($this->options->fix_render_blocking) {
            $this->node->detach();
            $this->body()->appendWith($this->node);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | DeferLazyable functions
    |--------------------------------------------------------------------------
     */

    /**
     * {@inheritdoc}
     */
    public function shouldLazyload()
    {
        return parent::shouldLazyload()
            && ($this->hasLazyloadFlag() || $this->isThirdParty());
    }

    /**
     * {@inheritdoc}
     */
    public function lazyload()
    {
        // Only defer for javascript
        if ($this->isJavascript()) {
            if ($this->isDeferJs() ||
                $this->isCriticalJavascript() ||
                $this->skipLazyloading('src')) {
                return false;
            }

            // Remove lazy attributes
            $this->node->removeAttribute(DeferConstant::ATTR_DEFER);
            $this->node->removeAttribute(DeferConstant::ATTR_LAZY);

            // Convert to type=deferjs node
            $this->node->setAttribute('type', $this->options->deferjs_type_attribute);

            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function resolveNoScript()
    {
        return null;
    }

    /*
    |--------------------------------------------------------------------------
    | DeferPreloadable functions
    |--------------------------------------------------------------------------
     */

    /**
     * {@inheritdoc}
     */
    public function isThirdParty()
    {
        return DeferAssetUtil::isThirdParty(
            $this->node->getAttribute('src'),
            $this->options
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getPreloadNode()
    {
        if ($this->isSrcJavascript()
            && ($this->isThirdParty() || !$this->node->hasAttribute('async'))) {
            $preload = $this->newNode('link', [
                'rel'         => LinkResolver::PRELOAD,
                'as'          => 'script',
                'href'        => $this->node->getAttribute('src'),
                'charset'     => $this->node->getAttribute('charset'),
                'integrity'   => $this->node->getAttribute('integrity'),
                'crossorigin' => $this->node->getAttribute('crossorigin'),
            ]);

            return $preload;
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getPreconnectNode()
    {
        if ($this->isSrcJavascript()) {
            $preconnect = $this->newNode('link', [
                'rel'         => LinkResolver::PRECONNECT,
                'href'        => $this->node->getAttribute('src'),
                'crossorigin' => $this->node->getAttribute('crossorigin'),
            ]);

            return $preconnect;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getPrefetchNode()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getDnsPrefetchNode()
    {
        return null;
    }

    /*
    |--------------------------------------------------------------------------
    | DeferMinifyable functions
    |--------------------------------------------------------------------------
     */

    /**
     * {@inheritdoc}
     */
    public function minify()
    {
        $minified = $script = $this->node->getText();

        if (!empty($script) && !$this->isDeferJs()) {
            if ($this->isJavascript()) {
                $minified = DeferMinifier::minifyJs($script);
            } elseif ($this->isJson()) {
                $minified = DeferMinifier::minifyJson($script);
            }
        }

        if (empty($minified) && !$this->node->hasAttribute('src')) {
            $this->node->detach();
        } elseif ($minified != $script) {
            $this->node->nodeValue = '';
            $this->node->setText($minified);
        }
    }
}
