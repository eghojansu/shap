<?php

class Shap
{
    const
        PACKAGE = 'eghojansu/shap',
        VERSION = '0.1.2';

    public static $app;
    private static $baseUrl;

    public static function find($ext, $dir, $recursive = false, $keep = true)
    {
        $files = [];
        foreach (glob($dir.'*.'.$ext) as $file) {
            $files[] = $file;
            $keep || unlink($file);
        }
        foreach ($recursive?glob($dir.'*', GLOB_ONLYDIR):[] as $dir)
            $files = array_merge($files, self::find($ext, $dir));

        return $files;
    }

    public static function removeDir($dir)
    {
        $removed = 0;
        foreach (glob($dir.'*') as $path)
            $removed += is_dir($path)?self::removeDir($path):(int) unlink($path);
        $removed += (int) unlink($dir);

        return $removed;
    }

    public static function dump($data, $exit = false)
    {
        echo '<pre>'.print_r($data, true).'</pre>';
        !$exit || exit(0);
        echo '<hr>';
    }

    public static function path($path, $params = null)
    {
        $fw = Base::instance();
        is_array($params) || $params = $fw->parse($params);
        $path = $fw->build(empty($fw['ALIASES'][$path])?$path:$fw['ALIASES'][$path], $params+$fw['PARAMS']);
        return $fw['BASE'].('/'===$path[0]?'':'/').$path;
    }

    public static function url($path = null, $params = null)
    {
        if (!self::$baseUrl) {
            $fw = Base::instance();
            $port = $fw['PORT'];
            self::$baseUrl = $fw['SCHEME'].'://'.$_SERVER['SERVER_NAME'].
                ($port && $port!=80 && $port!=443?':'.$port:'').$fw['BASE'].'/';
        }
        !$path || $path = self::path($path, $params);

        return self::$baseUrl.substr($path, 1);
    }

    public static function clear()
    {
        $fw = Base::instance();
        self::find('php', $fw['TEMP'], true, false);
        $fw->clear('CACHE');
    }

    public static function flash($key)
    {
        $fw = Base::instance();
        $content = $fw->get('SESSION.'.$key);
        $fw->clear('SESSION.'.$key);
        return $content;
    }

    public static function random($len)
    {
        $random = '';
        $alfa = array_merge(range('a','z'), range('A','Z'));
        shuffle($alfa);
        return substr(implode('', $alfa), mt_rand(1,52), $len);
    }

    public static function titleize($str)
    {
        return ucwords(implode(' ', explode('_', F3::snakecase(lcfirst($str)))));
    }

    public static function className($class)
    {
        return F3::snakecase(lcfirst(substr($class, strrpos($class, '\\')+1)));
    }
}
