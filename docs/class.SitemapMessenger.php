<?php


class SitemapMessenger
{
    const MESSAGES = [
        'en_US' =>  [
            'only_cli'  =>  "Sitemap Generator can't be launched in browser.",
            
            // welcome message, %s#1 - текущий год
            'welcome' =>    '
<font color="white">KWCSG</font> is a <strong>Karel Wintersky\'s Configurable Sitemap Generator</strong><br>
It uses .ini files for configuration <br>
See: https://github.com/KarelWintersky/kwTools.SitemapGenerator/blob/master/README.md
or https://github.com/KarelWintersky/kwTools.SitemapGenerator/blob/master/README-EN.md

© Karel Wintersky, 2017-%s, <font color="dgray">https://github.com/KarelWintersky/kwTools.SitemapGenerator</font><hr>',
 
            // сообщение при отсутствующем конфиге
            'missing_config'
                =>  '<font color="red">missing config file</font>
<font color="white">Use: </font> %1$s <font color="yellow">--config /path/to/sitemap-config.ini</font>
or
<font color="white">Use: </font> %1$s --help<hr>
',
            //
            'missing_dbsuffix'
                =>  '<font color="yellow">[WARNING]</font> Key <font color="cyan">___GLOBAL_SETTINGS___/db_section_suffix </font> is <strong>EMPTY</strong> in file <font color="yellow">%1$s</font>
Database connection can\'t be established.
Any sections with <strong>source=\'sql\'</strong> will be skipped.
',
    
            'missing_dbsection'
                =>  '<font color=\'lred\'>[ERROR]</font> : Config section <font color=\'cyan\'>[___GLOBAL_SETTINGS:%1$s___]</font> not found in file <font color=\'yellow\'>%2$s</font>
See <font color=\'green\'>https://github.com/KarelWintersky/kwTools.SitemapGenerator</font> for assistance.
',
            'unknown_db_driver'
                =>  '<font color="red">[ERROR]</font> Unknown database driver: %1$s!',
    
            'generated_sitemap_chunk'
                =>  '+ Generated sitemap URLs from offset %1$s and count %2$s. Consumed time: %3$s sec.',
            
            


        ],
        'ru_RU' =>  [
            
            'cant_use_sql_section_no_connection'    =>  "Нельзя использовать секцию с источником данных SQL - отсутствует подключение к БД"
        
        ],
    ];
    
}