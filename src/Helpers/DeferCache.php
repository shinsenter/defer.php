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

namespace AppSeeds\Helpers;

use DateInterval;
use Psr\SimpleCache\CacheInterface;

class DeferCache implements CacheInterface
{
    const FORMAT = "<?php\n\n/*\n%s\n*/\n\$expire=%d;\n\$value=%s;\n";
    const LEVEL  = 2;

    protected $path;
    protected $defaultTtl;
    protected $defaultChmod;

    public function __construct($options = [])
    {
        $path         = !empty($options['path']) ? $options['path'] : sys_get_temp_dir();
        $defaultTtl   = !empty($options['defaultTtl']) ? $options['defaultTtl'] : null;
        $defaultChmod = !empty($options['defaultChmod']) ? $options['defaultChmod'] : null;

        $this->setPath($path);
        $this->defaultTtl   = $defaultTtl ?: 900;
        $this->defaultChmod = $defaultChmod ?: 0777;
    }

    // -------------------------------------------------------------------------

    public function __get($key)
    {
        return $this->get($key);
    }

    public function __set($key, $value)
    {
        return $this->set($key, $value);
    }

    // -------------------------------------------------------------------------

    /**
     * {@inheritdoc}
     */
    public function get($key, $default = null)
    {
        $value = $this->validate($key);

        return is_null($value) ? $default : $value;
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value, $ttl = null)
    {
        if (is_null($ttl)) {
            $ttl = $this->defaultTtl;
        } elseif ($ttl instanceof DateInterval) {
            $ttl = ($ttl->s)
                    + ($ttl->i * 60)
                    + ($ttl->h * 60 * 60)
                    + ($ttl->d * 60 * 60 * 24)
                    + ($ttl->m * 60 * 60 * 24 * 30)
                    + ($ttl->y * 60 * 60 * 24 * 365);
        }

        if ($ttl > 0) {
            $expire  = time() + $ttl;
            $path    = $this->hashedPath($key);
            $tmp     = $path . '.lock';
            $encoded = strtr(var_export($value, true), ['stdClass::__set_state' => '(object)']);
            $comment = sprintf("%s\nKey: %s\nDuration: %d seconds", date('Y-m-d H:i:s'), $key, $ttl);
            $cache   = (sprintf(self::FORMAT, $comment, $expire, $encoded));

            @mkdir(dirname($path), $this->defaultChmod, true);
            @file_put_contents($tmp, $cache, LOCK_EX);
            @rename($tmp, $path);
        } else {
            $this->delete($key);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function delete($key)
    {
        $path = $this->hashedPath($key);

        if ($this->exists($path)) {
            return @unlink($path);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        $dirs = $this->scan($this->path, 'd');

        foreach ($dirs as $child) {
            $this->rmdir($child);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getMultiple($keys, $default = null)
    {
        $values = [];

        foreach ($keys as $key) {
            $values[$key] = $this->get($key, $default);
        }

        return $values;
    }

    /**
     * {@inheritdoc}
     */
    public function setMultiple($values, $ttl = null)
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value, $ttl);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function deleteMultiple($keys)
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function has($key)
    {
        return $this->validate($key) !== null;
    }

    // -------------------------------------------------------------------------

    public function validate($key)
    {
        $expire = 0;
        $value  = null;
        $path   = $this->hashedPath($key);

        @include $path;

        if (is_null($value) || $expire < time()) {
            return null;
        }

        return $value;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function setPath($path)
    {
        $path = realpath(strtr($path, [
            '/'  => DIRECTORY_SEPARATOR,
            '\\' => DIRECTORY_SEPARATOR,
        ]));

        if (!$this->exists($path)) {
            @mkdir($path, $this->defaultChmod, true);
        } elseif (!is_dir($path)) {
            throw new DeferException('Cannot write to cache path.', 1);
        }

        $this->path = $path;
    }

    protected function rmdir($dirPath)
    {
        $files = $this->scan($dirPath);

        foreach ($files as $file) {
            if (is_dir($file)) {
                $this->rmdir($file);
            } else {
                @unlink($file);
            }
        }

        @rmdir($dirPath);
    }

    protected function exists($path)
    {
        return (bool) stream_resolve_include_path($path);
    }

    protected function scan($path, $type = null)
    {
        $list   = [];
        $handle = @opendir($path);

        if ($handle) {
            while (false !== ($item = readdir($handle))) {
                if ($item != '.' && $item !== '..') {
                    $list[] = $path . DIRECTORY_SEPARATOR . $item;
                }
            }

            closedir($handle);

            if ($type == 'd') {
                $list = array_filter($list, 'is_dir');
            } elseif ($type == 'f') {
                $list = array_filter($list, 'is_file');
            }
        }

        return $list;
    }

    protected function hashedPath($key)
    {
        $hashed = $this->hash($key);
        $path   = $this->path;

        if (strstr(php_sapi_name(), 'cli') !== false) {
            $path .= DIRECTORY_SEPARATOR . 'cli';
        } elseif (!empty(($_SERVER['HTTP_HOST']))) {
            $path .= DIRECTORY_SEPARATOR . $_SERVER['HTTP_HOST'];
        }

        for ($i = 1; $i <= self::LEVEL; $i++) {
            $path .= DIRECTORY_SEPARATOR . substr($hashed, -$i, $i);
        }

        $path = $path . DIRECTORY_SEPARATOR . $hashed . '.php';

        return $path;
    }

    protected function hash($key)
    {
        return hash('adler32', $key);
    }
}
