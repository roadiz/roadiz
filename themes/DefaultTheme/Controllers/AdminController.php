<?php
declare(strict_types=1);

namespace Themes\DefaultTheme\Controllers;

use RZ\Roadiz\CMS\Forms\ExplorerProviderItemType;
use RZ\Roadiz\Core\Entities\Setting;
use Symfony\Component\HttpFoundation\Request;
use Themes\DefaultTheme\DefaultThemeApp;
use Themes\Rozier\Explorer\SettingsProvider;
use Themes\Rozier\RozierApp;

/**
 * @package Themes\DefaultTheme\Controllers
 */
class AdminController extends RozierApp
{
    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function listAction(
        Request $request
    ) {
        /*
         * Use an existing ExplorerProviderInterface
         * or create your own!
         */
        $explorerProvider = new SettingsProvider();
        $explorerProvider->setContainer($this->getContainer());

        /*
         * Create a form with ExplorerProviderItemType
         * this AbstractType will convert your entities to form
         * and display a beautiful widget and its ajax explorer.
         */
        $form = $this->createForm(ExplorerProviderItemType::class, [
            $this->get('em')->getRepository(Setting::class)->findOneByName('admin_image'),
            $this->get('em')->getRepository(Setting::class)->findOneByName('display_debug_panel')
        ], [
            'label' => 'Choose your settings',
            'explorerProvider' => $explorerProvider,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->assignation['data'] = $form->getData();
        }

        $this->assignation['form'] = $form->createView();
        /*
         * Use a namespace to force using this theme template
         * and not a other theme one if its filename is the same.
         */
        return $this->render('admin/test.html.twig', $this->assignation, null, DefaultThemeApp::getThemeDir());
    }
}
