<?php
/**
@todo: Специальные функции удаления ключей:
->delete('price/*');      // удалить все элементы из секции prices кроме самой секции
->delete('price/source'); // удалить ->price->source
->delete('price');        // удалить секцию price
 */

/**
 * Class StaticConfig
 */
class INI_Config
{
    const GLUE = '/';
    private $config = array();

    /**
     * @param $filepath
     * @param string $subpath
     */
    public function __construct($filepath, $subpath = '')
    {
        $this->init($filepath, $subpath);
    }

    /**
     * @param $filepath
     * @param string $subpath
     */
    public function init($filepath, $subpath = '')
    {
        if (file_exists($filepath)) {
            $new_config = parse_ini_file($filepath, true);

            if ($subpath == "" || $subpath == $this::GLUE) {
                foreach ($new_config as $key => $part) {
                    if (array_key_exists($key, $this->config)) {
                        $this->config[$key] = array_merge($this->config[$key], $part);
                    } else {
                        $this->config[$key] = $part;
                    }
                }

            } else {
                $this->config["{$subpath}"] = $new_config;
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
    public function append($filepath, $subpath = '')
    {
        $this->init($filepath, $subpath);
    }
    //-------------------------------------------------------------------------------------------------------
    // https://stackoverflow.com/a/44189105/5127037

    /**
     * @param $parents
     * @param null $default
     * @return array|null
     */
    public function get($parents, $default = NULL)
    {
        if ($parents === '') {
            return $default;
        }

        if (!is_array($parents)) {
            $parents = explode($this::GLUE, $parents);
        }

        $ref = &$this->config;

        foreach ((array) $parents as $parent) {
            if (is_array($ref) && array_key_exists($parent, $ref)) {
                $ref = &$ref[$parent];
            } else {
                return null;
            }
        }
        return $ref;
    }

    /**
     * @param $parents
     * @param $value
     * @return bool
     */
    public function set($parents, $value)
    {
        if (!is_array($parents)) {
            $parents = explode($this::GLUE, (string) $parents);
        }

        if (empty($parents)) return false;

        $ref = &$this->config;

        foreach ($parents as $parent) {
            if (isset($ref) && !is_array($ref)) {
                $ref = array();
            }

            $ref = &$ref[$parent];
        }

        $ref = $value;
        return true;
    }

    /**
     * @param array $array
     * @param array|string $parents
     */
    private function array_unset_value(&$array, $parents)
    {
        if (!is_array($parents)) {
            $parents = explode($this::GLUE, $parents);
        }

        $key = array_shift($parents);

        if (empty($parents)) {
            unset($array[$key]);
        } else {
            $this->array_unset_value($array[$key], $parents);
        }
    }

    /**
     * @param $parents
     */
    public function delete($parents)
    {
        $this->array_unset_value($this->config, $parents);
    }

    /**
     * @return array
     */
    public function getAll()
    {
        return $this->config;
    }




}
