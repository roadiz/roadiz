<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Log\Handler;

use Doctrine\Persistence\ManagerRegistry;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use RZ\Roadiz\Core\Entities\Log;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\User;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * A log system which store message in database.
 */
final class DoctrineHandler extends AbstractProcessingHandler
{
    protected ManagerRegistry $managerRegistry;
    protected TokenStorageInterface $tokenStorage;
    protected ?User $user = null;
    protected RequestStack $requestStack;

    public function __construct(
        ManagerRegistry $managerRegistry,
        TokenStorageInterface $tokenStorage,
        RequestStack $requestStack,
        $level = Logger::DEBUG,
        $bubble = true
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->requestStack = $requestStack;
        $this->managerRegistry = $managerRegistry;

        parent::__construct($level, $bubble);
    }

    /**
     * @return TokenStorageInterface
     */
    public function getTokenStorage(): TokenStorageInterface
    {
        return $this->tokenStorage;
    }
    /**
     * @param TokenStorageInterface $tokenStorage
     *
     * @return $this
     */
    public function setTokenStorage(TokenStorageInterface $tokenStorage): DoctrineHandler
    {
        $this->tokenStorage = $tokenStorage;
        return $this;
    }

    /**
     * @return User|null
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * @param User|null $user
     * @return $this
     */
    public function setUser(User $user = null): DoctrineHandler
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @return RequestStack
     */
    public function getRequestStack(): RequestStack
    {
        return $this->requestStack;
    }

    /**
     * @param RequestStack $requestStack
     * @return DoctrineHandler
     */
    public function setRequestStack(RequestStack $requestStack): DoctrineHandler
    {
        $this->requestStack = $requestStack;
        return $this;
    }

    /**
     * @param array  $record
     */
    public function write(array $record): void
    {
        try {
            $manager = $this->managerRegistry->getManagerForClass(Log::class);
            if (null !== $manager && $manager->isOpen()) {
                return;
            }

            $log = new Log(
                $record['level'],
                $record['message']
            );

            $log->setChannel((string) $record['channel']);
            $data = $record['extra'];
            if (isset($record['context']['request'])) {
                $data = array_merge(
                    $data,
                    $record['context']['request']
                );
            }
            if (isset($record['context']['username'])) {
                $data = array_merge(
                    $data,
                    ['username' => $record['context']['username']]
                );
            }
            $log->setAdditionalData($data);

            /*
             * Use available securityAuthorizationChecker to provide a valid user
             */
            if (null !== $this->getTokenStorage() &&
                null !== $token = $this->getTokenStorage()->getToken()) {
                $user = $token->getUser();
                if (null !== $user && $user instanceof UserInterface) {
                    if ($user instanceof User) {
                        $log->setUser($user);
                    } else {
                        $log->setUsername($user->getUsername());
                    }
                } else {
                    $log->setUsername($token->getUsername());
                }
            }
            /*
             * Use manually set user
             */
            if (null !== $this->getUser()) {
                $log->setUser($this->getUser());
            }

            /*
             * Add client IP to log if it’s an HTTP request
             */
            if (null !== $this->requestStack->getMasterRequest()) {
                $log->setClientIp($this->requestStack->getMasterRequest()->getClientIp());
            }

            /*
             * Add a related node-source entity
             */
            if (isset($record['context']['source']) &&
                null !== $record['context']['source'] &&
                $record['context']['source'] instanceof NodesSources) {
                $log->setNodeSource($record['context']['source']);
            }

            $manager->persist($log);
            $manager->flush();
        } catch (\Exception $e) {
            /*
             * Need to prevent SQL errors over throwing
             * if PDO has fault
             */
        }
    }
}
