<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file StringHandler.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Renzo\Core\Utils;

/**
 * String handling methods.
 */
class StringHandler
{
    /**
     * Remove diacritics characters and replace them with their basic alpha letter.
     *
     * @param string $string
     *
     * @return string
     */
    public static function removeDiacritics($string)
    {
        $string = htmlentities($string, ENT_NOQUOTES, 'utf-8');
        $string = preg_replace('#([\'])#', ' ', $string);
        $string = preg_replace('#&([A-Za-z])(?:uml|circ|tilde|acute|grave|cedil|ring);#', '\1', $string);
        $string = preg_replace('#&([A-Za-z]{2})(?:lig);#', '\1', $string);
        $string = preg_replace('#&[^;]+;#', ' ', $string);

        return $string;
    }

    /**
     * Transform to lowercase and remplace every non-alpha character with a dash.
     *
     * @param string $string
     *
     * @return string Slugified string
     */
    public static function slugify($string)
    {
        $string = static::removeDiacritics($string);
        $string = trim(strtolower($string));
        $string = preg_replace('#([^a-zA-Z0-9]+)#', '-', $string);

        return $string;
    }
    /**
     * Transform a string for use as a classname.
     *
     * @param string $string
     *
     * @return string Classified string
     */
    public static function classify($string)
    {
        $string = static::removeDiacritics($string);
        $string = trim(preg_replace('#([^a-zA-Z])#', '', ucwords($string)));

        return $string;
    }
    /**
     * Transform to lowercase and remplace every non-alpha character with an underscore.
     *
     * @param string $string
     *
     * @return string Slugified string
     */
    public static function cleanForFilename($string)
    {
        $string = trim(strtolower($string));
        $string = static::removeDiacritics($string);
        $string = preg_replace('#([^a-zA-Z0-9\.]+)#', '_', $string);

        return $string;
    }

    /**
     * Transform to lowercase and remplace every non-alpha character with an underscore.
     *
     * @param string $string
     *
     * @return string Variablized string
     */
    public static function variablize($string)
    {
        $string = static::removeDiacritics($string);
        $string = preg_replace('#([^a-zA-Z0-9]+)#', '_', $string);
        $string = strtolower($string);
        $string = trim($string);

        return $string;
    }
}
