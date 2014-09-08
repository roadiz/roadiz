<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file Translation.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Renzo\Core\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use RZ\Renzo\Core\AbstractEntities\AbstractDateTimed;
use RZ\Renzo\Core\Handlers\TranslationHandler;
/**
 * Translations describe language locales to be used by Nodes,
 * Tags, UrlAliases and Documents.
 *
 * @Entity(repositoryClass="RZ\Renzo\Core\Entities\TranslationRepository")
 * @Table(name="translations", indexes={
 *     @index(name="available_idx", columns={"available"}),
 *     @index(name="default_translation_idx", columns={"default_translation"})
 * })
 */
class Translation extends AbstractDateTimed
{
    /**
     * Associates locales to pretty languages names.
     *
     * @var array
     */
    public static $availableLocales = array(
        "fr_FR"         => "French", // France
        "en_GB"         => "British english",
        "en_US"         => "American english",
        "it_IT"         => "Italian",
        "es_ES"         => "Spanish",
        "nl_NL"         => "Dutch",
        "de_DE"         => "German",
        "zh_HK"         => "Chinese (Honk Kong)",
        "zh_CN"         => "Chinese (China)",
        "zh_SG"         => "Chinese (Singapore)",
        "zh_TW"         => "Chinese (Taïwan)",
        "ja_JP"         => "Japanese"
    );
    /**
     * Associates locales to *famfamfam* flag files names.
     *
     * @var array
     */
    public static $availableLocalesFlags = array(
        "fr_FR"         => "fr.png", // France
        "en_GB"         => "gb.png",
        "en_US"         => "us.png",
        "it_IT"         => "it.png",
        "es_ES"         => "es.png",
        "nl_NL"         => "nl.png",
        "de_DE"         => "de.png",
        "zh_HK"         => "hk.png",
        "zh_CN"         => "cn.png",
        "zh_SG"         => "sg.png",
        "zh_TW"         => "tw.png",
        "ja_JP"         => "jp.png"
    );

    /**
     * Associates short locales (2 letters)
     * with locales (language_Country).
     *
     * @var array
     */
    public static $availableLocalesShortcut = array(
        'fr' => "fr_FR", // France
        'en' => "en_GB",
        'en' => "en_US",
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
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * @param string $locale
     *
     * @return $this
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;

        return $this;
    }
    /**
     * @return string
     */
    public function getShortLocale()
    {
        $shorts = array_flip(static::$availableLocalesShortcut);

        return $shorts[$this->getLocale()];
    }

    /**
     * @Column(type="string", unique=true)
     */
    private $name;

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName($name)
    {
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
    public function isDefaultTranslation()
    {
        return $this->defaultTranslation;
    }
    /**
     * @param boolean $defaultTranslation
     *
     * @return $this
     */
    public function setDefaultTranslation($defaultTranslation)
    {
        $this->defaultTranslation = (boolean) $defaultTranslation;

        return $this;
    }

    /**
     * @Column(type="boolean")
     */
    private $available = true;

    /**
     * @return boolean
     */
    public function isAvailable()
    {
        return $this->available;
    }

    /**
     * @param boolean $available
     *
     * @return $this
     */
    public function setAvailable($available)
    {
        $this->available = $available;

        return $this;
    }

    /**
     * @todo Move this method to a TranslationViewer
     * @return string
     */
    public function getOneLineSummary()
    {
        return $this->getId()." — ".$this->getName()." — ".$this->getLocale().
            " — Available : ".($this->isAvailable()?'true':'false').PHP_EOL;
    }


    /**
     * Return available locales in an array.
     *
     * @return array
     */
    public static function getAvailableLocales()
    {
        return array_keys(static::$availableLocales);
    }

    /**
     * Return available locales shotcuts in an array.
     *
     * @return array
     */
    public static function getAvailableLocalesShortcuts()
    {
        return array_keys(static::$availableLocalesShortcut);
    }

    /**
     * Return complete locale name from short locale.
     * Ex : en => en_GB or us => en_US
     *
     * @param string $shortcut
     *
     * @return string
     */
    public static function getLocaleFromShortcut($shortcut)
    {
        if (isset(static::$availableLocalesShortcut[$shortcut])) {
            return static::$availableLocalesShortcut[$shortcut];
        } else {
            return "";
        }
    }

    /**
     * @OneToMany(targetEntity="NodesSources", mappedBy="translation", orphanRemoval=true, fetch="EXTRA_LAZY")
     * @var ArrayCollection
     */
    private $nodeSources = null;
    /**
     * @return ArrayCollection
     */
    public function getNodeSources()
    {
        return $this->nodeSources;
    }
    /**
     * @OneToMany(targetEntity="TagTranslation", mappedBy="translation", orphanRemoval=true, fetch="EXTRA_LAZY")
     * @var ArrayCollection
     */
    private $tagTranslations = null;
    /**
     * @return Translation
     */
    public function getTagTranslations()
    {
        return $this->tagTranslations;
    }

    /**
     * Return short locale name from complete locale.
     * Ex : en_GB => en or en_US => us
     *
     * @param string $locale
     *
     * @return string
     */
    public static function getShortcutFromLocale($locale)
    {
        if (in_array($locale, static::$availableLocalesShortcut)) {
            return array_search($locale, static::$availableLocalesShortcut);
        } else {
            return false;
        }
    }
    /**
     * @return TranslationHandler
     */
    public function getHandler()
    {
        return new TranslationHandler($this);
    }

    /**
     * Create a new Translation
     */
    public function __construct()
    {
        $this->nodeSources = new ArrayCollection();
        $this->tagTranslations = new ArrayCollection();
    }
}