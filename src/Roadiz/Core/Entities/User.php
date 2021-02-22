<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use RZ\Roadiz\Core\AbstractEntities\AbstractHuman;
use RZ\Roadiz\Utils\Security\SaltGenerator;
use Symfony\Component\Security\Core\User\AdvancedUserInterface;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * User Entity.
 *
 * @ORM\Entity(repositoryClass="RZ\Roadiz\Core\Repositories\UserRepository")
 * @ORM\Table(name="users", indexes={
 *     @ORM\Index(columns={"enabled"}),
 *     @ORM\Index(columns={"expired"}),
 *     @ORM\Index(columns={"expires_at"}),
 *     @ORM\Index(columns={"locale"})
 * })
 * @ORM\HasLifecycleCallbacks
 */
class User extends AbstractHuman implements AdvancedUserInterface, \Serializable, EquatableInterface
{
    /**
     * Email confirmation link TTL (in seconds) to change
     * password.
     *
     * @var int
     */
    const CONFIRMATION_TTL = 900;

    /**
     * @var bool
     * @Serializer\Groups({"user"})
     */
    protected $sendCreationConfirmationEmail;

    /**
     * @var string|null
     * @ORM\Column(type="string", name="facebook_name", unique=false, nullable=true)
     * @Serializer\Groups({"user"})
     */
    protected $facebookName = null;

    /**
     * @var string|null
     * @ORM\Column(type="text", name="picture_url", nullable=true)
     * @Serializer\Groups({"user"})
     */
    protected $pictureUrl = '';
    /**
     * @var boolean
     * @ORM\Column(type="boolean", nullable=false, options={"default" = true})
     * @Serializer\Groups({"user"})
     */
    protected $enabled = true;
    /**
     * @ORM\Column(name="confirmation_token", type="string", unique=true, nullable=true)
     * @Serializer\Groups({"user"})
     * @var string|null
     */
    protected $confirmationToken;
    /**
     * @ORM\Column(name="password_requested_at", type="datetime", nullable=true)
     * @Serializer\Groups({"user"})
     * @var \DateTime|null
     */
    protected $passwordRequestedAt;
    /**
     * @ORM\Column(type="string", unique=true)
     * @Serializer\Groups({"user", "log_user"})
     * @var string
     */
    private $username = '';
    /**
     * The salt to use for hashing
     *
     * @ORM\Column(name="salt", type="string")
     * @Serializer\Exclude()
     * @var string
     */
    private $salt = '';
    /**
     * Encrypted password.
     *
     * @var string
     * @ORM\Column(type="string", nullable=false)
     * @Serializer\Exclude()
     */
    private $password = '';
    /**
     * Plain password. Used for model validation.
     * **Must not be persisted.**
     *
     * @var string|null
     * @Serializer\Exclude()
     */
    private $plainPassword = null;
    /**
     * @var \DateTime|null
     * @ORM\Column(name="last_login", type="datetime", nullable=true)
     * @Serializer\Groups({"user"})
     */
    private $lastLogin;
    /**
     * @var Collection<Role>
     * @ORM\ManyToMany(targetEntity="RZ\Roadiz\Core\Entities\Role")
     * @Serializer\Groups({"user_role"})
     * @Serializer\Accessor(getter="getRolesEntities",setter="setRolesEntities")
     * @ORM\JoinTable(name="users_roles",
     *      joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="role_id", referencedColumnName="id", onDelete="CASCADE")}
     * )
     */
    private $roles;
    /**
     * Names of current User roles
     * to be compatible with symfony security scheme
     *
     * @var array<string>
     * @Serializer\Groups({"user"})
     */
    private $rolesNames = null;
    /**
     * @ORM\ManyToMany(targetEntity="RZ\Roadiz\Core\Entities\Group", inversedBy="users")
     * @ORM\JoinTable(name="users_groups",
     *      joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="group_id", referencedColumnName="id")}
     * )
     * @var Collection<Group>
     * @Serializer\Groups({"user_group"})
     */
    private $groups;
    /**
     * @var boolean
     * @ORM\Column(type="boolean", nullable=false, options={"default" = false})
     * @Serializer\Groups({"user"})
     */
    private $expired = false;
    /**
     * @var boolean
     * @ORM\Column(type="boolean", nullable=false, options={"default" = false})
     * @Serializer\Groups({"user"})
     */
    private $locked = false;
    /**
     * @ORM\Column(name="credentials_expires_at", type="datetime", nullable=true)
     * @Serializer\Groups({"user"})
     * @var \DateTime|null
     */
    private $credentialsExpiresAt;
    /**
     * @var boolean
     * @ORM\Column(type="boolean", name="credentials_expired", nullable=false, options={"default" = false})
     * @Serializer\Groups({"user"})
     */
    private $credentialsExpired = false;
    /**
     * @ORM\Column(name="expires_at", type="datetime", nullable=true)
     * @Serializer\Groups({"user"})
     * @var \DateTime|null
     */
    private $expiresAt;
    /**
     * @ORM\ManyToOne(targetEntity="RZ\Roadiz\Core\Entities\Node")
     * @ORM\JoinColumn(name="chroot_id", referencedColumnName="id", onDelete="SET NULL")
     * @Serializer\Groups({"user"})
     * @var Node|null
     */
    private $chroot;

    /**
     * @var null|string
     * @ORM\Column(name="locale", type="string", nullable=true, length=7)
     * @Serializer\Groups({"user"})
     */
    private $locale = null;

    public function __construct()
    {
        $this->roles = new ArrayCollection();
        $this->groups = new ArrayCollection();
        $this->sendCreationConfirmationEmail(false);
        $this->initAbstractDateTimed();

        $saltGenerator = new SaltGenerator();
        $this->setSalt($saltGenerator->generateSalt());
    }

    /**
     * Set if we need Roadiz to send a default email
     * when User will be persisted.
     *
     * @param bool $sendCreationConfirmationEmail
     * @return User
     */
    public function sendCreationConfirmationEmail($sendCreationConfirmationEmail): User
    {
        $this->sendCreationConfirmationEmail = $sendCreationConfirmationEmail;
        return $this;
    }

    /**
     * Tells if we need Roadiz to send a default email
     * when User will be persisted. Default: false.
     *
     * @return bool
     */
    public function willSendCreationConfirmationEmail(): bool
    {
        return $this->sendCreationConfirmationEmail;
    }

    /**
     * Get available user name data, first name and last name
     * or username as a last try.
     *
     * @return string
     * @Serializer\Groups({"user"})
     * @Serializer\VirtualProperty()
     */
    public function getIdentifier(): string
    {
        if ($this->getFirstName() != "" && $this->getLastName() != "") {
            return $this->getFirstName() . " " . $this->getLastName();
        } elseif ($this->getFirstName() != "") {
            return $this->getFirstName();
        } else {
            return $this->getUsername();
        }
    }

    /**
     * @return string $username
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * @param string $username
     *
     * @return $this
     */
    public function setUsername($username): User
    {
        $this->username = $username;
        return $this;
    }

    /**
     * Get facebook profile name to grab public infos such as picture
     *
     * @return string|null
     */
    public function getFacebookName(): ?string
    {
        return $this->facebookName;
    }

    /**
     * @param string $facebookName
     * @return $this
     */
    public function setFacebookName($facebookName): User
    {
        $this->facebookName = $facebookName;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getPictureUrl(): ?string
    {
        return $this->pictureUrl;
    }

    /**
     * @param string $pictureUrl
     * @return $this
     */
    public function setPictureUrl($pictureUrl): User
    {
        $this->pictureUrl = $pictureUrl;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getSalt(): ?string
    {
        return $this->salt;
    }

    /**
     * @param string $salt
     * @return $this
     */
    public function setSalt($salt): User
    {
        $this->salt = $salt;
        return $this;
    }

    /**
     * @return string $password
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @param string $password
     * @return $this
     */
    public function setPassword($password): User
    {
        $this->password = $password;
        return $this;
    }

    /**
     * @return string|null $plainPassword
     */
    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    /**
     * @param string $plainPassword
     * @return User
     */
    public function setPlainPassword($plainPassword): User
    {
        $this->plainPassword = $plainPassword;
        if (null !== $plainPassword && $plainPassword != '') {
            /*
             * We MUST change password to trigger preUpdate lifeCycle event.
             */
            $this->password = '--password-changed--' . uniqid();
        }
        return $this;
    }

    /**
     * @return \DateTime $lastLogin
     */
    public function getLastLogin(): ?\DateTime
    {
        return $this->lastLogin;
    }

    /**
     * @param \DateTime $lastLogin
     * @return User
     */
    public function setLastLogin($lastLogin): User
    {
        $this->lastLogin = $lastLogin;
        return $this;
    }

    /**
     * Get random string sent to the user email address in order to verify it.
     *
     * @return string
     */
    public function getConfirmationToken(): ?string
    {
        return $this->confirmationToken;
    }

    /**
     * Set random string sent to the user email address in order to verify it.
     *
     * @param string $confirmationToken
     * @return $this
     */
    public function setConfirmationToken($confirmationToken): User
    {
        $this->confirmationToken = $confirmationToken;

        return $this;
    }

    /**
     * Check if password reset request has expired.
     *
     * @param int $ttl Password request time to live.
     *
     * @return boolean
     */
    public function isPasswordRequestNonExpired($ttl): bool
    {
        return $this->getPasswordRequestedAt() instanceof \DateTime &&
            $this->getPasswordRequestedAt()->getTimestamp() + $ttl > time();
    }

    /**
     * Gets the timestamp that the user requested a password reset.
     *
     * @return null|\DateTime
     */
    public function getPasswordRequestedAt(): ?\DateTime
    {
        return $this->passwordRequestedAt;
    }

    /**
     * Sets the timestamp that the user requested a password reset.
     *
     * @param \DateTime|null $date
     * @return $this
     */
    public function setPasswordRequestedAt(\DateTime $date = null): User
    {
        $this->passwordRequestedAt = $date;
        return $this;
    }

    /**
     * Add a role object to current user.
     *
     * @param Role $role
     *
     * @return $this
     */
    public function addRole(Role $role): User
    {
        if (!$this->getRolesEntities()->contains($role)) {
            $this->getRolesEntities()->add($role);
        }

        return $this;
    }

    /**
     * Get roles entities
     *
     * @return Collection
     */
    public function getRolesEntities(): ?Collection
    {
        return $this->roles;
    }

    /**
     * @param ArrayCollection $roles
     *
     * @return User
     */
    public function setRolesEntities(ArrayCollection $roles): User
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * Remove role from current user.
     *
     * @param Role $role
     *
     * @return $this
     */
    public function removeRole(Role $role): User
    {
        if ($this->getRolesEntities()->contains($role)) {
            $this->getRolesEntities()->removeElement($role);
        }

        return $this;
    }

    /**
     * Removes sensitive data from the user.
     *
     * @return User
     */
    public function eraseCredentials(): User
    {
        return $this->setPlainPassword('');
    }

    /**
     * Insert user into group.
     *
     * @param Group $group
     *
     * @return $this
     */
    public function addGroup(Group $group): User
    {
        if (!$this->getGroups()->contains($group)) {
            $this->getGroups()->add($group);
        }

        return $this;
    }

    /**
     * @return Collection
     */
    public function getGroups(): ?Collection
    {
        return $this->groups;
    }

    /**
     * Remove user from group
     *
     * @param Group $group
     *
     * @return $this
     */
    public function removeGroup(Group $group): User
    {
        if ($this->getGroups()->contains($group)) {
            $this->getGroups()->removeElement($group);
        }

        return $this;
    }

    /**
     * Get current user groups name.
     *
     * @return array Array of strings
     * @Serializer\Groups({"user"})
     * @Serializer\VirtualProperty()
     */
    public function getGroupNames(): array
    {
        $names = [];
        foreach ($this->getGroups() as $group) {
            $names[] = $group->getName();
        }

        return $names;
    }

    /**
     * Return strictly forced expiration status.
     *
     * @return boolean
     */
    public function getExpired(): bool
    {
        return $this->expired;
    }

    /**
     * @param boolean $expired
     * @return $this
     */
    public function setExpired($expired): User
    {
        $this->expired = $expired;

        return $this;
    }

    /**
     * Checks whether the user's account has expired.
     *
     * Combines expiresAt date-time limit AND expired boolean value.
     *
     * Internally, if this method returns false, the authentication system
     * will throw an AccountExpiredException and prevent login.
     *
     * @return bool    true if the user's account is non expired, false otherwise
     *
     * @see AccountExpiredException
     * @Serializer\Groups({"user"})
     * @Serializer\VirtualProperty()
     */
    public function isAccountNonExpired(): bool
    {
        if ($this->expiresAt !== null &&
            $this->expiresAt->getTimestamp() < time()) {
            return false;
        }

        return !$this->expired;
    }

    /**
     * Checks whether the user is locked.
     *
     * Internally, if this method returns false, the authentication system
     * will throw a LockedException and prevent login.
     *
     * @return bool true if the user is not locked, false otherwise
     *
     * @see LockedException
     * @Serializer\Groups({"user"})
     * @Serializer\VirtualProperty()
     */
    public function isAccountNonLocked(): bool
    {
        return !$this->locked;
    }

    public function setLocked($locked)
    {
        $this->locked = (boolean)$locked;
        return $this;
    }

    /**
     * @return bool
     */
    public function isLocked(): bool
    {
        return $this->locked;
    }

    /**
     * {@inheritdoc}
     *
     * @param User $user
     *
     * @return boolean
     */
    public function equals(User $user): bool
    {
        return (
            $this->username == $user->getUsername() ||
            $this->email == $user->getEmail()
        );
    }

    /**
     * @return \DateTime
     */
    public function getCredentialsExpiresAt(): ?\DateTime
    {
        return $this->credentialsExpiresAt;
    }

    /**
     * @param \DateTime $date
     *
     * @return User
     */
    public function setCredentialsExpiresAt(\DateTime $date = null): User
    {
        $this->credentialsExpiresAt = $date;

        return $this;
    }

    /**
     * Return strictly forced credentials expiration status.
     *
     * @return boolean
     */
    public function getCredentialsExpired(): bool
    {
        return $this->credentialsExpired;
    }

    /**
     * @param boolean $credentialsExpired
     * @return $this
     */
    public function setCredentialsExpired($credentialsExpired): User
    {
        $this->credentialsExpired = $credentialsExpired;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getExpiresAt(): ?\DateTime
    {
        return $this->expiresAt;
    }

    /**
     * @param \DateTime $date
     *
     * @return User
     */
    public function setExpiresAt(\DateTime $date = null): User
    {
        $this->expiresAt = $date;

        return $this;
    }

    /**
     * @return Node|null
     * @internal Do use directly, use NodeChrootResolver class to support external users (SSO, oauth2, …)
     */
    public function getChroot(): ?Node
    {
        return $this->chroot;
    }

    /**
     * @param Node|null $chroot
     * @return User
     */
    public function setChroot(Node $chroot = null): User
    {
        $this->chroot = $chroot;

        return $this;
    }

    /**
     * Get prototype abstract Gravatar url.
     *
     * @param string $type Default: "identicon"
     * @param string $size Default: "200"
     *
     * @return string
     */
    public function getGravatarUrl($type = "identicon", $size = "200"): string
    {
        if (null !== $this->getEmail()) {
            return "https://www.gravatar.com/avatar/" . md5(strtolower(trim($this->getEmail()))) . "?d=".$type."&s=".$size;
        }
        return '';
    }

    /**
     * @return string $text
     */
    public function __toString(): string
    {
        $text = $this->getUsername() . ' <' . $this->getEmail() . '>' . PHP_EOL;
        $text .= '— Enabled: ' . ($this->isEnabled() ? 'Yes' : 'No') . PHP_EOL;
        $text .= '— Expired: ' . ($this->isCredentialsNonExpired() ? 'No' : 'Yes') . PHP_EOL;
        $text .= "— Roles: " . implode(', ', $this->getRoles());

        return $text;
    }

    /**
     * Checks whether the user is enabled.
     *
     * Internally, if this method returns false, the authentication system
     * will throw a DisabledException and prevent login.
     *
     * @return bool true if the user is enabled, false otherwise
     *
     * @see DisabledException
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @param boolean $enabled
     *
     * @return User
     */
    public function setEnabled($enabled): User
    {
        $this->enabled = (boolean)$enabled;

        return $this;
    }

    /**
     * Checks whether the user's credentials (password) has expired.
     *
     * Combines credentialsExpiresAt date-time limit AND credentialsExpired boolean value.
     *
     * Internally, if this method returns false, the authentication system
     * will throw a CredentialsExpiredException and prevent login.
     *
     * @return bool    true if the user's credentials are non expired, false otherwise
     *
     * @see CredentialsExpiredException
     */
    public function isCredentialsNonExpired(): bool
    {
        if ($this->credentialsExpiresAt !== null &&
            $this->credentialsExpiresAt->getTimestamp() < time()) {
            return false;
        }

        return !$this->credentialsExpired;
    }

    /**
     * Get roles names as a simple array, combining groups roles.
     *
     * @return array<string>
     */
    public function getRoles(): array
    {
        $this->rolesNames = [];
        if (null !== $this->getRolesEntities()) {
            foreach ($this->getRolesEntities() as $role) {
                if (null !== $role) {
                    $this->rolesNames[] = $role->getName();
                }
            }
        }
        if (null !== $this->getGroups()) {
            foreach ($this->getGroups() as $group) {
                if ($group instanceof Group) {
                    // User roles > Groups roles
                    $this->rolesNames = array_merge($group->getRoles(), $this->rolesNames);
                }
            }
        }

        // we need to make sure to have at least one role
        $this->rolesNames[] = Role::ROLE_DEFAULT;
        $this->rolesNames = array_unique($this->rolesNames);

        return $this->rolesNames;
    }

    /**
     * @return null|string
     */
    public function getLocale(): ?string
    {
        return $this->locale;
    }

    /**
     * @param null|string $locale
     * @return User
     */
    public function setLocale($locale): User
    {
        $this->locale = $locale;
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @see https://github.com/FriendsOfSymfony/FOSUserBundle/blob/master/Model/User.php
     */
    public function serialize()
    {
        return serialize([
            $this->password,
            $this->salt,
            $this->username,
            $this->enabled,
            $this->id,
            $this->email,
            // needed for token roles
            $this->roles,
            $this->groups,
            // needed for advancedUserinterface
            $this->expired,
            $this->expiresAt,
            $this->locked,
            $this->credentialsExpired,
            $this->credentialsExpiresAt,
        ]);
    }
    /**
     * {@inheritdoc}
     *
     * @see https://github.com/FriendsOfSymfony/FOSUserBundle/blob/master/Model/User.php
     */
    public function unserialize($serialized)
    {
        $data = unserialize($serialized);
        if (count($data) === 6) {
            // Compatibility with Roadiz <=1.4
            [
                $this->password,
                $this->salt,
                $this->username,
                $this->enabled,
                $this->id,
                $this->email,
            ] = $data;
        } else {
            [
                $this->password,
                $this->salt,
                $this->username,
                $this->enabled,
                $this->id,
                $this->email,
                $this->roles,
                $this->groups,
                $this->expired,
                $this->expiresAt,
                $this->locked,
                $this->credentialsExpired,
                $this->credentialsExpiresAt,
            ] = $data;
        }
    }

    /**
     * @Serializer\Groups({"user"})
     * @Serializer\VirtualProperty()
     */
    public function isSuperAdmin()
    {
        return $this->hasRole(Role::ROLE_SUPERADMIN);
    }

    /**
     * @param mixed $name
     *
     * @return bool
     */
    public function hasGroup($name)
    {
        return in_array((string) $name, $this->getGroupNames());
    }

    /**
     * @param mixed $role
     *
     * @return bool
     */
    public function hasRole($role)
    {
        return in_array(strtoupper((string) $role), $this->getRoles(), true);
    }

    /**
     * Every field tested in this methods must be serialized in token.
     *
     * @param UserInterface $user
     *
     * @return bool
     */
    public function isEqualTo(UserInterface $user)
    {
        if (!$user instanceof User) {
            return false;
        }

        if ($this->getId() !== $user->getId()) {
            return false;
        }

        if ($this->getEmail() !== $user->getEmail()) {
            return false;
        }

        if ($this->getPassword() !== $user->getPassword()) {
            return false;
        }

        if ($this->getSalt() !== $user->getSalt()) {
            return false;
        }

        if ($this->getUsername() !== $user->getUsername()) {
            return false;
        }

        if ($this->isAccountNonExpired() !== $user->isAccountNonExpired()) {
            return false;
        }

        if ($this->isAccountNonLocked() !== $user->isAccountNonLocked()) {
            return false;
        }

        if ($this->isCredentialsNonExpired() !== $user->isCredentialsNonExpired()) {
            return false;
        }

        if ($this->isEnabled() !== $user->isEnabled()) {
            return false;
        }

        if (array_diff($this->getRoles(), $user->getRoles())) {
            return false;
        }

        return true;
    }
}
