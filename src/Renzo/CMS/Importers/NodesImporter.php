<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file NodeTypesImporter.php
 * @copyright REZO ZERO 2014
 * @author Maxime Constantinian
 */

namespace RZ\Renzo\CMS\Importers;

use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Entities\Node;
use RZ\Renzo\Core\Entities\Translation;
use Doctrine\Common\Collections\ArrayCollection;
use RZ\Renzo\Core\Serializers\NodeJsonSerializer;

use RZ\Renzo\CMS\Importers\ImporterInterface;


/**
 * {@inheritdoc}
 */
class NodesImporter implements ImporterInterface {
    /**
     * Import a Json file (.rzt) containing node and node source.
     *
     * @param string $serializedData
     *
     * @return bool
     */
    public static function importJsonFile($serializedData) {
        $nodes = NodeJsonSerializer::deserialize($serializedData);
        $exist = Kernel::getInstance()->getService('em')
                      ->getRepository('RZ\Renzo\Core\Entities\Node')
                      ->findAll();
        if (empty($exist)) {
            foreach ($nodes as $node) {
                static::browseTree($node);
            }
        }
        return true;
    }

    private static function browseTree($node) {
        $childObj = array();
        $sourceObj = array();
        foreach ($node->getChildren() as $child) {
            $childObj[] = static::browseTree($child);
        }
        $node->getChildren()->clear();
        foreach ($node->getNodeSources() as $nodeSource) {
            $trans = Kernel::getInstance()->getService('em')
                          ->getRepository("RZ\Renzo\Core\Entities\Translation")
                          ->findOneByLocale($nodeSource->getTranslation()->getLocale());

            if (empty($trans)) {
                $trans = new Translation();
                $trans->setLocale($nodeSource->getTranslation()->getLocale());
                $trans->setName(Translation::$availableLocales[$nodeSource->getTranslation()->getLocale()]);
                Kernel::getInstance()->getService('em')->persist($trans);
            }
            $nodeSource->setTranslation($trans);
            foreach ($nodeSource->getUrlAliases() as $alias) {
                Kernel::getInstance()->getService('em')->persist($alias);
            }
            $nodeSource->setNode(null);
            Kernel::getInstance()->getService('em')->persist($nodeSource);
            $sourceObj[] = $nodeSource;
        }

        Kernel::getInstance()->getService('em')->persist($node);
        foreach ($childObj as $child) {
            $child->setParent($node);
        }
        foreach ($sourceObj as $nodeSource) {
            $nodeSource->setNode($node);
        }
        Kernel::getInstance()->getService('em')->flush();
        return $node;
    }
}