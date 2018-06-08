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
use RZ\Roadiz\Utils\Theme\ThemeResolverInterface;
use Symfony\Component\HttpFoundation\Request;
use Themes\Install\Forms\SiteInformationType;
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
        /** @var ThemeResolverInterface $themeResolver */
        $themeResolver = $this->get('themeResolver');

        /** @var Theme|null $result */
        $result = $themeResolver->findById($id);
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
        /** @var ThemeResolverInterface $themeResolver */
        $themeResolver = $this->get('themeResolver');
        $importFile = ThemeInstaller::install($request, $request->get("classname"), $this->get("em"));
        /** @var Theme $theme */
        $theme = $themeResolver->findThemeByClass($request->get("classname"));

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
        $siteName = $this->get('settingsBag')->get('site_name');
        $metaDescription = $this->get('settingsBag')->get('seo_description');
        $emailSender = $this->get('settingsBag')->get('email_sender');
        $emailSenderName = $this->get('settingsBag')->get('email_sender_name');
        $timeZone = $this->get('config')['timezone'];
        $defaults = [
            'site_name' => $siteName != '' ? $siteName : "My website",
            'seo_description' => $metaDescription != '' ? $metaDescription : "My website is beautiful!",
            'email_sender' => $emailSender != '' ? $emailSender : "",
            'email_sender_name' => $emailSenderName != '' ? $emailSenderName : "",
            'install_frontend' => true,
            'timezone' => $timeZone != '' ? $timeZone : "Europe/Paris",
        ];
        $informationForm = $this->createForm(SiteInformationType::class, $defaults, [
            'themes_config' => $this->get('config')['themes'],
        ]);
        $informationForm->handleRequest($request);

        if ($informationForm->isValid()) {
            $informationData = $informationForm->getData();
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
                $fixtures->saveInformations($informationData);

                if (!empty($informationData["install_theme"])) {
                    /*
                     * Force redirect to avoid resending form when refreshing page
                     */
                    return $this->redirect($this->generateUrl('installThemeSummaryPage', [
                        'classname' => $informationData['className'],
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
        $this->assignation['infosForm'] = $informationForm->createView();

        return $this->render('steps/themes.html.twig', $this->assignation);
    }
}
