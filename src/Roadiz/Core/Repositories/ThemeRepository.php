<?php
/**
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
 * @file ThemeRepository.php
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
     * @return \RZ\Roadiz\Core\Entities\Theme
     */
    public function findAvailableBackend()
    {
        $query = $this->_em->createQuery('
            SELECT t FROM RZ\Roadiz\Core\Entities\Theme t
            WHERE t.available = true
            AND t.backendTheme = true')
                    ->setMaxResults(1);

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
     * We need to order themes using hostname to make
     * hostnamed theme prioritary over wildcard themes.
     *
     * @return array|null
     */
    public function findAvailableFrontends()
    {
        $query = $this->_em->createQuery('
            SELECT t FROM RZ\Roadiz\Core\Entities\Theme t
            WHERE t.available = true
            AND t.backendTheme = false
            ORDER BY t.hostname DESC, t.staticTheme DESC');

        $query->useResultCache(true, 3600, 'RZTheme_frontends');

        try {
            return $query->getResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }

    /**
     * Get available frontend theme.
     *
     * This method use Result cache.
     *
     * @return \RZ\Roadiz\Core\Entities\Theme|null
     */
    public function findFirstAvailableFrontend()
    {
        $query = $this->_em->createQuery('
            SELECT t FROM RZ\Roadiz\Core\Entities\Theme t
            WHERE t.available = true
            AND t.backendTheme = false
            AND t.hostname = \'*\'')
                    ->setMaxResults(1);

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
     * @param string $hostname
     *
     * @return \RZ\Roadiz\Core\Entities\Theme|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findAvailableFrontendWithHost($hostname = "*")
    {
        $query = $this->_em->createQuery('
            SELECT t FROM RZ\Roadiz\Core\Entities\Theme t
            WHERE t.available = true
            AND t.backendTheme = false
            AND t.hostname = :hostname')
                    ->setParameter('hostname', $hostname)
                    ->setMaxResults(1);

        $query->useResultCache(true, 3600, 'RZTheme_frontend_'.$hostname);

        try {
            return $query->getSingleResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }

    /**
     * Get available non-static frontend theme.
     *
     * Static means that your theme is not suitable for responding from
     * nodes urls but only static routes.
     *
     * This method use Result cache.
     *
     * @return \RZ\Roadiz\Core\Entities\Theme|null
     */
    public function findFirstAvailableNonStaticFrontend()
    {
        $query = $this->_em->createQuery('
            SELECT t FROM RZ\Roadiz\Core\Entities\Theme t
            WHERE t.available = true
            AND t.backendTheme = false
            AND t.staticTheme = false
            AND t.hostname = \'*\'')
                    ->setMaxResults(1);

        $query->useResultCache(true, 3600, 'RZTheme_first_nonstatic_frontend');

        try {
            return $query->getSingleResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }

    /**
     * Get available non-static frontend theme for host.
     *
     * Static means that your theme is not suitable for responding from
     * nodes urls but only static routes.
     *
     * This method use Result cache.
     *
     * @param string $hostname
     *
     * @return \RZ\Roadiz\Core\Entities\Theme|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findAvailableNonStaticFrontendWithHost($hostname = "*")
    {
        $query = $this->_em->createQuery('
            SELECT t FROM RZ\Roadiz\Core\Entities\Theme t
            WHERE t.available = true
            AND t.backendTheme = false
            AND t.staticTheme = false
            AND t.hostname = :hostname')
                    ->setParameter('hostname', $hostname)
                    ->setMaxResults(1);

        $query->useResultCache(true, 3600, 'RZTheme_nonstatic_frontend_'.$hostname);

        try {
            return $query->getSingleResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }

    /**
     * Cached query for find a theme with its class-name.
     *
     * @param  string $className
     *
     * @return \RZ\Roadiz\Core\Entities\Theme|null
     */
    public function findOneByClassName($className)
    {
        $query = $this->_em->createQuery('
            SELECT t FROM RZ\Roadiz\Core\Entities\Theme t
            WHERE t.className = :className')
                    ->setParameter('className', $className)
                    ->setMaxResults(1);

        $query->useResultCache(true, 3600, 'RZTheme_'.$className);

        try {
            return $query->getSingleResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }
}
