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
        $this->assertTrue($this->get(NodeTypesImporter::class)->import($json));
        $this->get('em')->flush();
        $this->assertEquals(1, $this->countNodeTypes());
        $this->assertEquals($count, $this->countNodeTypeFields());

        $this->getNodeTypeRepository()->createQueryBuilder('t')->delete()->getQuery()->execute();
        $this->assertEquals(0, $this->countNodeTypes());
        $this->assertEquals(0, $this->countNodeTypeFields());
    }

    /**
     * @return \RZ\Roadiz\Core\Repositories\TagRepository
     */
    protected function getNodeTypeRepository()
    {
        return $this->get('em')->getRepository(NodeType::class);
    }

    /**
     * @return int
     */
    protected function countNodeTypes()
    {
        return $this->getNodeTypeRepository()
            ->createQueryBuilder('t')
            ->select('count(t)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return int
     */
    protected function countNodeTypeFields()
    {
        return $this->get('em')->getRepository(NodeTypeField::class)
            ->createQueryBuilder('t')
            ->select('count(t)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public static function importJsonFileProvider()
    {
        return [
            [
                file_get_contents(dirname(__DIR__) . '/../Fixtures/Importers/Person.json'),
                16,
            ]
        ];
    }
}
