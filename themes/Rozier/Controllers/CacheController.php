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

use RZ\Roadiz\Utils\Clearer\AssetsClearer;
use RZ\Roadiz\Utils\Clearer\ConfigurationCacheClearer;
use RZ\Roadiz\Utils\Clearer\DoctrineCacheClearer;
use RZ\Roadiz\Utils\Clearer\NodesSourcesUrlsCacheClearer;
use RZ\Roadiz\Utils\Clearer\OPCacheClearer;
use RZ\Roadiz\Utils\Clearer\RoutingCacheClearer;
use RZ\Roadiz\Utils\Clearer\TemplatesCacheClearer;
use RZ\Roadiz\Utils\Clearer\TranslationsCacheClearer;
use Symfony\Component\HttpFoundation\Request;
use Themes\Rozier\RozierApp;

/**
 * {@inheritdoc}
 */
class CacheController extends RozierApp
{
    /**
     * @param Symfony\Component\HttpFoundation\Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function deleteDoctrineCache(Request $request)
    {
        $this->validateAccessForRole('ROLE_ACCESS_DOCTRINE_CACHE_DELETE');

        $form = $this->buildDeleteDoctrineForm();
        $form->handleRequest($request);

        if ($form->isValid()) {
            $clearers = [
                new DoctrineCacheClearer($this->getService('em')),
                new NodesSourcesUrlsCacheClearer($this->getService('nodesSourcesUrlCacheProvider')),
                new TranslationsCacheClearer($this->getService('kernel')->getCacheDir()),
                new RoutingCacheClearer($this->getService('kernel')->getCacheDir()),
                new TemplatesCacheClearer($this->getService('kernel')->getCacheDir()),
                new ConfigurationCacheClearer($this->getService('kernel')->getCacheDir()),
                new OPCacheClearer(),
            ];
            foreach ($clearers as $clearer) {
                $clearer->clear();
            }

            $msg = $this->getTranslator()->trans('cache.deleted');
            $this->publishConfirmMessage($request, $msg);

            /*
             * Force redirect to avoid resending form when refreshing page
             */
            return $this->redirect($this->generateUrl('adminHomePage'));
        }

        $this->assignation['form'] = $form->createView();

        $this->assignation['cachesInfo'] = [
            'resultCache' => $this->getService('em')->getConfiguration()->getResultCacheImpl(),
            'hydratationCache' => $this->getService('em')->getConfiguration()->getHydrationCacheImpl(),
            'queryCache' => $this->getService('em')->getConfiguration()->getQueryCacheImpl(),
            'metadataCache' => $this->getService('em')->getConfiguration()->getMetadataCacheImpl(),
            'nodeSourcesUrlsCache' => $this->getService('nodesSourcesUrlCacheProvider'),
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
     * @return Symfony\Component\Form\Form
     */
    private function buildDeleteDoctrineForm()
    {
        $builder = $this->createFormBuilder();

        return $builder->getForm();
    }

    /**
     * @param Symfony\Component\HttpFoundation\Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function deleteAssetsCache(Request $request)
    {
        $this->validateAccessForRole('ROLE_ACCESS_DOCTRINE_CACHE_DELETE');

        $form = $this->buildDeleteAssetsForm();
        $form->handleRequest($request);

        if ($form->isValid()) {
            $clearer = new AssetsClearer($this->getService('kernel')->getCacheDir());
            $clearer->clear();

            $msg = $this->getTranslator()->trans('cache.deleted');
            $this->publishConfirmMessage($request, $msg);

            /*
             * Force redirect to avoid resending form when refreshing page
             */
            return $this->redirect($this->generateUrl('adminHomePage'));
        }

        $this->assignation['form'] = $form->createView();

        return $this->render('cache/deleteAssets.html.twig', $this->assignation);
    }

    /**
     * @return Symfony\Component\Form\Form
     */
    private function buildDeleteAssetsForm()
    {
        $builder = $this->createFormBuilder();

        return $builder->getForm();
    }
}
