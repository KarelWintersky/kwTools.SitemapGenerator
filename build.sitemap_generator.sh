#!/bin/bash
# Generating minimized file (all-in-one)
DIR_SOURCES='./sources'
DIR_DEST='./production'
FILE_SOURCE='generator.php'
FILE_DEST='sitemap_generator.php'

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

echo 'Incapsulating class.DBConnectionLite.php'
cp $DIR_SOURCES/class.DBConnectionLite.php __tmp__.php
sed -i "1s/.*/\/\*\*\//" __tmp__.php
sed -i -e "/class\.DBConnectionLite\.php/{r __tmp__.php"  -e 'd}' $DIR_DEST/$FILE_DEST

echo 'Incapsulating class.SitemapMessages.php'
cp $DIR_SOURCES/class.SitemapMessages.php __tmp__.php
sed -i "1s/.*/\/\*\*\//" __tmp__.php
sed -i -e "/class\.SitemapMessages\.php/{r __tmp__.php"  -e 'd}' $DIR_DEST/$FILE_DEST

echo 'Incapsulating class.SitemapFileSaver.php'
cp $DIR_SOURCES/class.SitemapFileSaver.php __tmp__.php
sed -i "1s/.*/\/\*\*\//" __tmp__.php
sed -i -e "/class\.SitemapFileSaver\.php/{r __tmp__.php"  -e 'd}' $DIR_DEST/$FILE_DEST

rm -f __tmp__.php

echo 'Ok.'
echo "$DIR_DEST/$FILE_DEST GENERATED"

