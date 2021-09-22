<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Installer;

use Doctrine\ORM\EntityManagerInterface;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\Kernel;
use Symfony\Component\Yaml\Yaml;

class ThemeInstaller
{
    /**
     * Get Theme information from its config.yml file.
     *
     * @param string $classname
     * @return array
     */
    public static function getThemeInformation(string $classname)
    {
        $themeFolder = call_user_func([$classname, 'getThemeFolder']);
        $file = $themeFolder . "/config.yml";
        if (file_exists($file)) {
            return Yaml::parse(file_get_contents($file));
        }
        return [
            "name" => 'Theme',
            "versionRequire" => '*',
            "supportedLocale" => [],
            "importFiles" => [],
        ];
    }

    /**
     * Install theme.
     *
     * @param string $classname
     * @param EntityManagerInterface $em
     *
     * @return bool
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public static function install(string $classname, EntityManagerInterface $em)
    {
        $data = static::getThemeInformation($classname);
        $data["className"] = $classname;
        $installedLanguage = $em->getRepository(Translation::class)->findAll();

        /**
         * @var int $key
         * @var Translation $locale
         */
        foreach ($installedLanguage as $key => $locale) {
            $installedLanguage[$key] = $locale->getLocale();
        }

        if (count($data["supportedLocale"]) > 0) {
            $exist = false;
            foreach ($data["supportedLocale"] as $locale) {
                if (in_array($locale, $installedLanguage)) {
                    $exist = true;
                }
            }

            if ($exist === false) {
                $newTranslation = new Translation();
                $newTranslation->setLocale($data["supportedLocale"][0]);
                $newTranslation->setName(Translation::$availableLocales[$data["supportedLocale"][0]]);
                $em->persist($newTranslation);
                $em->flush();
            }
        }

        $importFile = false;
        foreach ($data["importFiles"] as $name => $fileNames) {
            if (!empty($fileNames)) {
                $importFile = true;
                break;
            }
        }
        return $importFile;
    }

    /**
     * Assign summary theme information.
     *
     * @param string $classname
     * @param array $assignation
     * @param string $locale
     *
     * @return array
     */
    public static function assignSummaryInfo($classname, &$assignation, $locale)
    {
        $themeFolder = call_user_func([$classname, 'getThemeFolder']);
        $data = static::getThemeInformation($classname);

        $assignation["theme"] = [
            "name" => $data["name"],
            "version" => $data["versionRequire"],
            "supportedLocale" => $data["supportedLocale"],
            "imports" => $data["importFiles"],
        ];

        $assignation["cms"] = ["version" => Kernel::$cmsVersion];
        $assignation["status"] = [];
        $assignation["status"]["version"] = version_compare($data["versionRequire"], Kernel::$cmsVersion) <= 0;
        $assignation["cms"]["locale"] = $locale;
        $assignation["status"]["locale"] = in_array($locale, $data["supportedLocale"]);
        $assignation["status"]["import"] = [];

        $assignation['theme']['haveFileImport'] = false;

        foreach ($data["importFiles"] as $name => $filenames) {
            foreach ($filenames as $filename) {
                $assignation["status"]["import"][$filename] = file_exists($themeFolder . "/" . $filename);
                $assignation['theme']['haveFileImport'] = true;
            }
        }

        $assignation['classname'] = $classname;
        return $assignation;
    }
}
