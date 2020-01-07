<?php
/*
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
 * @file GroupsUtilsController.php
 * @author Thomas Aufresne
 */

namespace Themes\Rozier\Controllers;

use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\Serializer;
use RZ\Roadiz\CMS\Importers\SettingsImporter;
use RZ\Roadiz\Core\Entities\Setting;
use RZ\Roadiz\Core\Entities\SettingGroup;
use RZ\Roadiz\Utils\StringHandler;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Themes\Rozier\RozierApp;

/**
 * {@inheritdoc}
 */
class SettingsUtilsController extends RozierApp
{
    /**
     * Export all settings in a Json file (.rzt).
     *
     * @param Request $request
     * @param int|null    $settingGroupId
     *
     * @return Response
     */
    public function exportAllAction(Request $request, $settingGroupId = null)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_SETTINGS');

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $this->get('em');
        if (null !== $settingGroupId) {
            /** @var SettingGroup|null $group */
            $group = $entityManager->find(SettingGroup::class, $settingGroupId);
            if (null === $group) {
                throw $this->createNotFoundException();
            }
            $fileName = 'settings-' . strtolower(StringHandler::cleanForFilename($group->getName())) . '-' . date("YmdHis") . '.json';
            $settings = $entityManager
                ->getRepository(Setting::class)
                ->findBySettingGroup($group);
        } else {
            $fileName = 'settings-' . date("YmdHis") . '.json';
            $settings = $entityManager
                ->getRepository(Setting::class)
                ->findAll();
        }

        /** @var Serializer $serializer */
        $serializer = $this->get('serializer');

        return new JsonResponse(
            $serializer->serialize(
                $settings,
                'json',
                SerializationContext::create()->setGroups(['setting'])
            ),
            JsonResponse::HTTP_OK,
            [
                'Content-Disposition' => sprintf('attachment; filename="%s"', $fileName),
            ],
            true
        );
    }

    /**
     * Import a Json file (.rzt) containing setting and setting group.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function importJsonFileAction(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_SETTINGS');

        $form = $this->buildImportJsonFileForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() &&
            $form->isValid() &&
            !empty($form['setting_file'])) {
            $file = $form['setting_file']->getData();

            if ($form->isSubmitted() && $file->isValid()) {
                $serializedData = file_get_contents($file->getPathname());

                if (null !== json_decode($serializedData)) {
                    if ($this->get(SettingsImporter::class)->import($serializedData)) {
                        $msg = $this->getTranslator()->trans('setting.imported');
                        $this->publishConfirmMessage($request, $msg);
                        $this->get('em')->flush();

                        // redirect even if its null
                        return $this->redirect($this->generateUrl(
                            'settingsHomePage'
                        ));
                    }
                }
                $form->addError(new FormError($this->getTranslator()->trans('file.format.not_valid')));
            } else {
                $form->addError(new FormError($this->getTranslator()->trans('file.not_uploaded')));
            }
        }

        $this->assignation['form'] = $form->createView();

        return $this->render('settings/import.html.twig', $this->assignation);
    }

    /**
     * @return \Symfony\Component\Form\Form
     */
    private function buildImportJsonFileForm()
    {
        $builder = $this->createFormBuilder()
                        ->add('setting_file', FileType::class, [
                            'label' => 'settingFile',
                        ]);

        return $builder->getForm();
    }
}
