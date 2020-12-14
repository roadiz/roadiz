<?php
declare(strict_types=1);

namespace Themes\Install\Controllers;

use RZ\Roadiz\Config\ConfigurationHandlerInterface;
use RZ\Roadiz\Core\Bags\Settings;
use RZ\Roadiz\Core\Entities\Theme;
use RZ\Roadiz\Utils\Installer\ThemeInstaller;
use RZ\Roadiz\Utils\Theme\ThemeResolverInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Themes\Install\Forms\SiteInformationType;
use Themes\Install\InstallApp;

/**
 * @package Themes\Install\Controllers
 */
class ThemeController extends InstallApp
{
    /**
     * Import theme screen.
     *
     * @param Request $request
     * @param int     $id
     *
     * @return Response
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
     * @return Response
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function themeInstallAction(Request $request)
    {
        /** @var ThemeResolverInterface $themeResolver */
        $themeResolver = $this->get('themeResolver');
        $importFile = ThemeInstaller::install($request->get("classname"), $this->get("em"));

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
     * @return Response
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
     * @return Response
     * @throws \Twig\Error\RuntimeError
     */
    public function themesAction(Request $request)
    {
        /** @var Settings $settingsBag */
        $settingsBag = $this->get('settingsBag');
        $siteName = $settingsBag->get('site_name', 'My website');
        $metaDescription = $settingsBag->get('seo_description', 'My website is beautiful');
        $emailSender = $settingsBag->get('email_sender', '');
        $emailSenderName = $settingsBag->get('email_sender_name', 'My website');
        $timeZone = $this->get('config')['timezone'];
        $defaults = [
            'site_name' => $siteName,
            'seo_description' => $metaDescription,
            'email_sender' => $emailSender,
            'email_sender_name' => $emailSenderName,
            'install_frontend' => true,
            'timezone' => $timeZone != '' ? $timeZone : "Europe/Paris",
        ];
        $informationForm = $this->createForm(SiteInformationType::class, $defaults, [
            'themes_config' => $this->get('config')['themes'],
        ]);
        $informationForm->handleRequest($request);

        if ($informationForm->isSubmitted() && $informationForm->isValid()) {
            $informationData = $informationForm->getData();
            /*
             * Save information
             */
            try {
                $fixtures = $this->getFixtures($request);
                $fixtures->saveInformation($informationData);

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
                $informationForm->addError(new FormError($e->getMessage() . PHP_EOL . $e->getTraceAsString()));
            }
        }
        $this->assignation['infosForm'] = $informationForm->createView();

        return $this->render('steps/themes.html.twig', $this->assignation);
    }
}
