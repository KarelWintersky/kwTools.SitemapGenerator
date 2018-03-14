<?php

/**
 * User: Arris
 *
 * Class DBConnection
 *
 * Date: 15.03.2018, time: 0:31
 */
class DBConnection extends \PDO
{
    private $database_settings = array();
    private $pdo_connection;
    private $table_prefix = '';
    public  $is_connected = FALSE;
    public  $error_message = '';

    public function __construct($db_settings)
    {
        $database_settings = $db_settings;

        $this->table_prefix = $db_settings['table_prefix'] ?? '';
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
            $this->error_message = "Database connection error!: " . $e->getMessage() . "<br/>";
            $this->pdo_connection = null;
            return false;
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

/* end class.DBConnection.php */