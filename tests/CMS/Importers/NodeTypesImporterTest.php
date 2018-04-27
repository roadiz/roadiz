<?php

use RZ\Roadiz\Core\Entities\NodeTypeField;
use RZ\Roadiz\CMS\Importers\NodeTypesImporter;
use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Tests\SchemaDependentCase;

class NodeTypesImporterTest extends SchemaDependentCase
{
    /**
     * @dataProvider importJsonFileProvider
     */
    public function testImportJsonFile($json, $count)
    {
        $this->assertTrue(NodeTypesImporter::importJsonFile($json, $this->get('em'), $this->get('factory.handler')));
        $this->assertEquals(1, $this->countNodeTypes());
        $this->assertEquals($count, $this->countNodeTypeFields());

        $this->getNodeTypeRepository()->createQueryBuilder('t')->delete()->getQuery()->execute();
        $this->assertEquals(0, $this->countNodeTypes());
        $this->assertEquals(0, $this->countNodeTypeFields());
    }

    /**
     * @return \RZ\Roadiz\Core\Repositories\TagRepository
     */
    public function getNodeTypeRepository()
    {
        return $this->get('em')->getRepository(NodeType::class);
    }

    /**
     * @return int
     */
    public function countNodeTypes()
    {
        return $this->getNodeTypeRepository()->createQueryBuilder('t')->select('count(t)')->getQuery()->getSingleScalarResult();
    }

    /**
     * @return int
     */
    public function countNodeTypeFields()
    {
        return $this->get('em')->getRepository(NodeTypeField::class)->createQueryBuilder('t')->select('count(t)')->getQuery()->getSingleScalarResult();
    }

    public static function importJsonFileProvider()
    {
        return [
            [
                file_get_contents(dirname(__DIR__) . '/../Fixtures/Importers/Person.rzt'),
                16,
            ]
        ];
    }
}
