<?php 


namespace RZ\Renzo\Core\Utils;


abstract class StringHandler 
{
	/**
	 * Remove diacritics characters and replace them with their basic alpha letter
	 * 
	 * @param  string $string 
	 * @return string
	 */
	public static function removeDiacritics( $string )
	{
		$string = htmlentities($string, ENT_NOQUOTES, 'utf-8');
		$string = preg_replace('#([\'])#', ' ', $string);
		$string = preg_replace('#&([A-za-z])(?:uml|circ|tilde|acute|grave|cedil|ring);#', '\1', $string);
		$string = preg_replace('#&([A-za-z]{2})(?:lig);#', '\1', $string);
		$string = preg_replace('#&[^;]+;#', ' ', $string);

		return $string;
	}

	/**
	 * Transform to lowercase and remplace every non-alpha character with a dash
	 * 
	 * @param  string $string
	 * @return string Slugified string
	 */
	public static function slugify( $string )
	{
		$string = trim(strtolower($string));
		$string = static::removeDiacritics($string);
	 	$string = preg_replace('#([^a-zA-Z0-9]+)#', '-', $string);

	 	return $string;
	}
	/**
	 * Transform to lowercase and remplace every non-alpha character with an underscore
	 * 
	 * @param  string $string
	 * @return string Slugified string
	 */
	public static function cleanForFilename( $string )
	{
		$string = trim(strtolower($string));
		$string = static::removeDiacritics($string);
	 	$string = preg_replace('#([^a-zA-Z0-9\.]+)#', '_', $string);

	 	return $string;
	}

	/**
	 * Transform to lowercase and remplace every non-alpha character with an underscore
	 * 
	 * @param  string $string
	 * @return string Variablized string
	 */
	public static function variablize( $string )
	{
		$string = trim(strtolower($string));
		$string = static::removeDiacritics($string);
	 	$string = preg_replace('#([^a-zA-Z0-9]+)#', '_', $string);

	 	return $string;
	}
}