<?php
/*
 * Copyright © 2015, Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * Except as contained in this notice, the name of the ROADIZ shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from Ambroise Maupate and Julien Blanchet.
 *
 * @file ThemeController.php
 * @author Maxime Constantinian
 */
namespace Themes\Install\Controllers;

use RZ\Roadiz\Console\Tools\Fixtures;
use RZ\Roadiz\Console\Tools\YamlConfiguration;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\Kernel;
use Symfony\Component\HttpFoundation\Request;
use Themes\Install\InstallApp;

/**
 * ThemeController
 */
class ThemeController extends InstallApp
{
    /**
     * Import theme screen.
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param int                                      $id
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function importThemeAction(Request $request, $id)
    {

        $result = $this->getService('em')->find('RZ\Roadiz\Core\Entities\Theme', $id);

        $array = explode('\\', $result->getClassName());
        $file = ROADIZ_ROOT . "/themes/" . $array[2] . "/config.yml";
        $yaml = new YamlConfiguration($file);

        $yaml->load();

        $data = $yaml->getConfiguration();

        $this->assignation = array_merge($this->assignation, $data["importFiles"]);
        $this->assignation["themeId"] = $id;

        return $this->render('steps/importTheme.html.twig', $this->assignation);
    }

    /**
     * Install theme screen.
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function themeInstallAction(Request $request)
    {
        $array = explode('\\', $request->get("classname"));
        $file = ROADIZ_ROOT . "/themes/" . $array[2] . "/config.yml";
        $yaml = new YamlConfiguration($file);

        $yaml->load();

        $data = $yaml->getConfiguration();

        $fix = new Fixtures($this->getService("em"));
        $data["className"] = $request->get("classname");
        $fix->installTheme($data);
        $theme = $this->getService("em")->getRepository("RZ\Roadiz\Core\Entities\Theme")
                      ->findOneByClassName($request->get("classname"));

        $installedLanguage = $this->getService("em")->getRepository("RZ\Roadiz\Core\Entities\Translation")
                                  ->findAll();

        foreach ($installedLanguage as $key => $locale) {
            $installedLanguage[$key] = $locale->getLocale();
        }

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
            $this->getService('em')->persist($newTranslation);
            $this->getService('em')->flush();
        }

        $importFile = false;
        foreach ($data["importFiles"] as $name => $filenames) {
            foreach ($filenames as $filename) {
                $importFile = true;
                break;
            }
        }

        if ($importFile === false) {
            return $this->redirect($this->generateUrl(
                'installUserPage',
                ["id" => $theme->getId()]
            ));
        } else {
            return $this->redirect($this->generateUrl(
                'installImportThemePage',
                ["id" => $theme->getId()]
            ));
        }
    }

    /**
     * Theme summary screen
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function themeSummaryAction(Request $request)
    {
        $array = explode('\\', $request->get("classname"));
        $file = ROADIZ_ROOT . "/themes/" . $array[2] . "/config.yml";
        $yaml = new YamlConfiguration($file);

        $yaml->load();

        $data = $yaml->getConfiguration();

        $this->assignation["theme"] = [
            "name" => $data["name"],
            "version" => $data["versionRequire"],
            "supportedLocale" => $data["supportedLocale"],
            "imports" => $data["importFiles"],
        ];

        $this->assignation["cms"] = ["version" => Kernel::$cmsVersion];
        $this->assignation["status"] = [];

        $this->assignation["status"]["version"] = (version_compare($data["versionRequire"], Kernel::$cmsVersion) <= 0) ? true : false;

        $this->assignation["cms"]["locale"] = $request->getLocale();
        $this->assignation["status"]["locale"] = in_array($request->getLocale(), $data["supportedLocale"]);

        $this->assignation["status"]["import"] = [];

        $this->assignation['theme']['haveFileImport'] = false;

        foreach ($data["importFiles"] as $name => $filenames) {
            foreach ($filenames as $filename) {
                $this->assignation["status"]["import"][$filename] = file_exists(ROADIZ_ROOT . "/themes/" . $array[2] . "/" . $filename);
                $this->assignation['theme']['haveFileImport'] = true;
            }
        }

        $this->assignation['classname'] = $request->get("classname");

        return $this->render('steps/themeSummary.html.twig', $this->assignation);
    }

    /**
     * Theme install screen.
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function themesAction(Request $request)
    {
        $infosForm = $this->buildInformationsForm($request);

        if ($infosForm !== null) {
            $infosForm->handleRequest();

            if ($infosForm->isValid()) {
                /*
                 * Save informations
                 */
                try {
                    $fixtures = new Fixtures($this->getService("em"));
                    $fixtures->saveInformations($infosForm->getData());

                    if (!empty($infosForm->getData()["install_theme"])) {
                        /*
                         * Force redirect to avoid resending form when refreshing page
                         */
                        return $this->redirect($this->generateUrl(
                            'installThemeSummaryPage'
                        ) . "?classname=" . urlencode($infosForm->getData()['className']));
                    } else {
                        return $this->redirect($this->generateUrl(
                            'installUserPage'
                        ));
                    }

                } catch (\Exception $e) {
                    $this->assignation['error'] = true;
                    $this->assignation['errorMessage'] = $e->getMessage() . PHP_EOL . $e->getTraceAsString();
                }

            }
            $this->assignation['infosForm'] = $infosForm->createView();
        }

        return $this->render('steps/themes.html.twig', $this->assignation);
    }
}
