<?php

use GeneratedNodeSources\NSPage;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\Repositories\NodesSourcesRepository;
use RZ\Roadiz\Tests\DefaultThemeDependentCase;

class NodesSourcesRepositorySearchTest extends DefaultThemeDependentCase
{
    /**
     * @dataProvider findBySearchQueryProvider
     * @param $query
     * @param $expectedClass
     */
    public function testFindBySearchQuery($query, $expectedClass)
    {
        /** @var NodesSourcesRepository $repository */
        $repository = static::getManager()
            ->getRepository(NodesSources::class)
            ->setDisplayingAllNodesStatuses(true)
            ->setDisplayingNotPublishedNodes(true)
        ;
        $nSources = $repository->findBySearchQuery($query);

        if (null !== $nSources || count($nSources) > 0) {
            foreach ($nSources as $source) {
                $this->assertEquals($expectedClass, get_class($source));
            }
        } else {
            $this->markTestSkipped('No nodes are available for this search.');
        }
    }
    /**
     * @return array
     */
    public static function findBySearchQueryProvider()
    {
        return [
            ['Propos', NSPage::class],
            ['About', NSPage::class],
        ];
    }

    /**
     * @dataProvider findBySearchQueryAndTranslationProvider
     * @param $query
     * @param $expectedClass
     * @param Translation $translation
     */
    public function testFindBySearchQueryAndTranslation($query, $expectedClass, Translation $translation)
    {
        $nSources = static::getManager()
            ->getRepository(NodesSources::class)
            ->setDisplayingAllNodesStatuses(true)
            ->setDisplayingNotPublishedNodes(true)
            ->findBySearchQueryAndTranslation($query, $translation);

        if (null !== $nSources || count($nSources) > 0) {
            foreach ($nSources as $source) {
                $this->assertEquals($expectedClass, get_class($source));
            }
        } else {
            $this->markTestSkipped('No nodes are available for this search.');
        }
    }
    /**
     * @return array
     */
    public static function findBySearchQueryAndTranslationProvider()
    {
        $english = new Translation();
        $english->setLocale('en');
        $french = new Translation();
        $french->setLocale('fr');

        return [
            ['Propos', NSPage::class, $french],
            ['About', NSPage::class, $english],
        ];
    }

    /**
     * @throws \Doctrine\ORM\Tools\ToolsException
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        static::runCommand('themes:install --nodes "/Themes/DefaultTheme/DefaultThemeApp"');
        static::runCommand('solr:reindex');
    }
}
