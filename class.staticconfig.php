<?php

/**
 * Class StaticConfig
 */
class StaticConfig
{
    private static $config = array();
    private static $pdo_connection;

    /**
     * @param $filepath
     * @param string $subpath
     */
    public static function init($filepath, $subpath = '')
    {
        if (file_exists($filepath)) {
            $new_config = parse_ini_file($filepath, true);

            if ($subpath == "" || $subpath == '/') {
                foreach ($new_config as $key => $part) {
                    if (array_key_exists($key, self::$config)) {
                        self::$config[$key] = array_merge(self::$config[$key], $part);
                    } else {
                        self::$config[$key] = $part;
                    }
                }

            } else {
                self::$config["{$subpath}"] = $new_config;
            }

            unset($new_config);
        } else {
            die("<strong>FATAL ERROR:</strong> Config file `{$filepath}` not found. ");
        }
    }

    /**
     * @param $filepath
     * @param string $subpath
     */
    public static function append($filepath, $subpath = '')
    {
        self::init($filepath, $subpath);
    }

    /**
     * @param $key
     * @return array|null
     */
    public static function key($key)
    {
        $path = explode('/', $key);

        if ($path[0] == '') unset($path[0]);

        $r = self::$config;

        foreach ($path as $path_key => $path_value) {
            if (isset($r[$path_value])) {
                $r = $r[$path_value];
            } else return NULL;
        }
        unset($path);
        return $r;
    }

    /**
     * @return array
     */
    public static function getAll()
    {
        return self::$config;
    }

    public static function setPDO(\PDO $dbc)
    {
        if (get_class($dbc) === "DBConnection") {
            self::$pdo_connection = $dbc->getconnection();
        } elseif (get_class($dbc) == "PDO") {
            self::$pdo_connection = $dbc;
        } else {
            return false;
        }

        return true;
    }

    /**
     * @return mixed
     */
    public static function getPDO()
    {
        return self::$pdo_connection;
    }


    /**
     *
     */
    protected function __construct()
    {
    }

    private function __clone()
    {
    }

    private function __wakeup()
    {
    }
}
