<?php

// default charset and collate is "SET NAMES utf8 COLLATE utf8_unicode_ci"
$this->database_settings['charset']
    = array_key_exists('charset', $this->database_settings)
    ? $this->database_settings['charset']
    : 'utf8';

$this->database_settings['charset_collate']
    = array_key_exists('charset_collate', $this->database_settings)
    ? $this->database_settings['charset_collate']
    : 'utf8_unicode_ci';

if ($this->database_settings['charset']) {
    $dbh->exec("SET NAMES {$this->database_settings['charset']}");
}

if (isset($this->database_settings['charset_collate'])) {
    $dbh->exec("SET COLLATE {$this->database_settings['charset_collate']}");
}