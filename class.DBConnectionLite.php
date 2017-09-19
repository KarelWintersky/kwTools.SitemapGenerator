<?php

/**
 * Class DBConnectionLite
 * Используется вариативный конфиг (либо инстанс INI_Config и ключ подключения, либо ключ подключения для статика)
 * based on DBConnection ver 1.8
 */
class DBConnectionLite extends \PDO
{
    const USED_CONFIG_STATIC  = 0;
    const USED_CONFIG_DYNAMIC = 1;

    private $database_settings = array();
    private $pdo_connection;
    private $table_prefix = '';
    private $is_connected = FALSE;

    /**
     * @param $key_connection - если передана пустая строка - используем секцию, равную имени host/server (что может быть проще в некоторых случаях)
     * @param INI_Config|NULL $config_instance
     */
    public function __construct($key_connection, \INI_Config $config_instance = NULL)
    {
        $config_type = -1;
        $database_settings = array();
        $key_connection = trim($key_connection);

        if (is_null($config_instance)) {
            // используется StaticConfig

            $section_name = ($key_connection === '') ? '' : ':' . StaticConfig::key( "connection/{$key_connection}" );
            $database_settings_section_name = StaticConfig::key('host/server') . $section_name;
            $database_settings = StaticConfig::key( $database_settings_section_name );
            $this->table_prefix = StaticConfig::key(
                $database_settings_section_name . '/table_prefix'
            );

            // in single line
            // $this->database_settings = StaticConfig::key( StaticConfig::key('host/server') . (($key_connection === '') ? '' : ':' . StaticConfig::key( "connection/{$key_connection}" )) );

        } elseif (get_class($config_instance) === 'INI_Config') {
            // передан инстанс класса INI_Config

            $section_name = (($key_connection === '') ? '' : ':' . $config_instance->get( "connection/{$key_connection}" ));
            $database_settings_section_name = $config_instance->get('host/server') . $section_name;
            $database_settings = $config_instance->get( $database_settings_section_name );
            $this->table_prefix = $config_instance->get(
                $database_settings_section_name . '/table_prefix'
            );

            // в одну строчку
            // $this->database_settings = $config_instance->get( $config_instance->get('host/server') . (($key_connection === '') ? '' : ':' . $config_instance->get( "connection/{$key_connection}" )) );
        } else {
            die('Unknown config: ' . get_class($config_instance));
        }

        $this->database_settings = $database_settings;

        $dbhost = $database_settings['hostname'];
        $dbname = $database_settings['database'];
        $dbuser = $database_settings['username'];
        $dbpass = $database_settings['password'];
        $dbport = $database_settings['port'];

        $dsl = "mysql:host=$dbhost;port=$dbport;dbname=$dbname";

        try {
            $dbh = new \PDO($dsl, $dbuser, $dbpass);

            $dbh->exec("SET NAMES utf8 COLLATE utf8_unicode_ci");
            $dbh->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $dbh->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);

            $this->pdo_connection = $dbh;
        } catch (\PDOException $e) {
            echo "\r\n PDO CONNECTION ERROR: " . $e->getMessage() . "\r\n";

            $this->connect_error = "Database connection error!: " . $e->getMessage() . "<br/>";
            $this->pdo_connection = null;
            return false;
        }

        if ($config_type === self::USED_CONFIG_STATIC) {
            StaticConfig::setPDO( $dbh );
        }

        $this->is_connected = true;
        return true;
    }

    /**
     * @return null|PDO
     */
    public function getconnection()
    {
        return $this->pdo_connection;
    }




}
