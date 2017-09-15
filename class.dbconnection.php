<?php
/**
 *  version 2017-09-15 LITE
 *  PDO wrapper.
 *  © Karel Wintersky
 */

/**
 * Class DBConnection (lite version)
 *
 * Создает экземпляр класса DBConnection, дающего доступ к методам, упрощающим работу с PDO
 * ВАЖНО: Хотя этот класс и наследуется от PDO, он НЕ ЯВЛЯЕТСЯ инстансом соединения с базой данных.
 * ВАЖНО: Для получения инстанса соединения с базой данных надо вызывать ->getconnection()
 * ВАЖНО: Следует помнить об этом при передаче соединения в классы, принимающие непосредственно линк на соединение.
 */
class DBConnection extends \PDO
{
    public $connect_error = 0;
    public $database_settings;
    public $table_prefix = '';

    public $pdo_connection = NULL;

    public $is_connected = FALSE;

    /**
     *
     * @param string $key_connection
     */
    public function __construct($key_connection)
    {
        if ($key_connection === '') {
            echo("Connection to DB not defined \r\n");
            debug_print_backtrace();
            die("\r\n<br>");
        }
        $key_connection = trim($key_connection);

        $database_settings_section_name = StaticConfig::key('host/server') . ':' . StaticConfig::key( "connection/{$key_connection}" );
        $database_settings = StaticConfig::key( $database_settings_section_name );

        $this->database_settings = $database_settings;

        try {
            $dbhost = $database_settings['hostname'];
            $dbname = $database_settings['database'];
            $dbuser = $database_settings['username'];
            $dbpass = $database_settings['password'];
            $dbport = $database_settings['port'];

            $dsl = "mysql:host=$dbhost;port=$dbport;dbname=$dbname";

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

        $this->table_prefix = StaticConfig::key(
            $database_settings_section_name . '/table_prefix'
        );

        StaticConfig::setPDO( $dbh );

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

    /**
     * @param DBConnection $connection
     * @param $table
     * @param $data
     * @param bool|false $print_flag
     * @return int|string
     */
    public static function DB_insert(\DBConnection $connection, $table, $data, $print_flag = false)
    {
        $lid = 0;
        try {
            $sql = self::build__PDO_Insert($table, $data, $print_flag);
            $sth = $connection->getconnection()->prepare($sql);
            $sth->execute($data);
            $lid = $connection->getconnection()->lastInsertId();
        } catch (\PDOException $e) {
            echo $e->getMessage();
            var_dump($data);
        }
        unset($sth);
        return $lid;
    }

    /**
     * @param $tablename
     * @param $dataset
     * @param bool|FALSE $print_flag
     * @return string
     */
    public static function build__PDO_Insert($tablename, $dataset, $print_flag = FALSE)
    {
        $ret = '';
        $r = array();

        if (empty($dataset)) {
            $ret = "INSERT INTO {$tablename} () VALUES (); ";
        } else {
            $ret = "INSERT INTO `{$tablename}` SET ";

            foreach ($dataset as $index=>$value) {
                $r[] = "\r\n {$index} = :{$index} ";
            }

            $ret
                .= (count($r) == 1)
                ? $r[0]
                : implode(',', $r);
            $ret .= " ; ";
        }

        if ($print_flag) dump($ret);
        return $ret;
    }

    /**
     * @param DBConnection $connection
     * @param $request_string
     * @return int|string
     */
    public static function DB_insert_prepared(\DBConnection $connection, $request_string)
    {
        $lid = 0;
        try {
            $connection->getconnection()->query($request_string);
            $lid = $connection->getconnection()->lastInsertId();
        } catch (\PDOException $e) {
            echo $e->getMessage();
            dump($request_string);
        }
        unset($sth);
        return $lid;
    }

    /**
     * @param $tablename
     * @param $dataset
     * @param $where_condition
     * @param bool|FALSE $print_flag
     * @return string
     */
    public static function build__PDO_Update($tablename, $dataset, $where_condition, $print_flag = FALSE)
    {
        $ret = '';

        if (empty($dataset)) {
            return FALSE;
        } else {
            $ret = "UPDATE `{$tablename}` SET";
            $r = array();

            foreach ($dataset as $index=>$value) {
                $r[] = "\r\n{$index} = :{$index}";
            }

            $ret
                .= (count($r) == 1)
                ? $r[0]
                : implode(',', $r);

            $ret .= " \r\n" . $where_condition . " ;";
        }

        if ($print_flag) dump($ret);

        return $ret;
    }


    /**
     * @param DBConnection $connection
     * @param $table
     * @param $data
     * @param $condition
     * @param bool|false $print_flag
     * @return bool
     */
    public static function DB_update(\DBConnection $connection, $table, $data, $condition, $print_flag = false)
    {
        $sql = self::build__PDO_Update($table, $data, $condition, $print_flag);

        if ($sql === '')
            return FALSE;

        try {
            $sth = $connection->getconnection()->prepare($sql);
            $sth->execute( $data );
        } catch (\PDOException $e) {
            echo $e->getMessage();
            dump($data);
        }
        unset($sth);
        return TRUE;
    }


} // class

if (!function_exists('dump')) {
    /**
     * @param $data
     * @param bool|FALSE $is_cli
     */
    function dump($data, $is_cli = FALSE)
    {
        if (!$is_cli)
            echo '<div style="background-color: aquamarine"><pre style="font-family: monospace;">', PHP_EOL;
        var_dump($data);
        if (!$is_cli)
            echo '</pre></div>', PHP_EOL;
        if ($is_cli)
            echo "\r", PHP_EOL;
    }

}
