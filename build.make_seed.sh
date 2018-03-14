#!/bin/bash
# Generating make_seed file (all-in-one)

DIR_SOURCES='./sources'
DIR_DEST='./tests'
FILE_SOURCE='make_db_seed.php'
FILE_DEST='make_seed.php'

echo 'Generating all-in-one php-file'

echo '#!/usr/bin/php' > $DIR_DEST/$FILE_DEST
cat $DIR_SOURCES/$FILE_SOURCE >> $DIR_DEST/$FILE_DEST

echo 'Incapsulating class.CLIConsole.php'
cp $DIR_SOURCES/class.CLIConsole.php __tmp__.php
sed -i "1s/.*/\/\*\*\//" __tmp__.php
sed -i -e "/class\.CLIConsole\.php/{r __tmp__.php"  -e ' d}' $DIR_DEST/$FILE_DEST

echo 'Incapsulating class.INI_Config.php'
cp $DIR_SOURCES/class.INI_Config.php __tmp__.php
sed -i "1s/.*/\/\*\*\//" __tmp__.php
sed -i -e "/class\.INI_Config\.php/{r __tmp__.php"  -e 'd}' $DIR_DEST/$FILE_DEST

echo 'Incapsulating class.DBConnection.php'
cp $DIR_SOURCES/class.DBConnection.php __tmp__.php
sed -i "1s/.*/\/\*\*\//" __tmp__.php
sed -i -e "/class\.DBConnection\.php/{r __tmp__.php"  -e 'd}' $DIR_DEST/$FILE_DEST

echo 'Incapsulating class.SitemapFileSaver.php'
cp $DIR_SOURCES/class.SitemapFileSaver.php __tmp__.php
sed -i "1s/.*/\/\*\*\//" __tmp__.php
sed -i -e "/class\.SitemapFileSaver\.php/{r __tmp__.php"  -e 'd}' $DIR_DEST/$FILE_DEST

rm -f __tmp__.php

echo 'Ok.'
echo "$DIR_DEST/$FILE_DEST GENERATED"

