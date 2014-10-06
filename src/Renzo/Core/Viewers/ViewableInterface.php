<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file ViewableInterface.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */

namespace RZ\Renzo\Core\Viewers;

/**
 * ViewableInterface.
 */
interface ViewableInterface
{
    /**
     * Return current viewable twig engine instance.
     *
     * @return \Twig_Environment
     */
    public function getTwig();

    /**
     * Create a translator instance and load theme messages.
     *
     * {{themeDir}}/Resources/translations/messages.{{lang}}.xlf
     *
     * @todo [Cache] Need to write XLF catalog to PHP using \Symfony\Component\Translation\Writer\TranslationWriter
     *
     * @return $this
     */
    public function initializeTranslator();

    /**
     * @return Symfony\Component\Translation\Translator
     */
    public function getTranslator();
}
