<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Bags;

use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManager;
use RZ\Roadiz\Core\Entities\Document;
use RZ\Roadiz\Core\Entities\Setting;
use RZ\Roadiz\Core\Repositories\SettingRepository;

/**
 * @package RZ\Roadiz\Core\Bags
 */
class Settings extends LazyParameterBag
{
    /**
     * @var EntityManager
     */
    private $entityManager;
    /**
     * @var SettingRepository
     */
    private $repository;

    /**
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    /**
     * @return SettingRepository
     */
    public function getRepository()
    {
        if (null === $this->repository) {
            $this->repository = $this->entityManager->getRepository(Setting::class);
        }
        return $this->repository;
    }

    protected function populateParameters()
    {
        try {
            $settings = $this->getRepository()->findAll();
            $this->parameters = [];
            /** @var Setting $setting */
            foreach ($settings as $setting) {
                $this->parameters[$setting->getName()] = $setting->getValue();
            }
        } catch (DBALException $e) {
            $this->parameters = [];
        }
        $this->ready = true;
    }

    /**
     * @param string $key
     * @param mixed $default
     * @return bool|mixed
     */
    public function get($key, $default = false)
    {
        return parent::get($key, $default);
    }

    /**
     * Get a document from its setting name.
     *
     * @param string $key
     * @return Document|null
     */
    public function getDocument($key): ?Document
    {
        if (null !== $this->entityManager) {
            try {
                $id = $this->getInt($key);
                return $this->entityManager
                            ->getRepository(Document::class)
                            ->findOneById($id);
            } catch (\Exception $e) {
                return null;
            }
        }

        return null;
    }
}
