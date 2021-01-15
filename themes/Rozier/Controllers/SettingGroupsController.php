<?php
declare(strict_types=1);

namespace Themes\Rozier\Controllers;

use RZ\Roadiz\Core\Entities\SettingGroup;
use RZ\Roadiz\Core\Exceptions\EntityAlreadyExistsException;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Themes\Rozier\RozierApp;

/*
 * TODO: Refactor --> AbstractAdmin
 */
class SettingGroupsController extends RozierApp
{
    /**
     * List every settingGroups.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_SETTINGS');
        /*
         * Manage get request to filter list
         */
        $listManager = $this->createEntityListManager(
            SettingGroup::class,
            [],
            ['name' => 'ASC']
        );
        $listManager->setDisplayingNotPublishedNodes(true);
        $listManager->handle();

        $this->assignation['filters'] = $listManager->getAssignation();
        $this->assignation['settingGroups'] = $listManager->getEntities();

        return $this->render('settingGroups/list.html.twig', $this->assignation);
    }

    /**
     * Return an edition form for requested settingGroup.
     *
     * @param Request $request
     * @param int     $settingGroupId
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function editAction(Request $request, int $settingGroupId)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_SETTINGS');
        /** @var SettingGroup $settingGroup */
        $settingGroup = $this->get('em')->find(SettingGroup::class, $settingGroupId);

        if ($settingGroup !== null) {
            $this->assignation['settingGroup'] = $settingGroup;

            $form = $this->buildEditForm($settingGroup);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                try {
                    $this->editSettingGroup($form->getData(), $settingGroup);
                    $msg = $this->getTranslator()->trans(
                        'settingGroup.%name%.updated',
                        ['%name%' => $settingGroup->getName()]
                    );
                    $this->publishConfirmMessage($request, $msg);
                } catch (EntityAlreadyExistsException $e) {
                    $this->publishErrorMessage($request, $e->getMessage());
                }
                /*
                 * Force redirect to avoid resending form when refreshing page
                 */
                return $this->redirect($this->generateUrl(
                    'settingGroupsEditPage',
                    ['settingGroupId' => $settingGroup->getId()]
                ));
            }

            $this->assignation['form'] = $form->createView();

            return $this->render('settingGroups/edit.html.twig', $this->assignation);
        }

        throw new ResourceNotFoundException();
    }

    /**
     * Return an creation form for requested settingGroup.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function addAction(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_SETTINGS');

        $settingGroup = new SettingGroup();

        if (null !== $settingGroup) {
            $this->assignation['settingGroup'] = $settingGroup;

            $form = $this->buildAddForm($settingGroup);

            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                try {
                    $this->addSettingGroup($form->getData(), $settingGroup);
                    $msg = $this->getTranslator()->trans(
                        'settingGroup.%name%.created',
                        ['%name%' => $settingGroup->getName()]
                    );
                    $this->publishConfirmMessage($request, $msg);
                } catch (EntityAlreadyExistsException $e) {
                    $this->publishErrorMessage($request, $e->getMessage());
                }

                /*
                 * Force redirect to avoid resending form when refreshing page
                 */
                return $this->redirect($this->generateUrl('settingGroupsHomePage'));
            }

            $this->assignation['form'] = $form->createView();

            return $this->render('settingGroups/add.html.twig', $this->assignation);
        }

        throw new ResourceNotFoundException();
    }

    /**
     * Return an deletion form for requested settingGroup.
     *
     * @param Request $request
     * @param int     $settingGroupId
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction(Request $request, int $settingGroupId)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_SETTINGS');
        /** @var SettingGroup|null $settingGroup */
        $settingGroup = $this->get('em')->find(SettingGroup::class, (int) $settingGroupId);

        if (null !== $settingGroup) {
            $this->assignation['settingGroup'] = $settingGroup;

            $form = $this->buildDeleteForm($settingGroup);
            $form->handleRequest($request);

            if ($form->isSubmitted() &&
                $form->isValid() &&
                $form->getData()['settingGroupId'] == $settingGroup->getId()) {
                $this->deleteSettingGroup($form->getData(), $settingGroup);

                $msg = $this->getTranslator()->trans(
                    'settingGroup.%name%.deleted',
                    ['%name%' => $settingGroup->getName()]
                );
                $this->publishConfirmMessage($request, $msg);

                /*
                 * Force redirect to avoid resending form when refreshing page
                 */
                return $this->redirect($this->generateUrl('settingGroupsHomePage'));
            }

            $this->assignation['form'] = $form->createView();

            return $this->render('settingGroups/delete.html.twig', $this->assignation);
        }

        throw new ResourceNotFoundException();
    }

    /**
     * @param array        $data
     * @param SettingGroup $settingGroup
     *
     * @return bool
     * @throws EntityAlreadyExistsException
     */
    private function editSettingGroup($data, SettingGroup $settingGroup)
    {
        if (isset($data['name']) &&
            $data['name'] != $settingGroup->getName() &&
            $this->get('em')
            ->getRepository(SettingGroup::class)
            ->exists($data['name'])) {
            throw new EntityAlreadyExistsException($this->getTranslator()->trans(
                'settingGroup.%name%.no_update.already_exists',
                ['%name%' => $settingGroup->getName()]
            ), 1);
        }
        try {
            foreach ($data as $key => $value) {
                $setter = 'set' . ucwords($key);
                $settingGroup->$setter($value);
            }

            $this->get('em')->flush();

            return true;
        } catch (\Exception $e) {
            throw new EntityAlreadyExistsException($this->getTranslator()->trans(
                'settingGroup.%name%.no_update.already_exists',
                ['%name%' => $settingGroup->getName()]
            ), 1);
        }
    }

    /**
     * @param array        $data
     * @param SettingGroup $settingGroup
     *
     * @return bool
     * @throws EntityAlreadyExistsException
     */
    private function addSettingGroup($data, SettingGroup $settingGroup)
    {
        if ($this->get('em')
            ->getRepository(SettingGroup::class)
            ->exists($data['name'])) {
            throw new EntityAlreadyExistsException($this->getTranslator()->trans(
                'settingGroup.%name%.no_creation.already_exists',
                ['%name%' => $settingGroup->getName()]
            ), 1);
        }

        try {
            foreach ($data as $key => $value) {
                $setter = 'set' . ucwords($key);
                $settingGroup->$setter($value);
            }

            $this->get('em')->persist($settingGroup);
            $this->get('em')->flush();

            return true;
        } catch (\Exception $e) {
            throw new EntityAlreadyExistsException($this->getTranslator()->trans(
                'settingGroup.%name%.no_creation.already_exists',
                ['%name%' => $settingGroup->getName()]
            ), 1);
        }
    }

    /**
     * @param array        $data
     * @param SettingGroup $settingGroup
     *
     * @return boolean
     */
    private function deleteSettingGroup($data, SettingGroup $settingGroup)
    {
        $this->get('em')->remove($settingGroup);
        $this->get('em')->flush();

        return true;
    }

    /**
     * @param SettingGroup $settingGroup
     *
     * @return FormInterface
     */
    private function buildAddForm(SettingGroup $settingGroup)
    {
        $defaults = [
            'name' => $settingGroup->getName(),
            'inMenu' => $settingGroup->isInMenu(),
        ];
        $builder = $this->createFormBuilder($defaults)
                        ->add('name', TextType::class, [
                            'label' => 'name',
                            'constraints' => [
                                new NotNull(),
                                new NotBlank(),
                            ],
                        ])
                        ->add('inMenu', CheckboxType::class, [
                            'label' => 'settingGroup.in.menu',
                            'required' => false,
                        ])
        ;

        return $builder->getForm();
    }

    /**
     * @param SettingGroup $settingGroup
     *
     * @return FormInterface
     */
    private function buildEditForm(SettingGroup $settingGroup)
    {
        $defaults = [
            'name' => $settingGroup->getName(),
            'inMenu' => $settingGroup->isInMenu(),
        ];

        $builder = $this->createFormBuilder($defaults)
                        ->add(
                            'name',
                            TextType::class,
                            [
                                'label' => 'name',
                                'constraints' => [
                                    new NotNull(),
                                    new NotBlank()
                                ],
                            ]
                        )
                        ->add(
                            'inMenu',
                            CheckboxType::class,
                            [
                                'label' => 'settingGroup.in.menu',
                                'required' => false,
                            ]
                        );

        return $builder->getForm();
    }

    /**
     * @param SettingGroup $settingGroup
     *
     * @return FormInterface
     */
    private function buildDeleteForm(SettingGroup $settingGroup)
    {
        $builder = $this->createFormBuilder()
                        ->add('settingGroupId', HiddenType::class, [
                            'data' => $settingGroup->getId(),
                            'constraints' => [
                                new NotNull(),
                                new NotBlank(),
                            ],
                        ]);

        return $builder->getForm();
    }
}
