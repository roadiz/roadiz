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
 * @file CacheController.php
 * @author Ambroise Maupate
 */
namespace Themes\Rozier\Controllers;

use RZ\Roadiz\Core\Events\CacheEvents;
use RZ\Roadiz\Core\Events\FilterCacheEvent;
use RZ\Roadiz\Core\Kernel;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Themes\Rozier\RozierApp;

/**
 * {@inheritdoc}
 */
class CacheController extends RozierApp
{
    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteDoctrineCache(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_DOCTRINE_CACHE_DELETE');

        $form = $this->buildDeleteDoctrineForm();
        $form->handleRequest($request);

        if ($form->isValid()) {
            /** @var EventDispatcher $dispatcher */
            $dispatcher = $this->get('dispatcher');
            $event = new FilterCacheEvent($this->get('kernel'));
            $dispatcher->dispatch(CacheEvents::PURGE_REQUEST, $event);

            // Clear cache for prod preview
            $kernelClass = get_class($this->get('kernel'));
            /** @var Kernel $prodPreviewKernel */
            $prodPreviewKernel = new $kernelClass('prod', false, true);
            $prodPreviewKernel->boot();
            $prodPreviewEvent = new FilterCacheEvent($prodPreviewKernel);
            $dispatcher->dispatch(CacheEvents::PURGE_REQUEST, $prodPreviewEvent);

            $msg = $this->getTranslator()->trans('cache.deleted');
            $this->publishConfirmMessage($request, $msg);

            foreach ($event->getMessages() as $message) {
                $this->get('logger')->info(sprintf('Cache cleared: %s', $message['description']));
            }
            foreach ($event->getErrors() as $message) {
                $this->publishErrorMessage($request, sprintf('Could not clear cache: %s', $message['description']));
            }
            foreach ($prodPreviewEvent->getMessages() as $message) {
                $this->get('logger')->info(sprintf('Preview cache cleared: %s', $message['description']));
            }
            foreach ($prodPreviewEvent->getErrors() as $message) {
                $this->publishErrorMessage($request, sprintf('Could not clear creview cache: %s', $message['description']));
            }

            /*
             * Force redirect to avoid resending form when refreshing page
             */
            return $this->redirect($this->generateUrl('adminHomePage'));
        }

        $this->assignation['form'] = $form->createView();

        $this->assignation['cachesInfo'] = [
            'resultCache' => $this->get('em')->getConfiguration()->getResultCacheImpl(),
            'hydratationCache' => $this->get('em')->getConfiguration()->getHydrationCacheImpl(),
            'queryCache' => $this->get('em')->getConfiguration()->getQueryCacheImpl(),
            'metadataCache' => $this->get('em')->getConfiguration()->getMetadataCacheImpl(),
            'nodeSourcesUrlsCache' => $this->get('nodesSourcesUrlCacheProvider'),
        ];

        foreach ($this->assignation['cachesInfo'] as $key => $value) {
            if (null !== $value) {
                $this->assignation['cachesInfo'][$key] = get_class($value);
            } else {
                $this->assignation['cachesInfo'][$key] = false;
            }
        }

        return $this->render('cache/deleteDoctrine.html.twig', $this->assignation);
    }

    /**
     * @return \Symfony\Component\Form\Form
     */
    private function buildDeleteDoctrineForm()
    {
        $builder = $this->createFormBuilder();

        return $builder->getForm();
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteAssetsCache(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_DOCTRINE_CACHE_DELETE');

        $form = $this->buildDeleteAssetsForm();
        $form->handleRequest($request);

        if ($form->isValid()) {
            /** @var EventDispatcher $dispatcher */
            $dispatcher = $this->get('dispatcher');
            $event = new FilterCacheEvent($this->get('kernel'));
            $dispatcher->dispatch(CacheEvents::PURGE_ASSETS_REQUEST, $event);

            $msg = $this->getTranslator()->trans('cache.deleted');
            $this->publishConfirmMessage($request, $msg);
            foreach ($event->getMessages() as $message) {
                $this->get('logger')->info(sprintf('Cache cleared: %s', $message['description']));
            }
            foreach ($event->getErrors() as $message) {
                $this->publishErrorMessage($request, sprintf('Could not clear cache: %s', $message['description']));
            }

            /*
             * Force redirect to avoid resending form when refreshing page
             */
            return $this->redirect($this->generateUrl('adminHomePage'));
        }

        $this->assignation['form'] = $form->createView();

        return $this->render('cache/deleteAssets.html.twig', $this->assignation);
    }

    /**
     * @return \Symfony\Component\Form\Form
     */
    private function buildDeleteAssetsForm()
    {
        $builder = $this->createFormBuilder();

        return $builder->getForm();
    }
}
