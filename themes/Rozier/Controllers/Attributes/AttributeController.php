<?php
/**
 * Copyright © 2019, Ambroise Maupate and Julien Blanchet
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
 * Except as contained in this notice, the name of the roadiz shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from Ambroise Maupate and Julien Blanchet.
 *
 * @file AttributeController.php
 * @author Ambroise Maupate
 *
 */
declare(strict_types=1);

namespace Themes\Rozier\Controllers\Attributes;

use RZ\Roadiz\Attribute\Form\AttributeType;
use RZ\Roadiz\Core\Entities\Attribute;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Themes\Rozier\RozierApp;

class AttributeController extends RozierApp
{
    /**
     * @param Request $request
     *
     * @return Response
     */
    public function indexAction(Request $request)
    {
        $this->validateAccessForRole('ROLE_ACCESS_ATTRIBUTES');

        $listManager = $this->createEntityListManager(
            Attribute::class,
            [],
            ['code' => 'ASC']
        );
        $listManager->handle();

        $this->assignation['filters'] = $listManager->getAssignation();
        $this->assignation['items'] = $listManager->getEntities();

        return $this->render('attributes/list.html.twig', $this->assignation);
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function addAction(Request $request)
    {
        $this->validateAccessForRole('ROLE_ACCESS_ATTRIBUTES');

        $item = new Attribute();
        $item->setCode('new_attribute');

        $form = $this->createForm(AttributeType::class, $item, [
            'entityManager' => $this->get('em'),
        ]);
        $form->handleRequest($request);

        if ($form->isValid()) {
            try {
                $this->get('em')->persist($item);
                $this->get('em')->flush();

                $msg = $this->getTranslator()->trans('attribute.%name%.created', ['%name%' => $item->getCode()]);
                $this->publishConfirmMessage($request, $msg);
            } catch (\RuntimeException $e) {
                $this->publishErrorMessage($request, $e->getMessage());
            }

            /*
             * Force redirect to avoid resending form when refreshing page
             */
            return $this->redirect($this->generateUrl('attributesEditPage', ['id' => $item->getId()]));
        }

        $this->assignation['form'] = $form->createView();

        return $this->render('attributes/add.html.twig', $this->assignation);
    }

    /**
     * @param Request $request
     * @param int     $id
     *
     * @return Response
     */
    public function editAction(Request $request, $id)
    {
        $this->validateAccessForRole('ROLE_ACCESS_ATTRIBUTES');

        /** @var Attribute $item */
        $item = $this->get('em')->find(Attribute::class, (int) $id);

        if ($item === null) {
            throw $this->createNotFoundException('Attribute does not exist.');
        }

        $form = $this->createForm(AttributeType::class, $item, [
            'entityManager' => $this->get('em')
        ]);
        $form->handleRequest($request);
        if ($form->isValid()) {
            try {
                $this->get('em')->flush();
                $msg = $this->getTranslator()->trans(
                    'attribute.%name%.updated',
                    ['%name%' => $item->getCode()]
                );
                $this->publishConfirmMessage($request, $msg);
            } catch (\RuntimeException $e) {
                $this->publishErrorMessage($request, $e->getMessage());
            }
            return $this->redirect($this->generateUrl('attributesEditPage', ['id' => $item->getId()]));
        }

        $this->assignation['item'] = $item;
        $this->assignation['form'] = $form->createView();

        return $this->render('attributes/edit.html.twig', $this->assignation);
    }

    /**
     * @param Request $request
     * @param int $id
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function deleteAction(Request $request, $id)
    {
        $this->validateAccessForRole('ROLE_ACCESS_ATTRIBUTES_DELETE');

        /** @var Attribute $item */
        $item = $this->get('em')->find(Attribute::class, (int) $id);

        if ($item === null) {
            throw $this->createNotFoundException('Attribute does not exist.');
        }

        $form = $this->createForm();
        $form->handleRequest($request);

        if ($form->isValid()) {
            try {
                $this->get('em')->remove($item);
                $this->get('em')->flush();

                $msg = $this->getTranslator()->trans(
                    'attribute.%name%.deleted',
                    ['%name%' => $item->getCode()]
                );
                $this->publishConfirmMessage($request, $msg);
            } catch (\RuntimeException $e) {
                $this->publishErrorMessage($request, $e->getMessage());
            }

            return $this->redirect($this->generateUrl('attributesHomePage'));
        }

        $this->assignation['form'] = $form->createView();
        $this->assignation['item'] = $item;

        return $this->render('attributes/delete.html.twig', $this->assignation);
    }
}
