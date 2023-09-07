<?php

/**
 * Defer.php aims to help you concentrate on web performance optimization.
 * (c) 2019-2023 SHIN Company https://shin.company
 *
 * PHP Version >=5.6
 *
 * @category  Web_Performance_Optimization
 * @package   AppSeeds
 * @author    Mai Nhut Tan <shin@shin.company>
 * @copyright 2019-2023 SHIN Company
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
use AppSeeds\Elements\ElementNode;
use AppSeeds\Helpers\DeferAssetUtil;
use AppSeeds\Helpers\DeferConstant;
use AppSeeds\Helpers\DeferJs;
use AppSeeds\Helpers\DeferMinifier;

final class ScriptResolver extends DeferResolver implements DeferNormalizable, DeferReorderable, DeferMinifyable, DeferLazyable, DeferPreloadable
{
    /**
     * @var string[]
     */
    const CHECK = [
        'eval(',
        'document.write(',
        'dataLayer.push(',
    ];

    /**
     * |-----------------------------------------------------------------------
     * | Internal methods
     * |-----------------------------------------------------------------------.
     */
    public function isDeferJs()
    {
        $id = $this->node->getAttribute('id');

        return in_array($id, [DeferJs::DEFERJS_ID, DeferJs::POLYFILL_ID, DeferJs::HELPERS_JS]);
    }

    /**
     * Return true if it is a JSON tag.
     *
     * @return bool
     */
    public function isJson()
    {
        $type = strtolower($this->node->getAttribute('type')) ?: '';

        // Check script type
        return in_array($type, ['application/json', 'application/ld+json']);
    }

    /**
     * Return true if it is a JavaScript tag.
     *
     * @return bool
     */
    public function isJavascript()
    {
        $type = strtolower($this->node->getAttribute('type')) ?: '';
        if (empty($type)) {
            return true;
        }

        if (strstr($type, '/javascript') !== false) {
            return true;
        }

        return $type === $this->options->deferjs_type_attribute;
    }

    /**
     * Return true if JavaScript contains eval() or document.write().
     *
     * @return bool
     */
    public function isCriticalJavascript()
    {
        if ($this->isJavascript()) {
            $text = $this->node->getText();

            if (!empty($text)) {
                foreach (self::CHECK as $bad_word) {
                    if (strstr($text, $bad_word) !== false) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Return true if it is a async JavaScript tag.
     *
     * @return bool
     */
    public function isSrcJavascript()
    {
        if (!$this->isJavascript()) {
            return false;
        }

        return $this->node->hasAttribute('src');
    }

    /**
     * |-----------------------------------------------------------------------
     * | DeferNormalizable functions
     * |-----------------------------------------------------------------------.
     */
    /**
     * {@inheritdoc}
     */
    #[\ReturnTypeWillChange]
    public function normalize()
    {
        $src = $this->node->getAttribute('src');

        if (!empty($src)) {
            $normalized = DeferAssetUtil::normalizeUrl($src);

            if ($normalized !== $src) {
                $this->node->setAttribute('src', $normalized);
            }
        } elseif ($this->isJavascript()) {
            if ($this->node->hasAttribute(DeferConstant::ATTR_ASYNC)) {
                $this->node->removeAttribute(DeferConstant::ATTR_ASYNC);
                $this->node->setAttribute(DeferConstant::ATTR_LAZY, 'true');
            }

            if ($this->node->hasAttribute(DeferConstant::ATTR_DEFER)) {
                $this->node->removeAttribute(DeferConstant::ATTR_DEFER);
                $this->node->setAttribute(DeferConstant::ATTR_LAZY, 'true');
            }
        }

        if ($this->isJavascript()) {
            $this->node->removeAttribute('type');
        }

        // Normalize the Node
        $this->node->normalize();
    }

    /**
     * |-----------------------------------------------------------------------
     * | DeferReorderable functions
     * |-----------------------------------------------------------------------.
     */

    /**
     * {@inheritdoc}
     */
    public function reposition()
    {
        if ($this->isDeferJs()) {
            $this->node->detach();
            $title = $this->title();

            if ($title instanceof ElementNode) {
                // @var ElementNode $title
                $title->follow($this->node);
            }

            return;
        }

        if (!$this->isJavascript()) {
            return;
        }

        if ($this->isCriticalJavascript()) {
            return;
        }

        if ($this->options->fix_render_blocking) {
            $this->node->detach();
            $body = $this->body();

            if ($body instanceof ElementNode) {
                // @var ElementNode $body
                $body->appendWith($this->node);
            }
        }
    }

    /**
     * |-----------------------------------------------------------------------
     * | DeferLazyable functions
     * |-----------------------------------------------------------------------.
     */

    /**
     * {@inheritdoc}
     */
    public function shouldLazyload()
    {
        if (!parent::shouldLazyload()) {
            return false;
        }

        if ($this->hasLazyloadFlag()) {
            return true;
        }

        return $this->isThirdParty();
    }

    /**
     * Check if the node contains "data-lazy" or "defer" attribute.
     *
     * @return bool
     */
    public function hasLazyloadFlag()
    {
        if ($this->node->hasAttribute(DeferConstant::ATTR_DEFER)) {
            return true;
        }

        if ($this->node->hasAttribute(DeferConstant::ATTR_ASYNC)) {
            return true;
        }

        return $this->node->hasAttribute(DeferConstant::ATTR_LAZY);
    }

    /**
     * {@inheritdoc}
     */
    public function lazyload()
    {
        // Only defer for javascript
        if ($this->isJavascript()) {
            if ($this->isDeferJs()) {
                return false;
            }

            if ($this->isCriticalJavascript()) {
                return false;
            }

            if ($this->skipLazyloading('src')) {
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

    /**
     * |-----------------------------------------------------------------------
     * | DeferPreloadable functions
     * |-----------------------------------------------------------------------.
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
        if (!$this->isSrcJavascript()) {
            return null;
        }

        if ($this->node->hasAttribute(DeferConstant::ATTR_ASYNC)) {
            return null;
        }

        return $this->newNode('link', [
            'rel'         => LinkResolver::PRELOAD,
            'as'          => 'script',
            'href'        => $this->node->getAttribute('src'),
            'charset'     => $this->node->getAttribute('charset'),
            'integrity'   => $this->node->getAttribute('integrity'),
            'crossorigin' => $this->node->getAttribute('crossorigin'),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getPreconnectNode()
    {
        if ($this->isSrcJavascript()) {
            return $this->newNode('link', [
                'rel'         => LinkResolver::PRECONNECT,
                'href'        => $this->node->getAttribute('src'),
                'crossorigin' => $this->node->getAttribute('crossorigin'),
            ]);
        }

        return null;
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

    /**
     * |-----------------------------------------------------------------------
     * | DeferMinifyable functions
     * |-----------------------------------------------------------------------.
     */

    /**
     * {@inheritdoc}
     */
    public function minify()
    {
        $minified = $this->node->getText();
        $script   = $minified;
        if (!empty($script) && !$this->isDeferJs()) {
            if ($this->isJavascript()) {
                $minified = DeferMinifier::minifyJs($script);
            } elseif ($this->isJson()) {
                $minified = DeferMinifier::minifyJson($script);
            }
        }

        if (empty($minified) && !$this->node->hasAttribute('src')) {
            $this->node->detach();
        } elseif ($minified !== $script) {
            $this->node->nodeValue = '';
            $this->node->setText($minified);
        }
    }
}
