<?php
/**
 * Copyright REZO ZERO 2014
 *
 *
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
*
*/
class TagsController extends RozierApp {

    const ITEM_PER_PAGE = 5;

    /**
     * List every tags.
     * @param  Symfony\Component\HttpFoundation\Request  $request
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request) {
        /*
         * Manage get request to filter list
         */
        $listManager = new EntityListManager(
            $request,
            Kernel::getInstance()->em(),
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
     * @param  Symfony\Component\HttpFoundation\Request $request
     * @param  integer $tag_id
     * @param  integer $translation_id
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function editTranslatedAction(Request $request, $tag_id, $translation_id)
    {
        $translation = Kernel::getInstance()->em()
                ->find('RZ\Renzo\Core\Entities\Translation', (int)$translation_id);

        if ($translation !== null) {

            $tag = Kernel::getInstance()->em()
                ->getRepository('RZ\Renzo\Core\Entities\Tag')
                ->findWithTranslation((int)$tag_id, $translation);

            /*
             * If translation does not exist, we created it.
             */
            if ($tag === null) {

                $baseTag = Kernel::getInstance()->em()
                    ->find('RZ\Renzo\Core\Entities\Tag',(int)$tag_id);
                Kernel::getInstance()->em()->refresh($baseTag);

                if ($baseTag !== null) {

                    $translatedTag = new TagTranslation( $baseTag, $translation );
                    $translatedTag->setName($baseTag->getTranslatedTags()->first()->getName());
                    Kernel::getInstance()->em()->persist($translatedTag);
                    Kernel::getInstance()->em()->flush();

                    $tag = Kernel::getInstance()->em()
                        ->getRepository('RZ\Renzo\Core\Entities\Tag')
                        ->findWithTranslation((int)$tag_id, $translation);
                    Kernel::getInstance()->em()->refresh($tag);
                }
                else {
                    return $this->throw404();
                }
            }

            $this->assignation['tag'] = $tag;
            $this->assignation['translation'] = $translation;
            $this->assignation['available_translations'] = Kernel::getInstance()->em()
                ->getRepository('RZ\Renzo\Core\Entities\Translation')
                ->findAllAvailable();

            $form = $this->buildEditForm( $tag );

            $form->handleRequest();

            if ($form->isValid()) {
                $this->editTag($form->getData(), $tag);

                $msg = $this->getTranslator()->trans('tag.updated', array('%name%'=>$tag->getTranslatedTags()->first()->getName()));
                $request->getSession()->getFlashBag()->add('confirm', $msg);
                $this->getLogger()->info($msg);
                /*
                 * Force redirect to avoid resending form when refreshing page
                 */
                $response = new RedirectResponse(
                    Kernel::getInstance()->getUrlGenerator()->generate(
                        'tagsEditTranslatedPage',
                        array('tag_id' => $tag->getId(), 'translation_id' => $translation->getId())
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
        }
        else {
            return $this->throw404();
        }
    }

    /**
     * Return an creation form for requested tag.
     * @param  Symfony\Component\HttpFoundation\Request  $request
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function addAction(Request $request) {
        $tag = new Tag();

        $translation = Kernel::getInstance()->em()
                ->getRepository('RZ\Renzo\Core\Entities\Translation')
                ->findDefault();

        if ($tag !== null &&
            $translation !== null) {

            $this->assignation['tag'] = $tag;
            $form = $this->buildAddForm($tag );

            $form->handleRequest();

            if ($form->isValid()) {
                $this->addTag($form->getData(), $tag, $translation);

                $msg = $this->getTranslator()->trans('tag.created', array('%name%'=>$tag->getTranslatedTags()->first()->getName()));
                $request->getSession()->getFlashBag()->add('confirm', $msg);
                $this->getLogger()->info($msg);
                /*
                 * Force redirect to avoid resending form when refreshing page
                 */
                $response = new RedirectResponse(
                    Kernel::getInstance()->getUrlGenerator()->generate('tagsHomePage')
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
        }
        else {
            return $this->throw404();
        }
    }

    /**
     * Return a deletion form for requested tag.
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param int  $tag_id
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction(Request $request, $tag_id) {
        $tag = Kernel::getInstance()->em()
            ->find('RZ\Renzo\Core\Entities\Tag', (int)$tag_id);

        if ($tag !== null) {
            $this->assignation['tag'] = $tag;

            $form = $this->buildDeleteForm( $tag );
            $form->handleRequest();

            if ($form->isValid() &&
                $form->getData()['tag_id'] == $tag->getId() ) {

                $this->deleteTag($form->getData(), $tag);
                $msg = $this->getTranslator()->trans('tag.deleted', array('%name%'=>$tag->getTranslatedTags()->first()->getName()));
                $request->getSession()->getFlashBag()->add('confirm', $msg);
                $this->getLogger()->info($msg);

                /*
                 * Force redirect to avoid resending form when refreshing page
                 */
                $response = new RedirectResponse(
                    Kernel::getInstance()->getUrlGenerator()->generate('tagsHomePage')
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
        }
        else {
            return $this->throw404();
        }
    }

    /**
     * Handle tag creation pages.
     * @param Symfony\Component\HttpFoundation\Request  $request
     * @param int  $tag_id
     * @param int  $translation_id
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function addChildAction(Request $request, $tag_id, $translation_id = null) {
        $translation = Kernel::getInstance()->em()
                ->getRepository('RZ\Renzo\Core\Entities\Translation')
                ->findDefault();

        if ($translation_id != null) {
            $translation = Kernel::getInstance()->em()
                ->find('RZ\Renzo\Core\Entities\Translation', (int)$translation_id);
        }
        $parentTag = Kernel::getInstance()->em()
            ->find('RZ\Renzo\Core\Entities\Tag', (int)$tag_id);

        if ($translation !== null &&
            $parentTag !== null) {

            $form = $this->buildAddChildForm( $parentTag );
            $form->handleRequest();

            if ($form->isValid()) {

                try {
                    $tag = $this->addChildTag($form->getData(), $parentTag, $translation);

                    $msg = $this->getTranslator()->trans('tag.created', array('%name%'=>$tag->getId()));
                    $request->getSession()->getFlashBag()->add('confirm', $msg);
                    $this->getLogger()->info($msg);

                    $response = new RedirectResponse(
                        Kernel::getInstance()->getUrlGenerator()->generate(
                            'tagsEditPage',
                            array('tag_id' => $tag->getId())
                        )
                    );
                    $response->prepare($request);
                    return $response->send();
                }
                catch(EntityAlreadyExistsException $e) {

                    $request->getSession()->getFlashBag()->add('error', $e->getMessage());
                    $this->getLogger()->warning($e->getMessage());

                    $response = new RedirectResponse(
                        Kernel::getInstance()->getUrlGenerator()->generate(
                            'tagsAddChildPage',
                            array('tag_id' => $tag_id, 'translation_id' => $translation_id)
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
        }else {
            return $this->throw404();
        }
    }

    /**
     * @param  array  $data
     * @param  RZ\Renzo\Core\Entities\Tag  $tag
     * @throws EntityAlreadyExistsException
     * @return void
     */
    private function editTag($data, Tag $tag) {
        $translatedTag = $tag->getTranslatedTags()->first();

        if ($translatedTag->getName() != $data['name'] &&
            $this->checkExists($data['name'])) {
            throw new EntityAlreadyExistsException($this->getTranslator()->trans('tag.no_update.already_exists', array('%name%'=>$data['name'])), 1);
        }

        foreach ($data as $key => $value) {
            $setter = 'set'.ucwords($key);

            if ($key == 'name' || $key == 'description') {
                $translatedTag->$setter( $value );
            }
            else {
                $tag->$setter( $value );
            }
        }

        Kernel::getInstance()->em()->flush();
    }

    /**
     * @param array  $data
     * @param RZ\Renzo\Core\Entities\Tag $tag
     * @param RZ\Renzo\Core\Entities\Translation  $translation
     * @throws EntityAlreadyExistsException
     */
    private function addTag($data, Tag $tag, Translation $translation) {

        if ($this->checkExists($data['name'])) {
            throw new EntityAlreadyExistsException($this->getTranslator()->trans('tag.no_creation.already_exists', array('%name%'=>$data['name'])), 1);
        }

        $translatedTag = new TagTranslation( $tag, $translation );

        foreach ($data as $key => $value) {

            $setter = 'set'.ucwords($key);

            if ($key == 'name' || $key == 'description') {
                $translatedTag->$setter( $value );
            }
            else {
                $tag->$setter( $value );
            }
        }
        $tag->getTranslatedTags()->add($translatedTag);

        Kernel::getInstance()->em()->persist($translatedTag);
        Kernel::getInstance()->em()->persist($tag);
        Kernel::getInstance()->em()->flush();

        return $tag;
    }

    /**
     * Check if a tag already uses this name
     *
     * @param  string $name
     * @return boolean
     */
    private function checkExists( $name )
    {
        $ttag  = Kernel::getInstance()->em()
                    ->getRepository('RZ\Renzo\Core\Entities\TagTranslation')
                    ->findOneBy(array('name'=>$name));

        return $ttag !== null;
    }

    /**
     * @param array  $data
     * @param RZ\Renzo\Core\Entities\Tag $tag
     * @param RZ\Renzo\Core\Entities\Translation  $translation
     * @throws EntityAlreadyExistsException
     */
    private function addChildTag($data, Tag $parentTag, Translation $translation) {

        if ($parentTag->getId() != $data['parent_tag_id']) {
            throw new \RuntimeException("Parent tag Ids do not match", 1);
        }
        if ($this->checkExists($data['name'])) {
            throw new EntityAlreadyExistsException($this->getTranslator()->trans('tag.already_added', array('%name%'=>$data['name'])), 1);
        }

        $tag = new Tag();
        $tag->setParent($parentTag);
        $translatedTag = new TagTranslation( $tag, $translation );

        foreach ($data as $key => $value) {

            $setter = 'set'.ucwords($key);

            if ($key == 'name' || $key == 'description') {
                $translatedTag->$setter( $value );
            }
            elseif($key != 'parent_tag_id') {
                $tag->$setter( $value );
            }
        }
        $tag->getTranslatedTags()->add($translatedTag);

        Kernel::getInstance()->em()->persist($translatedTag);
        Kernel::getInstance()->em()->persist($tag);
        Kernel::getInstance()->em()->flush();

        return $tag;
    }

    /**
     * @param  array  $data
     * @param  RZ\Renzo\Core\Entities\Tag  $tag
     * @return
     */
    private function deleteTag( $data, Tag $tag )
    {
        Kernel::getInstance()->em()->remove($tag);
        Kernel::getInstance()->em()->flush();
    }

    /**
     * @param RZ\Renzo\Core\Entities\Tag  $tag
     * @return \Symfony\Component\Form\Form
     */
    private function buildAddForm(Tag $tag) {
        $defaults = array(
            'visible' => $tag->isVisible()
        );

        $builder = $this->getFormFactory()
                    ->createBuilder('form', $defaults)
                    ->add('name', 'text', array(
                        'constraints' => array(
                            new NotBlank()
                        )
                    ))
                    ->add('visible', 'checkbox', array('required' => false))
                    ->add('description', new \RZ\Renzo\CMS\Forms\MarkdownType() , array('required' => false))
        ;
        return $builder->getForm();
    }

    /**
     * @param RZ\Renzo\Core\Entities\Tag  $tag
     * @return \Symfony\Component\Form\Form
     */
    private function buildAddChildForm(Tag $tag) {
        $defaults = array(
            'visible' => $tag->isVisible()
        );

        $builder = $this->getFormFactory()
                    ->createBuilder('form', $defaults)
                    ->add('name', 'text', array(
                        'constraints' => array(
                            new NotBlank()
                        )
                    ))
                    ->add('visible', 'checkbox', array('required' => false))
                    ->add('description', new \RZ\Renzo\CMS\Forms\MarkdownType() , array('required' => false))
                    ->add('parent_tag_id', 'hidden', array(
                        "data" => $tag->getId(),
                        'required' => true
                    ))
        ;
        return $builder->getForm();
    }

    /**
     * @param RZ\Renzo\Core\Entities\Tag  $tag
     * @return \Symfony\Component\Form\Form
     */
    private function buildEditForm(Tag $tag) {
        $translation = $tag->getTranslatedTags()->first();

        $defaults = array(
            'visible' => $tag->isVisible(),
            'name' => $translation->getName(),
            'description' => $translation->getDescription(),
        );

        $builder = $this->getFormFactory()
            ->createBuilder('form', $defaults)
            ->add('name', 'text', array(
                'constraints' => array(
                    new NotBlank()
                )
            ))
            ->add('visible', 'checkbox', array('required' => false))
            ->add('description', new \RZ\Renzo\CMS\Forms\MarkdownType() , array('required' => false))
        ;

        return $builder->getForm();
    }

    /**
     * @param  RZ\Renzo\Core\Entities\Tag  $tag
     * @return \Symfony\Component\Form\Form
     */
    private function buildDeleteForm(Tag $tag) {
        $builder = $this->getFormFactory()
            ->createBuilder('form')
            ->add('tag_id', 'hidden', array(
                'data' => $tag->getId(),
                'constraints' => array(
                    new NotBlank()
                )
            ))
        ;
        return $builder->getForm();
    }

    /**
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public static function getTags() {
        return Kernel::getInstance()->em()
            ->getRepository('RZ\Renzo\Core\Entities\Tag')
            ->findAll();
    }
}