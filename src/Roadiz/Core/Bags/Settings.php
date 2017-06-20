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

use Doctrine\ORM\EntityManager;
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
     * SettingsBag constructor.
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @return SettingRepository
     */
    public function getRepository()
    {
        if (null === $this->repository) {
            $this->repository = $this->entityManager->getRepository('RZ\Roadiz\Core\Entities\Setting');
        }
        return $this->repository;
    }

    protected function populateParameters()
    {
        $settings = $this->getRepository()->findAll();
        $this->parameters = [];
        /** @var Setting $setting */
        foreach ($settings as $setting) {
            $this->parameters[$setting->getName()] = $setting->getValue();
        }
    }

    /**
     * @param string $key
     * @param null $default
     * @param bool $deep
     * @return bool|mixed
     */
    public function get($key, $default = null, $deep = false)
    {
        if (!is_array($this->parameters)) {
            $this->populateParameters();
        }

        return parent::get($key, false, false);
    }

    /**
     * Get a document from its setting name.
     *
     * @param string $key
     * @return \RZ\Roadiz\Core\Entities\Document|object|bool
     */
    public function getDocument($key)
    {
        if (null !== $this->entityManager) {
            try {
                $id = $this->getInt($key);
                return $this->entityManager->find('RZ\Roadiz\Core\Entities\Document', $id);
            } catch (\Exception $e) {
                return false;
            }
        }

        return false;
    }

    /**
     * @return array
     */
    public function all()
    {
        if (!is_array($this->parameters)) {
            $this->populateParameters();
        }

        return parent::all();
    }
}
