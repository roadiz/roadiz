<?php
/**
 * Copyright (c) 2019. Ambroise Maupate and Julien Blanchet
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
 */

namespace Themes\Rozier\Traits;

use Gedmo\Exception\UnexpectedValueException;
use Gedmo\Loggable\Entity\Repository\LogEntryRepository;
use RZ\Roadiz\Core\AbstractEntities\AbstractEntity;
use RZ\Roadiz\Core\Entities\UserLogEntry;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

trait VersionedControllerTrait
{
    /**
     * @var bool
     */
    protected $isReadOnly = false;

    /**
     * @return bool
     */
    public function isReadOnly(): bool
    {
        return $this->isReadOnly;
    }

    /**
     * @param bool $isReadOnly
     *
     * @return VersionedControllerTrait
     */
    public function setIsReadOnly(bool $isReadOnly)
    {
        $this->isReadOnly = $isReadOnly;

        return $this;
    }


    /**
     * @param Request        $request
     * @param AbstractEntity $entity
     *
     * @return Response|null
     */
    protected function handleVersions(Request $request, AbstractEntity $entity): ?Response
    {
        /**
         * Versioning.
         *
         * @var LogEntryRepository $repo
         */
        $repo = $this->get('em')->getRepository(UserLogEntry::class);
        $logs = $repo->getLogEntries($entity);

        if ($request->get('version', null) !== null &&
            $request->get('version', null) > 0) {
            try {
                $versionNumber = (int) $request->get('version', null);
                $repo->revert($entity, $versionNumber);
                $this->isReadOnly = true;
                $this->assignation['currentVersionNumber'] = $versionNumber;
                /** @var UserLogEntry $log */
                foreach ($logs as $log) {
                    if ($log->getVersion() === $versionNumber) {
                        $this->assignation['currentVersion'] = $log;
                    }
                }
                /** @var FormInterface $revertForm */
                $revertForm = $this->createNamedFormBuilder('revertVersion')
                    ->add('version', HiddenType::class, ['data' => $versionNumber])
                    ->getForm();
                $revertForm->handleRequest($request);

                $this->assignation['revertForm'] = $revertForm->createView();

                if ($revertForm->isSubmitted() && $revertForm->isValid()) {
                    $this->get('em')->persist($entity);
                    $this->onPostUpdate($entity, $request);

                    return $this->getPostUpdateRedirection($entity);
                }
            } catch (UnexpectedValueException $e) {
                throw new ResourceNotFoundException();
            }
        }

        $this->assignation['versions'] = $logs;

        return null;
    }

    abstract protected function onPostUpdate(AbstractEntity $entity, Request $request): void;

    abstract protected function getPostUpdateRedirection(AbstractEntity $entity): ?Response;
}
