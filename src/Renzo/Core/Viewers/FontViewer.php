<?php

/*
 * Copyright REZO ZERO 2014
 *
 * Description
 *
 * @file FontViewer.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */

namespace RZ\Renzo\Core\Viewers;

use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Entities\Font;
use RZ\Renzo\Core\Bags\SettingsBag;

use Symfony\Component\Form\Extension\Csrf\CsrfProvider\SessionCsrfProvider;
use Symfony\Bridge\Twig\Extension\RoutingExtension;
use Symfony\Bridge\Twig\Form\TwigRenderer;
use Symfony\Bridge\Twig\Form\TwigRendererEngine;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\Loader\XliffFileLoader;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

/**
 * FontViewer
 */
class FontViewer implements ViewableInterface
{
    protected $font = null;
    protected $twig = null;

    /**
     * @param RZ\Renzo\Core\Entities\Font $font
     */
    public function __construct(Font $font)
    {
        $this->initializeTwig();
        $this->font = $font;
    }

    /**
     * @return Symfony\Component\Translation\Translator
     */
    public function getTranslator()
    {
        return $this->translator;
    }

    /**
     * Get twig cache folder for current Viewer
     *
     * @return string
     */
    public function getCacheDirectory()
    {
        return RENZO_ROOT.'/cache/Core/FontViewer/twig_cache';
    }

    /**
     * @{inheritdoc}
     */
    public function initializeTranslator()
    {

    }

    /**
     * Create a Twig Environment instance
     *
     * @return  \Twig_Environment
     */
    public function initializeTwig()
    {
        $loader = new \Twig_Loader_Filesystem(array(
            RENZO_ROOT . '/src/Renzo/Core/Resources/views',
        ));
        $this->twig = new \Twig_Environment($loader, array(
            'cache' => $this->getCacheDirectory(),
            'debug' => Kernel::getInstance()->isDebug()
        ));

        // RoutingExtension
        $this->twig->addExtension(
            new RoutingExtension(Kernel::getService('urlGenerator'))
        );

        return $this;
    }

    /**
     * @return \Twig_Environment
     */
    public function getTwig()
    {
        return $this->twig;
    }

    /**
     * Get CSS font-face properties for current font.
     *
     * @param Symfony\Component\Form\Extension\Csrf\CsrfProvider\SessionCsrfProvider $csrfProvider
     *
     * @return string CSS output
     */
    public function getCSSFontFace(SessionCsrfProvider $csrfProvider)
    {
        $assignation = array(
            'font' => $this->font,
            'site' => SettingsBag::get('site_name'),
            'fontFolder' => '/'.Font::getFilesFolderName(),
            'csrfProvider' => $csrfProvider
        );

        return $this->getTwig()->render('fonts/fontfamily.css.twig', $assignation);
    }
}
