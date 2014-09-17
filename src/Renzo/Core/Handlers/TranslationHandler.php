<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file TranslationHandler.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Renzo\Core\Handlers;

use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Entities\Node;
use RZ\Renzo\Core\Entities\NodeType;
use RZ\Renzo\Core\Entities\NodeTypeField;
use RZ\Renzo\Core\Entities\Translation;

/**
 * Handle operations with translations entities.
 */
class TranslationHandler
{
    private $translation = null;

    /**
     * @return RZ\Renzo\Core\Entities\Translation
     */
    public function getTranslation()
    {
        return $this->translation;
    }

    /**
     * @param RZ\Renzo\Core\Entities\Translation $translation
     *
     * @return $this
     */
    public function setTranslation($translation)
    {
        $this->translation = $translation;

        return $this;
    }

    /**
     * Create a new translation handler with translation to handle.
     *
     * @param Translation $translation
     */
    public function __construct(Translation $translation)
    {
        $this->translation = $translation;
    }

    /**
     * Set current translation as default one.
     *
     * @return $this
     */
    public function makeDefault()
    {
        $defaults = Kernel::getInstance()->em()
            ->getRepository('RZ\Renzo\Core\Entities\Translation')
            ->findBy(array('defaultTranslation'=>true));

        foreach ($defaults as $default) {
            $default->setDefaultTranslation(false);
        }
        $this->getTranslation()->setDefaultTranslation(true);
        Kernel::getInstance()->em()->flush();

        return $this;
    }
}
