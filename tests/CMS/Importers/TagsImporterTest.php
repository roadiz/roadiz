<?php

use RZ\Roadiz\CMS\Importers\TagsImporter;
use RZ\Roadiz\Core\Entities\Tag;
use RZ\Roadiz\Core\Entities\TagTranslation;
use RZ\Roadiz\Core\Serializers\TagJsonSerializer;
use RZ\Roadiz\Tests\SchemaDependentCase;

class TagsImporterTest extends SchemaDependentCase
{
    /**
     * @dataProvider importJsonFileProvider
     */
    public function testImportJsonFile($json, $count)
    {
        $this->assertTrue(TagsImporter::importJsonFile($json, $this->get('em'), $this->get('factory.handler')));
        $this->assertEquals($count, $this->countTags());
        $this->assertEquals($count, $this->countTagTranslations());

        $this->getTagRepository()->createQueryBuilder('t')->delete()->getQuery()->execute();
        $this->assertEquals(0, $this->countTags());
        $this->assertEquals(0, $this->countTagTranslations());
    }

    /**
     * @dataProvider importJsonFileProvider
     */
    public function testDeserializeJsonFile($json, $count)
    {
        $serializer = new TagJsonSerializer();
        $tags = $serializer->deserialize($json);

        $this->assertEquals(1, count($tags));
        $this->assertEquals(($count - 1), count($tags[0]->getChildren()));
    }

    /**
     * @return \RZ\Roadiz\Core\Repositories\TagRepository
     */
    public function getTagRepository()
    {
        return $this->get('em')->getRepository(Tag::class);
    }

    /**
     * @return \RZ\Roadiz\Core\Repositories\TagTranslation
     */
    public function getTagTranslationRepository()
    {
        return $this->get('em')->getRepository(TagTranslation::class);
    }


    /**
     * @return int
     */
    public function countTags()
    {
        return $this->getTagRepository()->createQueryBuilder('t')->select('count(t)')->getQuery()->getSingleScalarResult();
    }

    /**
     * @return int
     */
    public function countTagTranslations()
    {
        return $this->getTagTranslationRepository()->createQueryBuilder('t')->select('count(t)')->getQuery()->getSingleScalarResult();
    }

    /**
     * @return array
     */
    public function tagNames()
    {
        return $this->getTagRepository()->createQueryBuilder('t')->select('t.tagName')->getQuery()->getScalarResult();
    }

    public static function importJsonFileProvider()
    {
        return [
            [
                file_get_contents(dirname(__DIR__) . '/../Fixtures/Importers/tag-pays-20180426182620.rzg'),
                250,
            ],
            [
                file_get_contents(dirname(__DIR__) . '/../Fixtures/Importers/tag-thematiques-20180426190148.rzg'),
                3
            ]
        ];
    }
}
