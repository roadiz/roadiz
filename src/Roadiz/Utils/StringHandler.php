<?php
/**
 * Copyright © 2014, Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * Except as contained in this notice, the name of the ROADIZ shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from Ambroise Maupate and Julien Blanchet.
 *
 * @file StringHandler.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Utils;

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
        $string = trim($string, "-");

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
        $string = trim($string, "_");

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

    /**
     * Transform to camelcase.
     *
     * @param string $string
     *
     * @return string
     */
    public static function camelCase($string)
    {
        $string = static::removeDiacritics($string);
        $string = preg_replace('#([-_=\.,;:]+)#', ' ', $string);
        $string = preg_replace('#([^a-zA-Z0-9]+)#', '', ucwords($string));
        $string = trim($string);
        $string[0] = strtolower($string[0]);

        return $string;
    }


    /**
     * Encode a string using website security secret.
     *
     * @param string $value String to encode
     * @param string $secret Secret salt
     *
     * @return string
     * @throws \InvalidArgumentException
     */
    public static function encodeWithSecret($value, $secret)
    {
        $secret = trim($secret);

        if (!empty($secret)) {
            $secret = crypt($secret, $secret);
            return base64_encode($secret . base64_encode(strip_tags($value)));
        } else {
            throw new \InvalidArgumentException("You cannot encode with an empty salt. Did you enter a secret security phrase in your conf/config.json file?", 1);
        }
    }

    /**
     * Decode a string using website security secret.
     *
     * @param string $value Salted base64 string
     * @param string $secret Secret salt
     *
     * @return string
     * @throws \InvalidArgumentException
     */
    public static function decodeWithSecret($value, $secret)
    {
        $secret = trim($secret);

        if (!empty($secret)) {
            $secret = crypt($secret, $secret);
            $salted = base64_decode($value);

            $nonSalted = str_replace($secret, "", $salted);

            return base64_decode($nonSalted);
        } else {
            throw new \InvalidArgumentException("You cannot encode with an empty salt. Did you enter a secret security phrase in your conf/config.json file?", 1);
        }
    }

    /**
     * @param string $haystack
     * @param string $needle
     * @return bool
     */
    public static function endsWith($haystack, $needle)
    {
        if (strlen($needle) > strlen($haystack)) {
            return false;
        }
        // search forward starting from end minus needle length characters
        return $needle === "" || strpos($haystack, $needle, strlen($haystack) - strlen($needle)) !== false;
    }

    /**
     * @param string $search
     * @param string $replace
     * @param string $subject
     * @return string
     */
    public static function replaceLast($search, $replace, $subject)
    {
        $pos = strrpos($subject, $search);

        if ($pos !== false) {
            $subject = substr_replace($subject, $replace, $pos, strlen($search));
        }

        return $subject;
    }
}
