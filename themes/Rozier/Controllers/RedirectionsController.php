<?php
/**
 * Copyright (c) 2017. Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
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
 * @file RedirectionsController.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */

namespace Themes\Rozier\Controllers;

use RZ\Roadiz\Core\Entities\Redirection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Themes\Rozier\Forms\RedirectionType;
use Themes\Rozier\RozierApp;
use Themes\Rozier\Utils\SessionListFilters;

/**
 * Class RedirectionsController
 * @package Themes\Rozier\Controllers
 */
class RedirectionsController extends RozierApp
{
    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {
        $this->validateAccessForRole('ROLE_ACCESS_REDIRECTIONS');

        $listManager = $this->createEntityListManager(
            'RZ\Roadiz\Core\Entities\Redirection',
            [],
            ['query' => 'ASC']
        );
        /*
         * Stored in session
         */
        $sessionListFilter = new SessionListFilters('redirections_item_per_page');
        $sessionListFilter->handleItemPerPage($request, $listManager);
        $listManager->handle();

        $this->assignation['filters'] = $listManager->getAssignation();
        $this->assignation['redirections'] = $listManager->getEntities();

        return $this->render('redirections/list.html.twig', $this->assignation);
    }

    /**
     * @param Request $request
     * @param $redirectionId
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function editAction(Request $request, $redirectionId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_REDIRECTIONS');

        /** @var Redirection|null $redirection */
        $redirection = $this->get('em')->find('RZ\Roadiz\Core\Entities\Redirection', $redirectionId);

        if (null === $redirection) {
            throw new ResourceNotFoundException();
        }

        $form = $this->createForm(new RedirectionType($this->get('em')), $redirection);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $this->get('em')->flush();

            return $this->redirect($this->generateUrl('redirectionsEditPage', [
                'redirectionId' => $redirectionId
            ]));
        }

        $this->assignation['form'] = $form->createView();
        $this->assignation['redirection'] = $redirection;

        return $this->render('redirections/edit.html.twig', $this->assignation);
    }

    /**
     * @param Request $request
     * @param integer $redirectionId
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction(Request $request, $redirectionId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_REDIRECTIONS');

        /** @var Redirection|null $redirection */
        $redirection = $this->get('em')->find('RZ\Roadiz\Core\Entities\Redirection', $redirectionId);

        if (null === $redirection) {
            throw new ResourceNotFoundException();
        }

        $form = $this->createForm('form');
        $form->handleRequest($request);

        if ($form->isValid()) {
            $this->get('em')->remove($redirection);
            $this->get('em')->flush();

            return $this->redirect($this->generateUrl('redirectionsHomePage'));
        }
        $this->assignation['form'] = $form->createView();
        $this->assignation['redirection'] = $redirection;

        return $this->render('redirections/delete.html.twig', $this->assignation);
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function addAction(Request $request)
    {
        $this->validateAccessForRole('ROLE_ACCESS_REDIRECTIONS');

        $redirection = new Redirection();
        $form = $this->createForm(new RedirectionType($this->get('em')), $redirection);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $this->get('em')->persist($redirection);
            $this->get('em')->flush();

            return $this->redirect($this->generateUrl('redirectionsHomePage'));
        }

        $this->assignation['form'] = $form->createView();

        return $this->render('redirections/add.html.twig', $this->assignation);
    }
}
