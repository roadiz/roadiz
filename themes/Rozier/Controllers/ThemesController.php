<?php
declare(strict_types=1);

namespace Themes\Rozier\Controllers;

use RZ\Roadiz\Core\Events\Cache\CachePurgeRequestEvent;
use RZ\Roadiz\Utils\Installer\ThemeInstaller;
use RZ\Roadiz\Utils\Theme\ThemeResolverInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\InvalidParameterException;
use Themes\Rozier\RozierApp;

/**
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
        $event = new CachePurgeRequestEvent($this->get('kernel'));
        $dispatcher->dispatch($event);

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
