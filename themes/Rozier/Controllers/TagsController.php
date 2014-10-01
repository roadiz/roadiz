<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file TagsController.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace Themes\Rozier\Controllers;

use Themes\Rozier\RozierApp;
use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Entities\Tag;
use RZ\Renzo\Core\Entities\TagTranslation;
use RZ\Renzo\Core\Entities\Translation;
use RZ\Renzo\Core\Entities\NodeTypeField;
use RZ\Renzo\Core\ListManagers\EntityListManager;
use RZ\Renzo\Core\Exceptions\EntityAlreadyExistsException;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use \Symfony\Component\Form\Form;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

/**
 * {@inheritdoc}
 */
class TagsController extends RozierApp
{
    /**
     * List every tags.
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {
        $this->validateAccessForRole('ROLE_ACCESS_TAGS');

        /*
         * Manage get request to filter list
         */
        $listManager = new EntityListManager(
            $request,
            $this->getService('em'),
            'RZ\Renzo\Core\Entities\Tag'
        );
        $listManager->handle();

        $this->assignation['filters'] = $listManager->getAssignation();
        $this->assignation['tags'] = $listManager->getEntities();

        return new Response(
            $this->getTwig()->render('tags/list.html.twig', $this->assignation),
            Response::HTTP_OK,
            array('content-type' => 'text/html')
        );
    }

    /**
     * Return an edition form for current translated tag.
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param integer                                  $tagId
     * @param integer | null                           $translationId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function editTranslatedAction(Request $request, $tagId, $translationId = null)
    {
        $this->validateAccessForRole('ROLE_ACCESS_TAGS');

        if (null === $translationId) {
            $translation = $this->getService('em')
                ->getRepository('RZ\Renzo\Core\Entities\Translation')
                ->findDefault();
        } else {
            $translation = $this->getService('em')
                    ->find('RZ\Renzo\Core\Entities\Translation', (int) $translationId);
        }

        if ($translation !== null) {

            $tag = $this->getService('em')
                ->getRepository('RZ\Renzo\Core\Entities\Tag')
                ->findWithTranslation((int) $tagId, $translation);

            /*
             * If translation does not exist, we created it.
             */
            if ($tag === null) {
                $baseTag = $this->getService('em')
                    ->find('RZ\Renzo\Core\Entities\Tag', (int) $tagId);

                $this->getService('em')->refresh($baseTag);

                if ($baseTag !== null) {

                    $baseTranslation = $baseTag->getTranslatedTags()->first();

                    $translatedTag = new TagTranslation($baseTag, $translation);

                    if (false !== $baseTranslation) {
                        $translatedTag->setName($baseTranslation->getName());
                    } else {
                        $translatedTag->setName('tag_'.$baseTag->getId());
                    }
                    $this->getService('em')->persist($translatedTag);
                    $this->getService('em')->flush();

                    $tag = $this->getService('em')
                        ->getRepository('RZ\Renzo\Core\Entities\Tag')
                        ->findWithTranslation((int) $tagId, $translation);
                    $this->getService('em')->refresh($tag);
                } else {
                    return $this->throw404();
                }
            }

            $this->assignation['tag'] = $tag;
            $this->assignation['translation'] = $translation;
            $this->assignation['available_translations'] = $this->getService('em')
                ->getRepository('RZ\Renzo\Core\Entities\Translation')
                ->findAllAvailable();

            $form = $this->buildEditForm($tag);

            $form->handleRequest();

            if ($form->isValid()) {
                $this->editTag($form->getData(), $tag);

                $msg = $this->getTranslator()->trans('tag.updated', array('%name%'=>$tag->getTranslatedTags()->first()->getName()));
                $request->getSession()->getFlashBag()->add('confirm', $msg);
                $this->getService('logger')->info($msg);
                /*
                 * Force redirect to avoid resending form when refreshing page
                 */
                $response = new RedirectResponse(
                    $this->getService('urlGenerator')->generate(
                        'tagsEditTranslatedPage',
                        array('tagId' => $tag->getId(), 'translationId' => $translation->getId())
                    )
                );
                $response->prepare($request);

                return $response->send();
            }

            $this->assignation['form'] = $form->createView();

            return new Response(
                $this->getTwig()->render('tags/edit.html.twig', $this->assignation),
                Response::HTTP_OK,
                array('content-type' => 'text/html')
            );
        } else {
            return $this->throw404();
        }
    }

    /**
     * Return an creation form for requested tag.
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function addAction(Request $request)
    {
        $this->validateAccessForRole('ROLE_ACCESS_TAGS');

        $tag = new Tag();

        $translation = $this->getService('em')
                ->getRepository('RZ\Renzo\Core\Entities\Translation')
                ->findDefault();

        if ($tag !== null &&
            $translation !== null) {

            $this->assignation['tag'] = $tag;
            $form = $this->buildAddForm($tag);

            $form->handleRequest();

            if ($form->isValid()) {
                $this->addTag($form->getData(), $tag, $translation);

                $msg = $this->getTranslator()->trans('tag.created', array('%name%'=>$tag->getTranslatedTags()->first()->getName()));
                $request->getSession()->getFlashBag()->add('confirm', $msg);
                $this->getService('logger')->info($msg);
                /*
                 * Force redirect to avoid resending form when refreshing page
                 */
                $response = new RedirectResponse(
                    $this->getService('urlGenerator')->generate('tagsHomePage')
                );
                $response->prepare($request);

                return $response->send();
            }

            $this->assignation['form'] = $form->createView();

            return new Response(
                $this->getTwig()->render('tags/add.html.twig', $this->assignation),
                Response::HTTP_OK,
                array('content-type' => 'text/html')
            );
        } else {
            return $this->throw404();
        }
    }

    /**
     * Return a deletion form for requested tag.
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param int                                      $tagId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction(Request $request, $tagId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_TAGS_DELETE');

        $tag = $this->getService('em')
            ->find('RZ\Renzo\Core\Entities\Tag', (int) $tagId);

        if ($tag !== null) {
            $this->assignation['tag'] = $tag;

            $form = $this->buildDeleteForm($tag);
            $form->handleRequest();

            if ($form->isValid() &&
                $form->getData()['tagId'] == $tag->getId()) {

                $this->deleteTag($form->getData(), $tag);
                $msg = $this->getTranslator()->trans('tag.deleted', array('%name%'=>$tag->getTranslatedTags()->first()->getName()));
                $request->getSession()->getFlashBag()->add('confirm', $msg);
                $this->getService('logger')->info($msg);

                /*
                 * Force redirect to avoid resending form when refreshing page
                 */
                $response = new RedirectResponse(
                    $this->getService('urlGenerator')->generate('tagsHomePage')
                );
                $response->prepare($request);

                return $response->send();
            }

            $this->assignation['form'] = $form->createView();

            return new Response(
                $this->getTwig()->render('tags/delete.html.twig', $this->assignation),
                Response::HTTP_OK,
                array('content-type' => 'text/html')
            );
        } else {
            return $this->throw404();
        }
    }

    /**
     * Handle tag creation pages.
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param int                                      $tagId
     * @param int                                      $translationId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function addChildAction(Request $request, $tagId, $translationId = null)
    {
        $this->validateAccessForRole('ROLE_ACCESS_TAGS');

        $translation = $this->getService('em')
                ->getRepository('RZ\Renzo\Core\Entities\Translation')
                ->findDefault();

        if ($translationId != null) {
            $translation = $this->getService('em')
                ->find('RZ\Renzo\Core\Entities\Translation', (int) $translationId);
        }
        $parentTag = $this->getService('em')
            ->find('RZ\Renzo\Core\Entities\Tag', (int) $tagId);

        if ($translation !== null &&
            $parentTag !== null) {

            $form = $this->buildAddChildForm($parentTag);
            $form->handleRequest();

            if ($form->isValid()) {

                try {
                    $tag = $this->addChildTag($form->getData(), $parentTag, $translation);

                    $msg = $this->getTranslator()->trans('tag.created', array('%name%'=>$tag->getId()));
                    $request->getSession()->getFlashBag()->add('confirm', $msg);
                    $this->getService('logger')->info($msg);

                    $response = new RedirectResponse(
                        $this->getService('urlGenerator')->generate(
                            'tagsEditPage',
                            array('tagId' => $tag->getId())
                        )
                    );
                    $response->prepare($request);

                    return $response->send();
                } catch (EntityAlreadyExistsException $e) {

                    $request->getSession()->getFlashBag()->add('error', $e->getMessage());
                    $this->getService('logger')->warning($e->getMessage());

                    $response = new RedirectResponse(
                        $this->getService('urlGenerator')->generate(
                            'tagsAddChildPage',
                            array('tagId' => $tagId, 'translationId' => $translationId)
                        )
                    );
                    $response->prepare($request);

                    return $response->send();
                }
            }

            $this->assignation['translation'] = $translation;
            $this->assignation['form'] = $form->createView();
            $this->assignation['parentTag'] = $parentTag;

            return new Response(
                $this->getTwig()->render('tags/add.html.twig', $this->assignation),
                Response::HTTP_OK,
                array('content-type' => 'text/html')
            );
        } else {
            return $this->throw404();
        }
    }

    /**
     * Handle tag nodes page.
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param int                                      $tagId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function editNodesAction(Request $request, $tagId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_TAGS');
        $tag = $this->getService('em')
                    ->find('RZ\Renzo\Core\Entities\Tag', (int) $tagId);

        if (null !== $tag) {

            $translation = $this->getService('em')
                    ->getRepository('RZ\Renzo\Core\Entities\Translation')
                    ->findDefault();

            $this->assignation['tag'] = $tag;

            /*
             * Manage get request to filter list
             */
            $listManager = new EntityListManager(
                $request,
                $this->getService('em'),
                'RZ\Renzo\Core\Entities\Node',
                array(
                    'tags' => $tag
                )
            );
            $listManager->handle();

            $this->assignation['filters'] = $listManager->getAssignation();
            $this->assignation['nodes'] = $listManager->getEntities();

            $this->assignation['translation'] = $translation;

            return new Response(
                $this->getTwig()->render('tags/nodes.html.twig', $this->assignation),
                Response::HTTP_OK,
                array('content-type' => 'text/html')
            );

        } else {

            return $this->throw404();
        }
    }

    /**
     * Handle tag tree page.
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param int                                      $tagId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function treeAction(Request $request, $tagId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_TAGS');

        $tag = $this->getService('em')
                    ->find('RZ\Renzo\Core\Entities\Tag', (int) $tagId);

        if (null !== $tag) {

        } else {

            return $this->throw404();
        }
    }

    /**
     * @param array                      $data
     * @param RZ\Renzo\Core\Entities\Tag $tag
     *
     * @throws EntityAlreadyExistsException
     */
    private function editTag($data, Tag $tag)
    {
        $translatedTag = $tag->getTranslatedTags()->first();

        if ($translatedTag->getName() != $data['name'] &&
            $this->checkExists($data['name'])) {
            throw new EntityAlreadyExistsException(
                $this->getTranslator()->trans(
                    'tag.no_update.already_exists',
                    array('%name%'=>$data['name'])
                ),
                1
            );
        }

        foreach ($data as $key => $value) {
            $setter = 'set'.ucwords($key);

            if ($key == 'name' || $key == 'description') {
                $translatedTag->$setter( $value );
            } else {
                $tag->$setter($value);
            }
        }

        $this->getService('em')->flush();
    }

    /**
     * @param array                              $data
     * @param RZ\Renzo\Core\Entities\Tag         $tag
     * @param RZ\Renzo\Core\Entities\Translation $translation
     *
     * @return RZ\Renzo\Core\Entities\Tag
     * @throws EntityAlreadyExistsException
     */
    private function addTag($data, Tag $tag, Translation $translation)
    {
        if ($this->checkExists($data['name'])) {
            throw new EntityAlreadyExistsException(
                $this->getTranslator()->trans(
                    'tag.no_creation.already_exists',
                    array('%name%'=>$data['name'])
                ),
                1
            );
        }

        $translatedTag = new TagTranslation($tag, $translation);

        foreach ($data as $key => $value) {
            $setter = 'set'.ucwords($key);

            if ($key == 'name' || $key == 'description') {
                $translatedTag->$setter( $value );
            } else {
                $tag->$setter( $value );
            }
        }
        $tag->getTranslatedTags()->add($translatedTag);

        $this->getService('em')->persist($translatedTag);
        $this->getService('em')->persist($tag);
        $this->getService('em')->flush();

        return $tag;
    }

    /**
     * Check if a tag already uses this name.
     *
     * @param string $name
     *
     * @return boolean
     */
    private function checkExists($name)
    {
        $ttag = $this->getService('em')
                    ->getRepository('RZ\Renzo\Core\Entities\TagTranslation')
                    ->findOneBy(array('name'=>$name));

        return $ttag !== null;
    }

    /**
     * @param array                              $data
     * @param RZ\Renzo\Core\Entities\Tag         $parentTag
     * @param RZ\Renzo\Core\Entities\Translation $translation
     *
     * @return RZ\Renzo\Core\Entities\Tag
     * @throws EntityAlreadyExistsException
     */
    private function addChildTag($data, Tag $parentTag, Translation $translation)
    {

        if ($parentTag->getId() != $data['parent_tagId']) {
            throw new \RuntimeException("Parent tag Ids do not match", 1);
        }
        if ($this->checkExists($data['name'])) {
            throw new EntityAlreadyExistsException(
                $this->getTranslator()->trans(
                    'tag.already_added',
                    array('%name%'=>$data['name'])
                ),
                1
            );
        }

        $tag = new Tag();
        $tag->setParent($parentTag);
        $translatedTag = new TagTranslation($tag, $translation);

        foreach ($data as $key => $value) {

            $setter = 'set'.ucwords($key);

            if ($key == 'name' || $key == 'description') {
                $translatedTag->$setter($value);
            } elseif ($key != 'parent_tagId') {
                $tag->$setter($value);
            }
        }
        $tag->getTranslatedTags()->add($translatedTag);

        $this->getService('em')->persist($translatedTag);
        $this->getService('em')->persist($tag);
        $this->getService('em')->flush();

        return $tag;
    }

    /**
     * @param array                      $data
     * @param RZ\Renzo\Core\Entities\Tag $tag
     */
    private function deleteTag($data, Tag $tag)
    {
        $this->getService('em')->remove($tag);
        $this->getService('em')->flush();
    }

    /**
     * @param RZ\Renzo\Core\Entities\Tag $tag
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildAddForm(Tag $tag)
    {
        $defaults = array(
            'visible' => $tag->isVisible()
        );

        $builder = $this->getService('formFactory')
                    ->createBuilder('form', $defaults)
                    ->add('name', 'text', array(
                        'constraints' => array(
                            new NotBlank()
                        )
                    ))
                    ->add('visible', 'checkbox', array('required' => false))
                    ->add('description', new \RZ\Renzo\CMS\Forms\MarkdownType(), array('required' => false));

        return $builder->getForm();
    }

    /**
     * @param RZ\Renzo\Core\Entities\Tag $tag
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildAddChildForm(Tag $tag)
    {
        $defaults = array(
            'visible' => $tag->isVisible()
        );

        $builder = $this->getService('formFactory')
                    ->createBuilder('form', $defaults)
                    ->add('name', 'text', array(
                        'constraints' => array(
                            new NotBlank()
                        )
                    ))
                    ->add('visible', 'checkbox', array('required' => false))
                    ->add('description', new \RZ\Renzo\CMS\Forms\MarkdownType(), array('required' => false))
                    ->add('parent_tagId', 'hidden', array(
                        "data" => $tag->getId(),
                        'required' => true
                    ));

        return $builder->getForm();
    }

    /**
     * @param RZ\Renzo\Core\Entities\Tag $tag
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildEditForm(Tag $tag)
    {
        $translation = $tag->getTranslatedTags()->first();

        $defaults = array(
            'visible' => $tag->isVisible(),
            'name' => $translation->getName(),
            'description' => $translation->getDescription(),
        );

        $builder = $this->getService('formFactory')
            ->createBuilder('form', $defaults)
            ->add('name', 'text', array(
                'constraints' => array(
                    new NotBlank()
                )
            ))
            ->add('visible', 'checkbox', array('required' => false))
            ->add('description', new \RZ\Renzo\CMS\Forms\MarkdownType(), array('required' => false));

        return $builder->getForm();
    }

    /**
     * @param RZ\Renzo\Core\Entities\Tag $tag
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildDeleteForm(Tag $tag)
    {
        $builder = $this->getService('formFactory')
            ->createBuilder('form')
            ->add('tagId', 'hidden', array(
                'data' => $tag->getId(),
                'constraints' => array(
                    new NotBlank()
                )
            ));

        return $builder->getForm();
    }

    /**
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public static function getTags()
    {
        return $this->getService('em')
            ->getRepository('RZ\Renzo\Core\Entities\Tag')
            ->findAll();
    }
}
