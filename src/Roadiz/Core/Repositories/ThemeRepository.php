<?php
/**
 * Copyright © 2014, REZO ZERO
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
 * Except as contained in this notice, the name of the REZO ZERO shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from the REZO ZERO SARL.
 *
 * @file ThemeRepository.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Repositories;

/**
 * {@inheritdoc}
 */
class ThemeRepository extends EntityRepository
{

    /**
     * Get available backend theme.
     *
     * This method use Result cache.
     *
     * @return RZ\Roadiz\Core\Entities\Theme
     */
    public function findAvailableBackend()
    {
        $query = $this->_em->createQuery('
            SELECT t FROM RZ\Roadiz\Core\Entities\Theme t
            WHERE t.available = true AND t.backendTheme = true');

        $query->useResultCache(true, 3600, 'RZTheme_backend');

        try {
            return $query->getSingleResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }

    /**
     * Get available frontend themes.
     *
     * This method uses Result cache.
     *
     * @return array
     */
    public function findAvailableFrontends()
    {
        $query = $this->_em->createQuery('
            SELECT t FROM RZ\Roadiz\Core\Entities\Theme t
            WHERE t.available = true
            AND t.backendTheme = false');

        $query->useResultCache(true, 3600, 'RZTheme_frontends');

        try {
            return $query->getResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }

    /**
     * Get available frontend theme for host.
     *
     * This method use Result cache.
     *
     * @return RZ\Roadiz\Core\Entities\Theme
     */
    public function findFirstAvailableFrontend()
    {
        $query = $this->_em->createQuery('
            SELECT t FROM RZ\Roadiz\Core\Entities\Theme t
            WHERE t.available = true
            AND t.backendTheme = false');

        $query->useResultCache(true, 3600, 'RZTheme_first_frontend');

        try {
            return $query->getSingleResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }

    /**
     * Get available frontend theme for host.
     *
     * This method use Result cache.
     *
     * @return RZ\Roadiz\Core\Entities\Theme
     */
    public function findAvailableFrontendWithHost($hostname = "*")
    {
        $query = $this->_em->createQuery('
            SELECT t FROM RZ\Roadiz\Core\Entities\Theme t
            WHERE t.available = true
            AND t.backendTheme = false
            AND t.hostname = :hostname')
                    ->setParameter('hostname', $hostname);

        $query->useResultCache(true, 3600, 'RZTheme_frontend_'.$hostname);

        try {
            return $query->getSingleResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }
}
