#!/bin/bash
# Generating minimized file (all-in-one)
DIR_SOURCES='./'
DIR_DEST='./dest'
FILE_DEST='sitemap_generator.php'

echo 'Generating all-in-one php-file'

echo '#!/usr/bin/php' > $DIR_DEST/$FILE_DEST
cat $DIR_SOURCES/generate.php >> $DIR_DEST/$FILE_DEST

echo 'Incapsulating core.sitemapgen.php'
cp $DIR_SOURCES/core.sitemapgen.php __tmp__.php
sed -i "1s/.*/\/\*\*\//" __tmp__.php
sed -i -e "/core\.sitemapgen\.php/{r __tmp__.php"  -e ' d}' $DIR_DEST/$FILE_DEST

echo 'Incapsulating class.INI_config.php'
cp $DIR_SOURCES/class.INI_config.php __tmp__.php
sed -i "1s/.*/\/\*\*\//" __tmp__.php
sed -i -e "/class\.INI_config\.php/{r __tmp__.php"  -e 'd}' $DIR_DEST/$FILE_DEST

echo 'Incapsulating class.DBConnectionLite.php'
cp $DIR_SOURCES/class.DBConnectionLite.php __tmp__.php
sed -i "1s/.*/\/\*\*\//" __tmp__.php
sed -i -e "/class\.DBConnectionLite\.php/{r __tmp__.php"  -e 'd}' $DIR_DEST/$FILE_DEST

echo 'Incapsulating class.SitemapFileSaver.php'
cp $DIR_SOURCES/class.SitemapFileSaver.php __tmp__.php
sed -i "1s/.*/\/\*\*\//" __tmp__.php
sed -i -e "/class\.SitemapFileSaver\.php/{r __tmp__.php"  -e 'd}' $DIR_DEST/$FILE_DEST

rm -f __tmp__.php

echo 'Ok.'
echo "$DIR_DEST/sitemap_generator.php GENERATED"

