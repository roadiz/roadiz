<?php

use RZ\Roadiz\CMS\Importers\SettingsImporter;
use RZ\Roadiz\Core\Entities\Setting;
use RZ\Roadiz\Tests\SchemaDependentCase;

class SettingsImporterTest extends SchemaDependentCase
{
    /**
     * @dataProvider importJsonFileProvider
     */
    public function testImportJsonFile($json, $count)
    {
        $this->assertTrue($this->get(SettingsImporter::class)->import($json));
        $this->get('em')->flush();
        $this->assertEquals($count, $this->countSettings());

        $this->getSettingRepository()->createQueryBuilder('t')->delete()->getQuery()->execute();
        $this->assertEquals(0, $this->countSettings());
    }

    /**
     * @return \RZ\Roadiz\Core\Repositories\TagRepository
     */
    protected function getSettingRepository()
    {
        return $this->get('em')->getRepository(Setting::class);
    }

    /**
     * @return int
     */
    protected function countSettings()
    {
        return $this->getSettingRepository()
            ->createQueryBuilder('t')
            ->select('count(t)')
            ->getQuery()->getSingleScalarResult();
    }

    public static function importJsonFileProvider()
    {
        return [
            [
                file_get_contents(dirname(__DIR__) . '/../Fixtures/Importers/settings.json'),
                39,
            ]
        ];
    }
}
