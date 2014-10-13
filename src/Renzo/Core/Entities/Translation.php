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
 * @Entity(repositoryClass="RZ\Renzo\Core\Repositories\TranslationRepository")
 * @Table(name="translations", indexes={
 *     @index(name="available_translation_idx", columns={"available"}),
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
        "fr"         => "French", // France
        "en"         => "English",
        "it"         => "Italian",
        "es"         => "Spanish",
        "nl"         => "Dutch",
        "de"         => "German",
        "zh"         => "Chinese (China)",
        "ja"         => "Japanese"
    );
    /**
     * Associates locales to *famfamfam* flag files names.
     *
     * @var array
     */
    public static $availableLocalesFlags = array(
        "fr"         => "fr.png", // France
        "en"         => "us.png",
        "it"         => "it.png",
        "es"         => "es.png",
        "nl"         => "nl.png",
        "de"         => "de.png",
        "zh"         => "cn.png",
        "ja"         => "jp.png"
    );

    /**
     * Associates short locales (2 letters)
     * with locales (language_Country).
     *
     * @var array
     */
    public static $availableLocalesShortcut = array(
        'fr' => "fr_FR", // France
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
        return $this->locale;
    }

    /**
     * @return string
     */
    public function getLongLocale()
    {
        return static::$availableLocalesShortcut[$this->getLocale()];
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
