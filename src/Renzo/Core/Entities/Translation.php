<?php 


namespace RZ\Renzo\Core\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use RZ\Renzo\Core\AbstractEntities\DateTimed;

/**
 * @Entity
 * @Table(name="translations", indexes={
 *     @index(name="available_idx", columns={"available"}), 
 *     @index(name="default_translation_idx", columns={"default_translation"})
 * })
 */
class Translation extends DateTimed {


	private static $availableLocales = array(
		"fr_FR"			=> "fr.png", // France
		"fr_RE"			=> "re.png", // Réunion
		"en_GB"			=> "gb.png",
		"en_US"			=> "us.png",
		"it_IT"			=> "it.png",
		"es_ES"			=> "es.png",
		"nl_NL"			=> "nl.png",
		"de_DE"			=> "de.png",
		"zh_HK"			=> "hk.png",
		"zh_CN"			=> "cn.png",
		"zh_SG"			=> "sg.png",
		"zh_TW"			=> "tw.png",
		"ja_JP"			=> "jp.png"
	);
	private static $availableLocalesShortcut = array(
		'fr' => "fr_FR", // France
		're' => "fr_RE", // Réunion
		'en' => "en_GB",
		'us' => "en_US",
		'it' => "it_IT",
		'es' => "es_ES",
		'nl' => "nl_NL",
		'de' => "de_DE",
		'zh' => "zh_CN",
		'sg' => "zh_SG",
		'hk' => "zh_HK",
		'tw' => "zh_TW",
		'jp' => "ja_JP"
	);


	/**
	 * Language locale
	 * 
	 * fr_FR or en_US for example
	 * 
	 * @Column(type="string", unique=true, length=10)
	 */
	private $locale;

	/**
	 * @return [type] [description]
	 */
	public function getLocale() {
	    return $this->locale;
	}
	
	/**
	 * @param [type] $newlocale [description]
	 */
	public function setLocale($locale) {
	    $this->locale = $locale;
	
	    return $this;
	}

	/**
	 * @Column(type="string", unique=true)
	 */
	private $name;

	/**
	 * @return [type] [description]
	 */
	public function getName() {
	    return $this->name;
	}
	
	/**
	 * @param [type] $newname [description]
	 */
	public function setName($name) {
	    $this->name = $name;
	
	    return $this;
	}

	/**
	 * @Column(name="default_translation", type="boolean")
	 */
	private $defaultTranslation = false;
	/**
	 * @return boolean
	 */
	public function isDefaultTranslation() {
	    return $this->defaultTranslation;
	}
	/**
	 * @param boolean $newdefaultTranslation
	 */
	public function setDefaultTranslation($defaultTranslation) {
	    $this->defaultTranslation = (boolean)$defaultTranslation;
	    return $this;
	}

	/**
	 * @Column(type="boolean")
	 */
	private $available = true;

	/**
	 * @return [type] [description]
	 */
	public function isAvailable() {
	    return $this->available;
	}
	
	/**
	 * @param [type] $newavailable [description]
	 */
	public function setAvailable($available) {
	    $this->available = $available;
	
	    return $this;
	}


	public function getOneLineSummary()
	{
		return $this->getId()." — ".$this->getName()." — ".$this->getLocale().
			" — Available : ".($this->isAvailable()?'true':'false').PHP_EOL;
	}


	/**
	 * Return available locales in an array
	 * @return array
	 */
	public static function getAvailableLocales()
	{
		return array_keys(static::$availableLocales);
	}

	/**
	 * Return available locales shotcuts in an array
	 * @return array
	 */
	public static function getAvailableLocalesShortcuts()
	{
		return array_keys(static::$availableLocalesShortcut);
	}

	/**
	 * Return complete locale name from short locale.    
	 * Ex : en => en_GB or us => en_US
	 * @param  string $shortcut 
	 * @return string           
	 */
	public static function getLocaleFromShortcut( $shortcut )
	{
		if (isset(static::$availableLocalesShortcut[$shortcut])) {
			return static::$availableLocalesShortcut[$shortcut];
		}
		else {
			return "";
		}
	}

	/**
	 * Return short locale name from complete locale.    
	 * Ex : en_GB => en or en_US => us
	 * @param  string $locale
	 * @return string        
	 */
	public static function getShortcutFromLocale( $locale )
	{
		if (in_array($locale, static::$availableLocalesShortcut)) {
			return array_search($locale, static::$availableLocalesShortcut);
		}
		else {
			return false;
		}
	}
}