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

class DeferCache
{
    const DS     = DIRECTORY_SEPARATOR;
    const FORMAT = "<?php\n/* %s */\n\$time=%d;\n\$value=%s;\n";

    protected $storage_path;
    protected $storage_level;

    public function __construct($path, $level = 2)
    {
        $this->setPath($path);
        $this->setLevel($level);
    }

    public function put($key, $value, $time = 3600, $comment = '')
    {
        $path   = $this->hashedPath($key);
        $tmp    = $path . '.lock';
        $value  = str_replace('stdClass::__set_state', '(object)', var_export($value, true));
        $cache  = sprintf(static::FORMAT, $comment, time() + $time, $value);

        @mkdir(dirname($path), 0755, true);
        @file_put_contents($tmp, $cache, LOCK_EX);
        @rename($tmp, $path);
    }

    public function validate($key)
    {
        $time  = 0;
        $value = null;
        $path  = $this->hashedPath($key);

        @include $path;

        if (is_null($value) || $time < time()) {
            return null;
        }

        return $value;
    }

    public function forget($key)
    {
        $path = $this->hashedPath($key);

        if (static::fast_file_exists($path)) {
            @unlink($path);
        }
    }

    public function clear()
    {
        $children = static::readdir($this->storage_path, 'd');

        foreach ($children as $child) {
            static::rmdir($child);
        }
    }

    public function has($key)
    {
        return (bool) $this->validate($key);
    }

    public function get($key, $default = null)
    {
        $result = $this->validate($key);

        return is_null($result) ? $default : $result;
    }

    public function getPath()
    {
        return $this->storage_path;
    }

    public function setPath($path)
    {
        $path = str_replace(['/', '\\'], DS, $path);

        if (!static::fast_file_exists($path)) {
            @mkdir($path, 0777, true);
        } elseif (!is_dir($path)) {
            throw new DeferException('Cannot write to cache path.', 1);
        }

        $this->storage_path = rtrim(dirname($path . DS . 'test.txt'), DS);
    }

    public function setLevel($level)
    {
        $this->storage_level = min(1, max(4, (int) $level));
    }

    public static function rmdir($dirPath)
    {
        $files = static::readdir($dirPath);

        foreach ($files as $file) {
            if (is_dir($file)) {
                self::rmdir($file);
            } else {
                unlink($file);
            }
        }

        rmdir($dirPath);
    }

    public static function fast_file_exists($path)
    {
        return (bool) stream_resolve_include_path($path);
    }

    public static function readdir($path, $type = null)
    {
        $list = [];

        if ($handle = @opendir($path)) {
            while (false !== ($item = readdir($handle))) {
                if ($item != '.' && $item !== '..') {
                    $list[] = $path . DS . $item;
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
        $key  = $this->hashCode($key);
        $path = $this->storage_path;

        for ($i = 1; $i <= $this->storage_level; $i++) {
            $path .= DS . substr($key, -$i, $i);
        }

        return $path . DS . $key . '.php';
    }

    protected function hashCode($key)
    {
        return md5($key);
    }
}
