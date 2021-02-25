<?php

use Doctrine\ORM\QueryBuilder;
use RZ\Roadiz\CMS\Importers\TagsImporter;
use RZ\Roadiz\Core\Entities\Tag;
use RZ\Roadiz\Core\Entities\TagTranslation;
use RZ\Roadiz\Core\Repositories\EntityRepository;
use RZ\Roadiz\Core\Repositories\TagRepository;
use RZ\Roadiz\Tests\SchemaDependentCase;

class TagsImporterTest extends SchemaDependentCase
{
    /**
     * @dataProvider importJsonFileProvider
     */
    public function testImportJsonFile($json, $count)
    {
        $this->getTagRepository()->createQueryBuilder('t')->delete()->getQuery()->execute();
        $this->assertEquals(0, $this->countTags());
        $this->assertEquals(0, $this->countTagTranslations());

        $this->assertTrue($this->get(TagsImporter::class)->import($json));
        $this->get('em')->flush();
        $this->assertEquals($count, $this->countTags());
        $this->assertEquals($count, $this->countTagTranslations());
    }

    /**
     * @dataProvider importJsonFileProvider
     */
    public function testDeserializeJsonFile($json, $count)
    {
        $serializer = $this->get('serializer');
        /** @var Tag $tag */
        $tag = $serializer->deserialize(
            $json,
            Tag::class,
            'json'
        );

        $this->assertEquals(($count - 1), count($tag->getChildren()));
    }

    /**
     * @return TagRepository
     */
    protected function getTagRepository()
    {
        return $this->get('em')->getRepository(Tag::class);
    }

    /**
     * @return EntityRepository
     */
    protected function getTagTranslationRepository()
    {
        return $this->get('em')->getRepository(TagTranslation::class);
    }

    /**
     * @return int
     */
    public function countTags(): int
    {
        /** @var QueryBuilder $qb */
        $qb = $this->getTagRepository()
            ->createQueryBuilder('t');
        return $qb->select($qb->expr()->countDistinct('t'))
            ->getQuery()->getSingleScalarResult();
    }

    /**
     * @return int
     */
    public function countTagTranslations(): int
    {
        /** @var QueryBuilder $qb */
        $qb = $this->getTagTranslationRepository()
            ->createQueryBuilder('t');
        return $qb->select($qb->expr()->countDistinct('t'))
            ->getQuery()->getSingleScalarResult();
    }

    public static function importJsonFileProvider()
    {
        return [
            [
                file_get_contents(dirname(__DIR__) . '/../Fixtures/Importers/tag-pays-20180426182620.json'),
                250,
            ],
            [
                file_get_contents(dirname(__DIR__) . '/../Fixtures/Importers/tag-thematiques-20180426190148.json'),
                3
            ]
        ];
    }
}
