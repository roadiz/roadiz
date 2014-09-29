<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file NodesSourcesRepositoryTest.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
use RZ\Renzo\Core\Entities\NodesSources;
use RZ\Renzo\Core\Entities\Translation;
use RZ\Renzo\Core\Kernel;
/**
 * NodesSourcesRepositoryTest.
 */
class NodesSourcesRepositoryTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider findBySearchQueryProvider
     */
    public function testFindBySearchQuery($query, $expectedClass)
    {
        $nSources = Kernel::getService('em')
                                ->getRepository('RZ\Renzo\Core\Entities\NodesSources')
                                ->findBySearchQuery($query);

        if (null !== $nSources) {
            foreach ($nSources as $key => $source) {
                //echo PHP_EOL.$source->getTitle();
                $this->assertEquals(get_class($source), $expectedClass);
            }
        }
    }
    /**
     * @return array
     */
    public static function findBySearchQueryProvider()
    {
        return array(
            array('text:Propos', 'GeneratedNodeSources\NSPage'),
            array('text:Lorem markdownum', 'GeneratedNodeSources\NSPage'),
            array('text:septemflua et diversa veniat', 'GeneratedNodeSources\NSPage')
        );
    }

    /**
     * @dataProvider findBySearchQueryAndTranslationProvider
     */
    public function testFindBySearchQueryAndTranslation($query, $expectedClass, Translation $translation)
    {
        $nSources = Kernel::getService('em')
                                ->getRepository('RZ\Renzo\Core\Entities\NodesSources')
                                ->findBySearchQueryAndTranslation($query, $translation);

        if (null !== $nSources) {
            foreach ($nSources as $key => $source) {
                //echo PHP_EOL.$source->getTitle();
                $this->assertEquals(get_class($source), $expectedClass);
            }
        }
    }
    /**
     * @return array
     */
    public static function findBySearchQueryAndTranslationProvider()
    {
        $english = new Translation();
        $english->setLocale('en_GB');

        return array(
            array('text:Propos', 'GeneratedNodeSources\NSPage', $english),
            array('text:Lorem markdownum', 'GeneratedNodeSources\NSPage', $english),
            array('text:septemflua et diversa veniat', 'GeneratedNodeSources\NSPage', $english)
        );
    }
}
