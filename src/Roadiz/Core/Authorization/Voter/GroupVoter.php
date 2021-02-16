<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Authorization\Voter;

use RZ\Roadiz\Core\Entities\Group;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\RoleVoter;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

class GroupVoter extends RoleVoter
{
    private RoleHierarchyInterface $roleHierarchy;

    public function __construct(RoleHierarchyInterface $roleHierarchy, string $prefix = 'ROLE_')
    {
        $this->roleHierarchy = $roleHierarchy;
        parent::__construct($prefix);
    }

    protected function extractRoles(TokenInterface $token)
    {
        return $this->roleHierarchy->getReachableRoleNames($token->getRoleNames());
    }

    /**
     * @inheritDoc
     */
    public function vote(TokenInterface $token, $subject, array $attributes)
    {
        $result = VoterInterface::ACCESS_ABSTAIN;
        /** @var string[] $roles */
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
     * @return string[]
     */
    protected function extractGroupRoles(Group $group)
    {
        return $this->roleHierarchy->getReachableRoleNames($group->getRoles());
    }

    /**
     * @param string $role
     * @param string[] $roles
     *
     * @return bool
     */
    protected function isRoleContained(string $role, $roles)
    {
        foreach ($roles as $singleRole) {
            if ($role === $singleRole) {
                return true;
            }
        }
        return false;
    }
}
