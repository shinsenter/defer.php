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

namespace AppSeeds\Helpers;

use Psr\SimpleCache\CacheInterface;

final class DeferCache implements CacheInterface
{
    /**
     * @var string
     */
    const FORMAT = "<?php\n\n/*\n%s\n*/\n\$expire=%d;\n\$value=%s;\n";

    /**
     * @var int
     */
    const LEVEL = 2;

    /**
     * @var string
     */
    private $path;

    /**
     * @var int
     */
    private $defaultTtl;

    /**
     * @var int
     */
    private $defaultChmod;

    /**
     * @param array<string,mixed> $options
     */
    public function __construct($options = [])
    {
        $path         = empty($options['path']) ? sys_get_temp_dir() : $options['path'];
        $defaultTtl   = empty($options['defaultTtl']) ? null : $options['defaultTtl'];
        $defaultChmod = empty($options['defaultChmod']) ? null : $options['defaultChmod'];

        $this->setPath((string) $path);
        $this->defaultTtl   = (int) ($defaultTtl ?: 900);
        $this->defaultChmod = (int) ($defaultChmod ?: 0777);
    }

    // -------------------------------------------------------------------------

    /**
     * @param string $key
     *
     * @return mixed
     */
    public function __get($key)
    {
        return $this->get($key);
    }

    /**
     * @param string $key
     * @param mixed  $value
     */
    public function __set($key, $value)
    {
        $this->set($key, $value);
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
        } elseif ($ttl instanceof \DateInterval) {
            $ttl = $ttl->s
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
            $cache   = sprintf(self::FORMAT, $comment, $expire, $encoded);

            @mkdir(dirname($path), $this->defaultChmod, true);
            @file_put_contents($tmp, $cache, LOCK_EX);
            @rename($tmp, $path);
        } else {
            $this->delete($key);
        }

        return true;
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

        return true;
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

        return true;
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

    /**
     * @param string $key
     *
     * @return mixed
     */
    public function validate($key)
    {
        $path = $this->hashedPath($key);

        if (!file_exists($path)) {
            return null;
        }

        $value  = null;
        $expire = null;
        @include $path;

        /** @var mixed $value */
        /** @var int|null $expire */
        if (!isset($value, $expire)) {
            return null;
        }

        if ($expire < time()) {
            return null;
        }

        return $value;
    }

    public function getPath()
    {
        return $this->path;
    }

    /**
     * @param string $path
     */
    public function setPath($path)
    {
        $path = realpath(strtr($path, [
            '/'  => DIRECTORY_SEPARATOR,
            '\\' => DIRECTORY_SEPARATOR,
        ]));

        if ($path === false) {
            return;
        }

        if (!$this->exists($path)) {
            @mkdir($path, $this->defaultChmod, true);
        } elseif (!is_dir($path)) {
            throw new DeferException('Cannot write to cache path.', 1);
        }

        $this->path = $path;
    }

    /**
     * @param string $dirPath
     */
    private function rmdir($dirPath)
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

    /**
     * @param string $path
     */
    private function exists($path)
    {
        return (bool) stream_resolve_include_path($path);
    }

    /**
     * @param string $path
     * @param string $type
     */
    private function scan($path, $type = null)
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

    /**
     * @param string $key
     */
    private function hashedPath($key)
    {
        $hashed = $this->hash($key);
        $path   = $this->path;

        if (strstr(PHP_SAPI, 'cli') !== false) {
            $path .= DIRECTORY_SEPARATOR . 'cli';
        } elseif (!empty($_SERVER['HTTP_HOST'])) {
            $path .= DIRECTORY_SEPARATOR . $_SERVER['HTTP_HOST'];
        }

        for ($i = 1; $i <= self::LEVEL; $i++) {
            $path .= DIRECTORY_SEPARATOR . substr($hashed, -$i, $i);
        }

        return $path . DIRECTORY_SEPARATOR . $hashed . '.php';
    }

    /**
     * @param string $key
     */
    private function hash($key)
    {
        return hash('adler32', $key);
    }
}
