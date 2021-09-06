<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Controllers;

use JMS\Serializer\SerializationContext;
use RZ\Roadiz\Core\AbstractEntities\TranslationInterface;
use RZ\Roadiz\Core\ContainerAwareInterface;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\Theme;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Error\LoaderError;

final class DefaultController extends FrontendController
{
    /**
     * @param Request $request
     * @param Node|null $node
     * @param TranslationInterface|null $translation
     * @param string $_format
     * @param Theme|null $theme
     *
     * @return JsonResponse|Response
     * @throws \Twig\Error\RuntimeError
     */
    public function indexAction(
        Request $request,
        Node $node = null,
        TranslationInterface $translation = null,
        string $_format = 'html',
        Theme $theme = null
    ) {
        /*
         * Fetch all assignation from current Theme.
         */
        if (null !== $theme) {
            $className = $theme->getClassName();
            $usedTheme = new $className();
            if ($usedTheme instanceof ContainerAwareInterface) {
                $usedTheme->setContainer($this->getContainer());
            }
            if ($usedTheme instanceof FrontendController) {
                $usedTheme->__init();
                $usedTheme->prepareThemeAssignation($node, $translation);
                $this->assignation = array_merge($this->assignation, $usedTheme->getAssignation());
                $this->node = $usedTheme->getNode();
                $this->nodeSource = $usedTheme->getNodeSource();
                $this->translation = $usedTheme->getTranslation();
            } else {
                $this->prepareThemeAssignation($node, $translation);
            }
        } else {
            $this->prepareThemeAssignation($node, $translation);
        }

        if ($request->getRequestFormat() === 'json') {
            $response = new JsonResponse(
                $this->get('serializer')->serialize(
                    $this->getNodeSource(),
                    'json',
                    SerializationContext::create()->setGroups(['nodes_sources', 'urls'])
                ),
                Response::HTTP_OK,
                [],
                true
            );
        } else {
            try {
                $response = $this->render('pages/' . strtolower($node->getNodeType()->getName()) . '.html.twig', $this->assignation, null, '/');
            } catch (LoaderError $exception) {
                /*
                 * Transform template not found into 404 error for node explicitly not handled.
                 */
                throw $this->createNotFoundException($exception->getMessage(), $exception);
            }
        }

        if ($this->getNode()->getTtl() > 0) {
            return $this->makeResponseCachable($request, $response, $this->getNode()->getTtl());
        }
        return $response;
    }
}
