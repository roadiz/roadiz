<?php
/**
 * Copyright (c) 2018. Ambroise Maupate and Julien Blanchet
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
 * @file SimpleQueryBuilder.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */

namespace RZ\Roadiz\Utils\Doctrine\ORM;

use Doctrine\ORM\Query\Expr\Comparison;
use Doctrine\ORM\Query\Expr\Func;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use RZ\Roadiz\Core\AbstractEntities\PersistableInterface;

class SimpleQueryBuilder
{
    /**
     * @var QueryBuilder
     */
    protected $queryBuilder;

    /**
     * SimpleQueryBuilder constructor.
     *
     * @param QueryBuilder $queryBuilder
     */
    public function __construct(QueryBuilder $queryBuilder)
    {
        $this->queryBuilder = $queryBuilder;
    }

    /**
     * @param string $key
     *
     * @return string
     */
    public function getParameterKey($key): string
    {
        return strtolower(str_replace('.', '_', $key));
    }

    /**
     * @param mixed $value
     * @param string $prefix Property prefix including DOT
     * @param string $key
     *
     * @return QueryBuilder
     */
    public function buildExpressionWithBinding($value, string $prefix, string $key): QueryBuilder
    {
        $this->buildExpressionWithoutBinding($value, $prefix, $key);
        return $this->bindValue($key, $value);
    }

    /**
     * @param mixed $value
     * @param string $prefix
     * @param string $key
     * @param string $baseKey
     *
     * @return Comparison|Func|string
     */
    public function buildExpressionWithoutBinding($value, string $prefix, string $key, string $baseKey = null)
    {
        if (strlen($prefix) > 0 && substr($prefix, -strlen('.')) !== '.') {
            $prefix .= '.';
        }

        if (null === $baseKey) {
            $baseKey = $this->getParameterKey($key);
        }

        if (is_object($value) && $value instanceof PersistableInterface) {
            return $this->queryBuilder->expr()->eq($prefix . $key, ':' . $baseKey);
        } elseif (is_array($value)) {
            /*
             * array
             *
             * ['!=', $value]
             * ['<=', $value]
             * ['<', $value]
             * ['>=', $value]
             * ['>', $value]
             * ['BETWEEN', $value, $value]
             * ['LIKE', $value]
             * ['NOT IN', [$value]]
             * [$value, $value] (IN)
             */
            if (count($value) > 1) {
                switch ($value[0]) {
                    case '!=':
                        # neq
                        return $this->queryBuilder->expr()->neq($prefix . $key, ':' . $baseKey);
                    case '<=':
                        # lte
                        return $this->queryBuilder->expr()->lte($prefix . $key, ':' . $baseKey);
                    case '<':
                        # lt
                        return $this->queryBuilder->expr()->lt($prefix . $key, ':' . $baseKey);
                    case '>=':
                        # gte
                        return $this->queryBuilder->expr()->gte($prefix . $key, ':' . $baseKey);
                    case '>':
                        # gt
                        return $this->queryBuilder->expr()->gt($prefix . $key, ':' . $baseKey);
                    case 'BETWEEN':
                        return $this->queryBuilder->expr()->between(
                            $prefix . $key,
                            ':' . $baseKey . '_1',
                            ':' . $baseKey . '_2'
                        );
                    case 'LIKE':
                        $fullKey = sprintf('LOWER(%s)', $prefix . $key);
                        return $this->queryBuilder->expr()->like($fullKey, $this->queryBuilder->expr()->literal(strtolower($value[1])));
                    case 'NOT IN':
                        return $this->queryBuilder->expr()->notIn($prefix . $key, ':' . $baseKey);
                    case 'INSTANCE OF':
                        return $this->queryBuilder->expr()->isInstanceOf($prefix . $key, ':' . $baseKey);
                    default:
                        return $this->queryBuilder->expr()->in($prefix . $key, ':' . $baseKey);
                }
            } else {
                return $this->queryBuilder->expr()->in($prefix . $key, ':' . $baseKey);
            }
        } elseif (is_bool($value)) {
            return $this->queryBuilder->expr()->eq($prefix . $key, ':' . $baseKey);
        } elseif ('NOT NULL' == $value) {
            return $this->queryBuilder->expr()->isNotNull($prefix . $key);
        } elseif (isset($value)) {
            return $this->queryBuilder->expr()->eq($prefix . $key, ':' . $baseKey);
        } elseif (null === $value) {
            return $this->queryBuilder->expr()->isNull($prefix . $key);
        }

        throw new \InvalidArgumentException('Value is not supported for expression.');
    }

    /**
     * @param string $key
     * @param mixed $value
     *
     * @return QueryBuilder
     */
    public function bindValue(string $key, $value): QueryBuilder
    {
        $key = $this->getParameterKey($key);

        if (is_object($value) && $value instanceof PersistableInterface) {
            return $this->queryBuilder->setParameter($key, $value->getId());
        } elseif (is_array($value)) {
            if (count($value) > 1) {
                switch ($value[0]) {
                    case '!=':
                    case '<=':
                    case '<':
                    case '>=':
                    case '>':
                    case 'INSTANCE OF':
                    case 'NOT IN':
                        return $this->queryBuilder->setParameter($key, $value[1]);
                    case 'BETWEEN':
                        return $this->queryBuilder->setParameter($key . '_1', $value[1])
                                                  ->setParameter($key . '_2', $value[2]);
                    case 'LIKE':
                        // param is set in filterBy
                        return $this->queryBuilder;
                    default:
                        return $this->queryBuilder->setParameter($key, $value);
                }
            } else {
                return $this->queryBuilder->setParameter($key, $value);
            }
        } elseif (is_bool($value) || $value === 0) {
            return $this->queryBuilder->setParameter($key, $value);
        } elseif ('NOT NULL' == $value) {
            // param is not needed
            return $this->queryBuilder;
        } elseif (isset($value)) {
            return $this->queryBuilder->setParameter($key, $value);
        } elseif (null === $value) {
            return $this->queryBuilder;
        }

        throw new \InvalidArgumentException('Value is not supported for binding. ('.get_class($value).')');
    }

    /**
     * @param string $rootAlias
     * @param string $joinAlias
     *
     * @return bool
     */
    public function joinExists(string $rootAlias, string $joinAlias): bool
    {
        if (isset($this->queryBuilder->getDQLPart('join')[$rootAlias])) {
            foreach ($this->queryBuilder->getDQLPart('join')[$rootAlias] as $join) {
                if (null !== $join &&
                    $join instanceof Join &&
                    $join->getAlias() === $joinAlias) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @return QueryBuilder
     */
    public function getQueryBuilder() :QueryBuilder
    {
        return $this->queryBuilder;
    }
}
