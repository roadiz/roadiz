<?php
/**
 * Copyright © 2019, Ambroise Maupate and Julien Blanchet
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
 * Except as contained in this notice, the name of the roadiz shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from Ambroise Maupate and Julien Blanchet.
 *
 * @file GroupVoter.php
 * @author Ambroise Maupate
 *
 */
declare(strict_types=1);

namespace RZ\Roadiz\Core\Authorization\Voter;

use RZ\Roadiz\Core\Entities\Group;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\RoleVoter;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\Role\Role;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

class GroupVoter extends RoleVoter
{
    private $roleHierarchy;

    public function __construct(RoleHierarchyInterface $roleHierarchy, $prefix = 'ROLE_')
    {
        $this->roleHierarchy = $roleHierarchy;
        parent::__construct($prefix);
    }

    /**
     * {@inheritdoc}
     */
    protected function extractRoles(TokenInterface $token)
    {
        return $this->roleHierarchy->getReachableRoles($token->getRoles());
    }

    /**
     * @inheritDoc
     */
    public function vote(TokenInterface $token, $subject, array $attributes)
    {
        $result = VoterInterface::ACCESS_ABSTAIN;
        $roles = $this->extractRoles($token);

        foreach ($attributes as $attribute) {
            if (!($attribute instanceof Group)) {
                return VoterInterface::ACCESS_ABSTAIN;
            }

            $result = VoterInterface::ACCESS_GRANTED;
            foreach ($this->extractGroupRoles($attribute) as $role) {
                if (!$this->isRoleContained($role, $roles)) {
                    $result = VoterInterface::ACCESS_DENIED;
                }
            }
        }

        return $result;
    }

    /**
     * @param Group $group
     *
     * @return \Symfony\Component\Security\Core\Role\RoleInterface[]
     */
    protected function extractGroupRoles(Group $group)
    {
        return $this->roleHierarchy->getReachableRoles($group->getRolesEntities()->toArray());
    }

    /**
     * @param Role $role
     * @param Role[] $roles
     *
     * @return bool
     */
    protected function isRoleContained(Role $role, $roles)
    {
        foreach ($roles as $singleRole) {
            if ($singleRole instanceof Role) {
                if ($role->getRole() === $singleRole->getRole()) {
                    return true;
                }
            }
        }
        return false;
    }
}
