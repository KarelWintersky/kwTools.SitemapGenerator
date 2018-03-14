<?php

/**
 * @param $array
 * @param $key
 * @param $default_value
 * @return mixed
 */
function at($array, $key, $default_value)
{
	if (array_key_exists($key, $array)) {
		return $array[$key];
	} else {
		return $default_value;
	}
}

/**
 * Печатает в консоли цветное сообщение.
 * Допустимые форматтеры:
 * <font color=""> задает цвет из списка: black, dark gray, blue, light blue, green, lightgreen, cyan, light cyan, red, light red, purple, light purple, brown, yellow, light gray, gray
 * <hr> - горизонтальная черта (80
 * @param string $message
 * @param bool|TRUE $breakline
 */
function echo_status_cli($message = "", $breakline = TRUE)
{
	static $fgcolors = array(
		'black' => '0;30',
		'dark gray' => '1;30',
		'dgray' => '1;30',
		'blue' => '0;34',
		'light blue' => '1;34',
		'lblue' => '1;34',
		'green' => '0;32',
		'light green' => '1;32',
		'lgreen' => '1;32',
		'cyan' => '0;36',
		'light cyan' => '1;36',
		'lcyan' => '1;36',
		'red' => '0;31',
		'light red' => '1;31',
		'lred' => '1;31',
		'purple' => '0;35',
		'light purple' => '1;35',
		'lpurple' => '1;35',
		'brown' => '0;33',
		'yellow' => '1;33',
		'light gray' => '0;37',
		'lgray' => '0;37',
		'white' => '1;37');
	static $bgcolors = array(
		'black' => '40',
		'red' => '41',
		'green' => '42',
		'yellow' => '43',
		'blue' => '44',
		'magenta' => '45',
		'cyan' => '46',
		'light gray' => '47');

	$message
		= (($message == '<hr>') || ($message == '<hr />') || ($message == '<hr/>'))
		? str_repeat('-', 80) : $message;

	$pattern = '#(?<Full>\<font[\s]+color=[\\\'\"](?<Color>[\D]+)[\\\'\"]\>(?<Content>.*)\<\/font\>)#U';
	$message = preg_replace_callback($pattern, function($matches) use ($fgcolors){
		$color = isset( $fgcolors[ $matches['Color'] ]) ? $fgcolors[ $matches['Color'] ] : $fgcolors[ 'white' ];
		return "\033[{$color}m{$matches['Content']}\033[0m";
	}, $message);

	// replace <strong> by <font color=
	$pattern_strong = '#(?<Full>\<strong\>(?<Content>.*)\<\/strong\>)#U';
	$message = strip_tags(preg_replace_callback($pattern_strong, function($matches) use ($fgcolors){
		$color = $fgcolors['white'];
		return "\033[{$color}m{$matches['Content']}\033[0m";
	}, $message) );


	if ($breakline === TRUE) $message .= PHP_EOL;
	echo $message;
}

/**
 * Wrapper around echo/echo_status_cli
 * Выводит сообщение на экран. Если мы вызваны из командной строки - заменяет теги на управляющие последовательности.
 * @param $message
 * @param bool|TRUE $breakline
 */
function echo_status($message = "", $breakline = TRUE)
{
	if (php_sapi_name() === "cli") {
		echo_status_cli($message, $breakline);
	} else {
		if ($breakline === TRUE) $message .= PHP_EOL . "<br/>\r\n";
		echo $message;
	}
}

/* end core.sitemap.gen */