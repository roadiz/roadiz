<?php
declare(strict_types=1);

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
     * @return self
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
