<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file NodesSourcesRepositoryTest.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\Kernel;
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
                                ->getRepository('RZ\Roadiz\Core\Entities\NodesSources')
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
            array('Propos', 'GeneratedNodeSources\NSPage'),
            array('Lorem markdownum', 'GeneratedNodeSources\NSPage')
        );
    }

    /**
     * @dataProvider findBySearchQueryAndTranslationProvider
     */
    public function testFindBySearchQueryAndTranslation($query, $expectedClass, Translation $translation)
    {
        $nSources = Kernel::getService('em')
                                ->getRepository('RZ\Roadiz\Core\Entities\NodesSources')
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
            array('Propos', 'GeneratedNodeSources\NSPage', $english),
            array('Lorem markdownum', 'GeneratedNodeSources\NSPage', $english)
        );
    }
}
