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
 * @file Settings.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */

namespace RZ\Roadiz\Core\Bags;

use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManager;
use RZ\Roadiz\Core\Entities\Document;
use RZ\Roadiz\Core\Entities\Setting;
use RZ\Roadiz\Core\Repositories\SettingRepository;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Class Settings
 * @package RZ\Roadiz\Core\Bags
 */
class Settings extends ParameterBag
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
     * @var bool
     */
    private $ready;

    /**
     * SettingsBag constructor.
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->ready = false;
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
        if (!$this->ready) {
            $this->populateParameters();
        }

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
                return $this->entityManager->find(Document::class, $id);
            } catch (\Exception $e) {
                return null;
            }
        }

        return null;
    }

    /**
     * @return array
     */
    public function all(): array
    {
        if (!$this->ready) {
            $this->populateParameters();
        }

        return parent::all();
    }

    public function reset(): void
    {
        $this->parameters = [];
        $this->ready = false;
    }
}
