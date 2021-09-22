<?php
declare(strict_types=1);

namespace Themes\DefaultTheme\Controllers;

use JMS\Serializer\SerializationContext;
use RZ\Roadiz\Core\AbstractEntities\TranslationInterface;
use RZ\Roadiz\Core\Entities\Node;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Themes\DefaultTheme\DefaultThemeApp;
use Themes\Rozier\Forms\NodeSource\NodeSourceType;

/**
 * Frontend controller to handle Page node-type request.
 */
class PageController extends DefaultThemeApp
{
    /**
     * @param Request $request
     * @param string $_locale
     *
     * @return JsonResponse
     */
    public function embedAction(Request $request, $name, string $_locale = 'en')
    {
        $translation = $this->bindLocaleFromRoute($request, $_locale);
        $this->prepareThemeAssignation(null, $translation);

        return new JsonResponse([
            'message' => 'Hello '. $name. '!'
        ]);
    }

    /**
     * Default action for any Page node.
     *
     * @param Request $request
     * @param Node|null $node
     * @param TranslationInterface|null $translation
     * @param string $_format
     *
     * @return Response
     */
    public function indexAction(
        Request $request,
        Node $node = null,
        TranslationInterface $translation = null,
        string $_format = 'html'
    ) {
        $this->prepareThemeAssignation($node, $translation);

        if ($request->query->has('404') && $request->query->get('404') == true) {
            throw $this->createNotFoundException('This is a 404 page manually triggered via ' . ResourceNotFoundException::class);
        }

        if ($request->query->has('not-found') && $request->query->get('not-found') == true) {
            throw new NotFoundHttpException('This is a 404 page manually triggered via '. NotFoundHttpException::class);
        }

        /*
         * You can add a NodeSourceType form to edit it
         * right into your front page.
         * Awesome isn’t it?
         */
        if ($request->getRequestFormat() === 'json') {
            $response = new JsonResponse(
                $this->get('serializer')->serialize(
                    $this->nodeSource,
                    'json',
                    SerializationContext::create()->setGroups(['nodes_sources', 'urls'])
                ),
                Response::HTTP_OK,
                [],
                true
            );
        } else {
            $response = $this->render('pages/page.html.twig', $this->assignation);
        }

        if ($this->getNode()->getTtl() > 0) {
            return $this->makeResponseCachable($request, $response, $this->getNode()->getTtl());
        }
        return $response;
    }

    /**
     * @param Request $request
     *
     * @return null|RedirectResponse
     */
    protected function handleEditForm(Request $request)
    {
        /*
         * Current page edition form
         */
        $form = $this->createForm(
            NodeSourceType::class,
            $this->nodeSource,
            [
                'class' => $this->node->getNodeType()->getSourceEntityFullQualifiedClassName(),
                'nodeType' => $this->node->getNodeType(),
                'withVirtual' => false,
                'withTitle' => false,
            ]
        );
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->get('em')->flush();
            return $this->redirect($this->generateUrl($this->nodeSource));
        }

        $this->assignation['form'] = $form->createView();

        return null;
    }
}
