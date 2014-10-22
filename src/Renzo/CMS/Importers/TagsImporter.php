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
use RZ\Renzo\Core\Entities\Tag;
use RZ\Renzo\Core\Entities\Translation;
use Doctrine\Common\Collections\ArrayCollection;
use RZ\Renzo\Core\Serializers\TagJsonSerializer;

use RZ\Renzo\CMS\Importers\ImporterInterface;


/**
 * {@inheritdoc}
 */
class TagsImporter implements ImporterInterface {
    /**
     * Import a Json file (.rzt) containing tag and tag translation.
     *
     * @param string $serializedData
     *
     * @return bool
     */
    public static function importJsonFile($serializedData) {
        $tags = TagJsonSerializer::deserialize($serializedData);
        $exist = Kernel::getInstance()->getService('em')
                      ->getRepository('RZ\Renzo\Core\Entities\Tag')
                      ->findAll();
        if (empty($exist)) {
            foreach ($tags as $tag) {
                static::browseTree($tag);
            }
        }
        return true;
    }

    private static function browseTree($tag) {
        $childObj = array();
        $sourceObj = array();
        foreach ($tag->getChildren() as $child) {
            $childObj[] = static::browseTree($child);
        }
        $tag->getChildren()->clear();
        foreach ($tag->getTranslatedTags() as $tagTranslation) {
            $trans = Kernel::getInstance()->getService('em')
                          ->getRepository("RZ\Renzo\Core\Entities\Translation")
                          ->findOneByLocale($tagTranslation->getTranslation()->getLocale());

            if (empty($trans)) {
                $trans = new Translation();
                $trans->setLocale($tagTranslation->getTranslation()->getLocale());
                $trans->setName(Translation::$availableLocales[$tagTranslation->getTranslation()->getLocale()]);
                Kernel::getInstance()->getService('em')->persist($trans);
            }
            $tagTranslation->setTranslation($trans);
            $tagTranslation->setTag(null);
            Kernel::getInstance()->getService('em')->persist($tagTranslation);
            $sourceObj[] = $tagTranslation;
        }
        Kernel::getInstance()->getService('em')->persist($tag);
        foreach ($childObj as $child) {
            $child->setParent($tag);
        }
        foreach ($sourceObj as $tagTranslation) {
            $tagTranslation->setTag($tag);
        }
        Kernel::getInstance()->getService('em')->flush();
        return $tag;
    }
}