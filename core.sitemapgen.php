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
// тут же мы можем реализовать CSV loader и прочие радости жизни
// + построение чанка, запроса с чанками (лимит/оффсет) итд
