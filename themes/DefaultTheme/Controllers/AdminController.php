<?php
/**
 * Copyright © 2014, Ambroise Maupate and Julien Blanchet
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
 *
 * @file AdminController.php
 * @author Ambroise Maupate
 */
namespace Themes\DefaultTheme\Controllers;

use RZ\Roadiz\CMS\Forms\ExplorerProviderItemType;
use RZ\Roadiz\Core\Entities\Setting;
use Symfony\Component\HttpFoundation\Request;
use Themes\DefaultTheme\DefaultThemeApp;
use Themes\Rozier\Explorer\SettingsProvider;
use Themes\Rozier\RozierApp;

/**
 * Class AdminController
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
