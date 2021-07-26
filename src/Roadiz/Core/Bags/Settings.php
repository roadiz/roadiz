<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Bags;

use Doctrine\DBAL\DBALException;
use Doctrine\Persistence\ManagerRegistry;
use RZ\Roadiz\Bag\LazyParameterBag;
use RZ\Roadiz\Core\Entities\Document;
use RZ\Roadiz\Core\Entities\Setting;
use RZ\Roadiz\Core\Repositories\SettingRepository;

class Settings extends LazyParameterBag
{
    private ManagerRegistry $managerRegistry;
    private ?SettingRepository $repository = null;

    /**
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct();
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * @return SettingRepository
     */
    public function getRepository()
    {
        if (null === $this->repository) {
            $this->repository = $this->managerRegistry->getRepository(Setting::class);
        }
        return $this->repository;
    }

    protected function populateParameters(): void
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
        try {
            $id = $this->getInt($key);
            return $this->managerRegistry
                        ->getRepository(Document::class)
                        ->findOneById($id);
        } catch (\Exception $e) {
            return null;
        }
    }
}
