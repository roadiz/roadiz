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
 * @file ThemesController.php
 * @author Ambroise Maupate
 */
namespace Themes\Rozier\Controllers;

use RZ\Roadiz\Core\Events\CacheEvents;
use RZ\Roadiz\Core\Events\FilterCacheEvent;
use RZ\Roadiz\Utils\Installer\ThemeInstaller;
use RZ\Roadiz\Utils\Theme\ThemeResolverInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\InvalidParameterException;
use Themes\Rozier\RozierApp;

/**
 * Class ThemesController
 *
 * @package Themes\Rozier\Controllers
 * @deprecated Themes are no more registered in database.
 */
class ThemesController extends RozierApp
{
    /**
     * Import theme screen.
     *
     * @param Request $request
     * @param int $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function importAction(Request $request, $id)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_THEMES');
        /** @var ThemeResolverInterface $themeResolver */
        $themeResolver = $this->get('themeResolver');
        $result = $themeResolver->findById($id);
        $data = ThemeInstaller::getThemeInformation($result->getClassName());

        $this->assignation = array_merge($this->assignation, $data["importFiles"]);
        $this->assignation["themeId"] = $id;

        return $this->render('themes/import.html.twig', $this->assignation);
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_THEMES');

        /** @var ThemeResolverInterface $themeResolver */
        $themeResolver = $this->get('themeResolver');
        $this->assignation['themes'] = $themeResolver->findAll();
        $this->assignation['availableThemesCount'] = count($themeResolver->findAll());

        return $this->render('themes/list.html.twig', $this->assignation);
    }

    /**
     * Return a summary for requested theme.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function summaryAction(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_THEMES');
        if (!$request->query->has("classname")) {
            throw new InvalidParameterException('classname query param is mandatory');
        }
        ThemeInstaller::assignSummaryInfo($request->get("classname"), $this->assignation, $request->getLocale());

        return $this->render('themes/summary.html.twig', $this->assignation);
    }

    /**
     * Return a setting form for requested theme.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function settingAction(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_THEMES');

        if (!$request->query->has("classname")) {
            throw new InvalidParameterException('classname query param is mandatory');
        }

        $classname = $request->get("classname");
        $importFile = ThemeInstaller::install($classname, $this->get("em"));
        /** @var ThemeResolverInterface $themeResolver */
        $themeResolver = $this->get('themeResolver');
        $theme = $themeResolver->findThemeByClass($classname) ;

        /** @var EventDispatcher $dispatcher */
        $dispatcher = $this->get('dispatcher');
        $event = new FilterCacheEvent($this->get('kernel'));
        $dispatcher->dispatch(CacheEvents::PURGE_REQUEST, $event);

        if ($importFile === false) {
            return $this->redirect($this->generateUrl(
                'themesHomePage'
            ));
        }

        return $this->redirect($this->generateUrl(
            'themesImportPage',
            ["id" => $theme->getId()]
        ));
    }
}
