<?php 


namespace RZ\Renzo\Core\Utils;


abstract class StringHandler 
{
	
	public static function removeDiacritics( $string )
	{
		$string = htmlentities($string, ENT_NOQUOTES, 'utf-8');
		$string = preg_replace('#([\'])#', ' ', $string);
		$string = preg_replace('#&([A-za-z])(?:uml|circ|tilde|acute|grave|cedil|ring);#', '\1', $string);
		$string = preg_replace('#&([A-za-z]{2})(?:lig);#', '\1', $string);
		$string = preg_replace('#&[^;]+;#', ' ', $string);

		return $string;
	}
}