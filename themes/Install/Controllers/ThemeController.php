<?php
/**
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
use RZ\Roadiz\Core\Entities\Theme;
use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Utils\Installer\ThemeInstaller;
use Symfony\Component\HttpFoundation\Request;
use Themes\Install\InstallApp;

/**
 * Class ThemeController
 * @package Themes\Install\Controllers
 */
class ThemeController extends InstallApp
{
    /**
     * Import theme screen.
     *
     * @param Request $request
     * @param int $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Twig_Error_Runtime
     */
    public function importThemeAction(Request $request, $id)
    {
        /** @var Theme|null $result */
        $result = $this->get('em')->find(Theme::class, $id);

        $data = ThemeInstaller::getThemeInformation($result->getClassName());

        $this->assignation = array_merge($this->assignation, $data["importFiles"]);
        $this->assignation["themeId"] = $id;

        return $this->render('steps/importTheme.html.twig', $this->assignation);
    }

    /**
     * Install theme screen.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function themeInstallAction(Request $request)
    {
        $importFile = ThemeInstaller::install($request, $request->get("classname"), $this->get("em"));
        /** @var Theme $theme */
        $theme = $this->get("em")
                      ->getRepository(Theme::class)
                      ->findOneByClassName($request->get("classname"));
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
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Twig_Error_Runtime
     */
    public function themeSummaryAction(Request $request)
    {
        ThemeInstaller::assignSummaryInfo($request->get("classname"), $this->assignation, $request->getLocale());

        return $this->render('steps/themeSummary.html.twig', $this->assignation);
    }

    /**
     * Theme install screen.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Twig_Error_Runtime
     */
    public function themesAction(Request $request)
    {
        $infosForm = $this->buildInformationsForm($request);

        if ($infosForm !== null) {
            $infosForm->handleRequest($request);

            if ($infosForm->isValid()) {
                /*
                 * Save information
                 */
                try {
                    /** @var Kernel $kernel */
                    $kernel = $this->get('kernel');
                    $fixtures = new Fixtures(
                        $this->get('em'),
                        $kernel->getCacheDir(),
                        $kernel->getRootDir() . '/conf/config.yml',
                        $kernel->getRootDir(),
                        $kernel->isDebug(),
                        $request
                    );
                    $fixtures->saveInformations($infosForm->getData());

                    if (!empty($infosForm->getData()["install_theme"])) {
                        /*
                         * Force redirect to avoid resending form when refreshing page
                         */
                        return $this->redirect($this->generateUrl('installThemeSummaryPage', [
                            'classname' => $infosForm->getData()['className'],
                        ]));
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
