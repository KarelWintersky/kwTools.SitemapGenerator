<?php

class SiteMapMessages
{
    const MESSAGES = [
        'welcome_message' => '
<font color="white">%s</font> is a <strong>Karel Wintersky\'s Configurable Sitemap Generator</strong> with .ini-files as configs
© Karel Wintersky, 2019, <font color="dgray">https://github.com/KarelWintersky/kwTools.SitemapGenerator</font> ',

        'hint_message'  => '
<font color="red">missing config file</font><br>
<font color="white">Use: </font> %1$s <font color="yellow">/path/to/sitemap-config.ini</font>
or
<font color="white">Use: </font> %1$s --help
',

        'MSG_DBSUFFIX_EMPTY'    =>  '
<font color=\'yellow\'>[WARNING]</font> Key <font color=\'cyan\'>___GLOBAL_SETTINGS___/db_section_suffix </font> is <strong>EMPTY</strong> in file <font color=\'yellow\'>%1$s</font><br>
Database connection can\'t be established.<br>
Any sections with <strong>source=\'sql\'</strong> will be skipped.<br>
<br>
        ',

        'MSG_DBSECTION_NOTFOUND' => '
<font color=\'lred\'>[ERROR]</font> : Config section <font color=\'cyan\'>[___GLOBAL_SETTINGS:%1$s___]</font> not found in file <font color=\'yellow\'>%2$s</font><br>
See <font color="green">https://github.com/KarelWintersky/kwTools.SitemapGenerator</font> for assistance. <br>
        
        ',

    ];

    public static function say($message_id = "", ...$args)
    {
        $string = array_key_exists($message_id, self::MESSAGES) ? self::MESSAGES[$message_id] : $message_id;

        $string =
            (func_num_args() > 1)
            ? vsprintf($string, $args)
            : $string;

        CLIConsole::echo_status( $string );
    }

}
/* end class.SitemapMessages.php */
