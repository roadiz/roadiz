<?php
declare(strict_types=1);

namespace Themes\Rozier\Controllers;

use RZ\Roadiz\Core\Events\Cache\CachePurgeAssetsRequestEvent;
use RZ\Roadiz\Core\Events\Cache\CachePurgeRequestEvent;
use RZ\Roadiz\Core\Kernel;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Themes\Rozier\RozierApp;

/**
 * @package Themes\Rozier\Controllers
 */
class CacheController extends RozierApp
{
    /**
     * @param Request $request
     *
     * @return Response
     */
    public function deleteDoctrineCache(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_DOCTRINE_CACHE_DELETE');

        $form = $this->buildDeleteDoctrineForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var EventDispatcher $dispatcher */
            $dispatcher = $this->get('dispatcher');
            $event = new CachePurgeRequestEvent($this->get('kernel'));
            $dispatcher->dispatch($event);

            // Clear cache for prod preview
            $kernelClass = get_class($this->get('kernel'));
            /** @var Kernel $prodPreviewKernel */
            $prodPreviewKernel = new $kernelClass('prod', false, true);
            $prodPreviewKernel->boot();
            $prodPreviewEvent = new CachePurgeRequestEvent($prodPreviewKernel);
            $dispatcher->dispatch($prodPreviewEvent);

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
     * @return FormInterface
     */
    private function buildDeleteDoctrineForm()
    {
        $builder = $this->createFormBuilder();

        return $builder->getForm();
    }

    /**
     * @param Request $request
     *
     * @return Response
     * @throws \Twig\Error\RuntimeError
     */
    public function deleteAssetsCache(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_DOCTRINE_CACHE_DELETE');

        $form = $this->buildDeleteAssetsForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var EventDispatcher $dispatcher */
            $dispatcher = $this->get('dispatcher');
            $event = $dispatcher->dispatch(new CachePurgeAssetsRequestEvent($this->get('kernel')));

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
     * @return FormInterface
     */
    private function buildDeleteAssetsForm()
    {
        $builder = $this->createFormBuilder();

        return $builder->getForm();
    }
}
