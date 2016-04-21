<?php

namespace pisc\LaravelDBDebug;

function mb_substr_replace($string, $replacement, $start, $length = null, $encoding = null)
{
	$string = (array)$string;
	$encoding = is_null($encoding) ? mb_internal_encoding() : $encoding;
	$length = is_null($length) ? mb_strlen($string) - $start : $length;

	$string = array_map(function($str) use ($replacement, $start, $length, $encoding){

		$begin = mb_substr($str, 0, $start, $encoding);
		$end = mb_substr($str, ($start + $length), mb_strlen($str), $encoding);

		return $begin . $replacement . $end;

	}, $string);

	return ( count($string) === 1 ) ? $string[0] : $string;
}

function str_replace_limit($search, $replace, $string, $limit)
{
	$i = 0;
	$searchLength = mb_strlen($search);

	while(($pos = mb_strpos($string, $search)) !== false && $i < $limit)
	{
		$string = mb_substr_replace($string, $replace, $pos, $searchLength);
		$i += 1;
	}

	return $string;
}