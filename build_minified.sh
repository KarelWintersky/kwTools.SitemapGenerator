#!/bin/bash
# Generating minimized file (all-in-one)
DIR_SOURCES='./'
DIR_DEST='./dest'

echo 'Generating all-in-one php-file'

cp $DIR_SOURCES/generate.php $DIR_DEST/sitemap_generator.php

echo 'Incapsulating core.sitemapgen.php'
cp $DIR_SOURCES/core.sitemapgen.php __tmp__.php
sed -i "1s/.*/\/\*\*\//" __tmp__.php
sed -i -e "/core\.sitemapgen\.php/{r __tmp__.php"  -e ' d}' $DIR_DEST/sitemap_generator.php

echo 'Incapsulating class.INI_config.php'
cp $DIR_SOURCES/class.INI_config.php __tmp__.php
sed -i "1s/.*/\/\*\*\//" __tmp__.php
sed -i -e "/class\.INI_config\.php/{r __tmp__.php"  -e 'd}' $DIR_DEST/sitemap_generator.php

echo 'Incapsulating class.DBConnectionLite.php'
cp $DIR_SOURCES/class.DBConnectionLite.php __tmp__.php
sed -i "1s/.*/\/\*\*\//" __tmp__.php
sed -i -e "/class\.DBConnectionLite\.php/{r __tmp__.php"  -e 'd}' $DIR_DEST/sitemap_generator.php

echo 'Incapsulating class.sitemap_file_saver.php'
cp $DIR_SOURCES/class.sitemap_file_saver.php __tmp__.php
sed -i "1s/.*/\/\*\*\//" __tmp__.php
sed -i -e "/class\.sitemap_file_saver\.php/{r __tmp__.php"  -e 'd}' $DIR_DEST/sitemap_generator.php

rm -f __tmp__.php

echo 'Ok.'
echo "$DIR_DEST/sitemap_generator.php GENERATED"
