#!/bin/bash
# Generating minimized file (all-in-one)

echo 'Generating all-in-one php-file'

cp generate.php sitemap_generator.php

# sed -i -e "/??/{r FILE"  -e 'd}' sitemap_generator.php

echo 'core.sitemapgen.php'
cp core.sitemapgen.php __tmp__.php
sed -i "1s/.*/\/\*\*\//" __tmp__.php
sed -i -e "/core\.sitemapgen\.php/{r __tmp__.php"  -e ' d}' sitemap_generator.php

echo 'class.INI_config.php'
cp class.INI_config.php __tmp__.php
sed -i "1s/.*/\/\*\*\//" __tmp__.php
sed -i -e "/class\.INI_config\.php/{r __tmp__.php"  -e 'd}' sitemap_generator.php

echo 'class.DBConnectionLite.php'
cp class.DBConnectionLite.php __tmp__.php
sed -i "1s/.*/\/\*\*\//" __tmp__.php
sed -i -e "/class\.DBConnectionLite\.php/{r __tmp__.php"  -e 'd}' sitemap_generator.php

echo 'class.sitemap_file_saver.php'
cp class.sitemap_file_saver.php __tmp__.php
sed -i "1s/.*/\/\*\*\//" __tmp__.php
sed -i -e "/class\.sitemap_file_saver\.php/{r __tmp__.php"  -e 'd}' sitemap_generator.php

rm -f __tmp__.php

echo 'Ok.'
echo 'sitemap_generator.php GENERATED'
