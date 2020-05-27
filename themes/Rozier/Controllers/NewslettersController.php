<?php
declare(strict_types=1);

namespace Themes\Rozier\Controllers;

use RZ\Roadiz\CMS\Forms\NodeSource\NodeSourceType;
use RZ\Roadiz\Core\Entities\Newsletter;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\Exceptions\EntityAlreadyExistsException;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Themes\Rozier\RozierApp;
use Themes\Rozier\Traits\NodesTrait;

/**
 * Class NewslettersController
 *
 * @package Themes\Rozier\Controllers
 */
class NewslettersController extends RozierApp
{
    use NodesTrait;

    public function listAction(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_NEWSLETTERS');

        $translation = $this->get('defaultTranslation');
        $listManager = $this->createEntityListManager(
            Newsletter::class,
            [],
            ["id" => "DESC"]
        );
        $listManager->setDisplayingNotPublishedNodes(true);
        $listManager->handle();

        $this->assignation['filters'] = $listManager->getAssignation();
        $this->assignation['newsletters'] = $listManager->getEntities();
        $this->assignation['nodeTypes'] = $this->get('em')
             ->getRepository(NodeType::class)
             ->findBy(['newsletterType' => true]);
        $this->assignation['translation'] = $translation;

        return $this->render('newsletters/list.html.twig', $this->assignation);
    }

    /**
     * Handle node creation pages.
     *
     * @param Request $request
     * @param int     $nodeTypeId
     * @param int     $translationId
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function addAction(Request $request, $nodeTypeId, $translationId = null)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_NEWSLETTERS');

        $type = $this->get('em')
                     ->find(NodeType::class, $nodeTypeId);

        $trans = $this->get('defaultTranslation');

        if ($translationId !== null) {
            $trans = $this->get('em')
                          ->find(Translation::class, (int) $translationId);
        }

        if ($type !== null &&
            $trans !== null) {

            /** @var Form $form */
            $form = $this->get('formFactory')
                         ->createBuilder()
                         ->add('title', TextType::class, [
                             'label' => 'title',
                             'constraints' => [
                                 new NotNull(),
                                 new NotBlank(),
                             ],
                         ])
                ->getForm();
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                try {
                    $node = $this->createNode($form->get('title')->getData(), $trans, null, $type);

                    $newsletter = new Newsletter($node);
                    $newsletter->setStatus(Newsletter::DRAFT);

                    $this->get('em')->persist($newsletter);
                    $this->get('em')->flush();

                    $msg = $this->getTranslator()->trans(
                        'newsletter.%name%.created',
                        ['%name%' => $node->getNodeName()]
                    );
                    $this->publishConfirmMessage($request, $msg);

                    return $this->redirect($this->generateUrl(
                        'newslettersIndexPage'
                    ));
                } catch (EntityAlreadyExistsException $e) {
                    $form->addError(new FormError($e->getMessage()));
                }
            }

            $this->assignation['translation'] = $trans;
            $this->assignation['form'] = $form->createView();
            $this->assignation['type'] = $type;
            $this->assignation['nodeTypesCount'] = true;

            return $this->render('newsletters/add.html.twig', $this->assignation);
        }

        throw new ResourceNotFoundException();
    }

    /**
     * Return an edition form for requested newsletter.
     *
     * @param Request $request
     * @param int     $newsletterId
     * @param int     $translationId
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function editAction(Request $request, $newsletterId, $translationId)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_NEWSLETTERS');

        $translation = $this->get('em')
                            ->find(Translation::class, (int) $translationId);

        if ($translation !== null) {
            /*
             * Here we need to directly select nodeSource
             * if not doctrine will grab a cache tag because of NodeTreeWidget
             * that is initialized before calling route method.
             */
            /** @var Newsletter $newsletter */
            $newsletter = $this->get('em')
                               ->find(Newsletter::class, (int) $newsletterId);

            /** @var NodesSources $source */
            $source = $this->get('em')
                           ->getRepository(NodesSources::class)
                           ->setDisplayingNotPublishedNodes(true)
                           ->findOneBy(['translation' => $translation, 'node' => $newsletter->getNode()]);

            if (null !== $source) {
                $node = $source->getNode();

                $this->assignation['translation'] = $translation;
                $this->assignation['available_translations'] = $this->get('em')
                                                                    ->getRepository(Translation::class)
                                                                    ->findAvailableTranslationsForNode($newsletter->getNode());
                $this->assignation['node'] = $node;
                $this->assignation['source'] = $source;
                $this->assignation['newsletterId'] = $newsletterId;

                /*
                 * Form
                 */
                $form = $this->createForm(
                    NodeSourceType::class,
                    $source,
                    [
                        'class' => $node->getNodeType()->getSourceEntityFullQualifiedClassName(),
                        'nodeType' => $node->getNodeType(),
                        'controller' => $this,
                        'entityManager' => $this->get('em'),
                        'container' => $this->getContainer(),
                    ]
                );
                $form->handleRequest($request);

                if ($form->isSubmitted()) {
                    if ($form->isSubmitted() && $form->isValid()) {
                        $this->get('em')->flush();

                        $msg = $this->getTranslator()->trans('newsletter.%newsletter%.updated.%translation%', [
                            '%newsletter%' => $source->getNode()->getNodeName(),
                            '%translation%' => $source->getTranslation()->getName(),
                        ]);

                        $this->publishConfirmMessage($request, $msg);

                        if ($request->isXmlHttpRequest()) {
                            $url = $this->generateUrl(
                                'newslettersPreviewPage',
                                ['newsletterId' => $newsletterId]
                            );

                            return new JsonResponse([
                                'status' => 'success',
                                'public_url' => $url,
                                'errors' => []
                            ], JsonResponse::HTTP_PARTIAL_CONTENT);
                        }

                        return $this->redirect($this->generateUrl(
                            'newslettersEditPage',
                            ['newsletterId' => $newsletterId, 'translationId' => $translationId]
                        ));
                    }
                    /*
                     * Handle errors when Ajax POST requests
                     */
                    if ($request->isXmlHttpRequest()) {
                        $errors = $this->getErrorsAsArray($form);
                        return new JsonResponse([
                            'status' => 'fail',
                            'errors' => $errors,
                            'message' => $this->getTranslator()->trans('form_has_errors.check_you_fields'),
                        ], JsonResponse::HTTP_BAD_REQUEST);
                    }
                }

                $this->assignation['form'] = $form->createView();

                return $this->render('newsletters/edit.html.twig', $this->assignation);
            }
        }

        throw new ResourceNotFoundException();
    }
}
