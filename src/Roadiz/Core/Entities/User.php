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
 * @file User.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use RZ\Roadiz\Core\AbstractEntities\AbstractHuman;
use RZ\Roadiz\Core\Entities\Group;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\Role;
use RZ\Roadiz\Core\Handlers\UserHandler;
use RZ\Roadiz\Core\Viewers\UserViewer;
use RZ\Roadiz\Utils\Security\PasswordGenerator;
use RZ\Roadiz\Utils\Security\SaltGenerator;
use Symfony\Component\Security\Core\User\AdvancedUserInterface;

/**
 * User Entity.
 *
 * @ORM\Entity(repositoryClass="RZ\Roadiz\Core\Repositories\UserRepository")
 * @ORM\Table(name="users", indexes={
 *     @ORM\Index(columns={"enabled"}),
 *     @ORM\Index(columns={"expired"}),
 *     @ORM\Index(columns={"expires_at"})
 * })
 * @ORM\HasLifecycleCallbacks
 */
class User extends AbstractHuman implements AdvancedUserInterface
{
    /**
     * @ORM\Column(type="string", unique=true)
     * @var string
     */
    private $username;

    /**
     * @return string $username
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @param string $username
     *
     * @return string $username
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Get available user name data, first name and last name
     * or username as a last try.
     *
     * @return string
     */
    public function getIdentifier()
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
     * @ORM\Column(type="string", name="facebook_name", unique=false, nullable=true)
     */
    protected $facebookName = null;

    /**
     * Get facebook profile name to grab public infos such as picture
     *
     * @return string
     */
    public function getFacebookName()
    {
        return $this->facebookName;
    }

    /**
     * @param string $facebookName
     *
     * @return string $facebookName
     */
    public function setFacebookName($facebookName)
    {
        $this->facebookName = $facebookName;

        return $this;
    }

    /**
     * @ORM\Column(type="text", name="picture_url")
     */
    protected $pictureUrl = '';

    /**
     * @return string
     */
    public function getPictureUrl()
    {
        return $this->pictureUrl;
    }

    /**
     * @param string $pictureUrl
     *
     * @return string $pictureURL
     */
    public function setPictureUrl($pictureUrl)
    {
        $this->pictureUrl = $pictureUrl;

        return $this;
    }

    /**
     * The salt to use for hashing
     *
     * @ORM\Column(name="salt", type="string")
     * @var string
     */
    private $salt;

    /**
     * @return string
     */
    public function getSalt()
    {
        return $this->salt;
    }

    /**
     * @param string $salt
     *
     * @return string $salt
     */
    public function setSalt($salt)
    {
        $this->salt = $salt;

        return $this;
    }

    /**
     * Encrypted password.
     *
     * @ORM\Column(type="string", nullable=false)
     */
    private $password;

    /**
     * @return string $password
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param string $password
     *
     * @return string $password
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Plain password. Used for model validation.
     * **Must not be persisted.**
     *
     * @var string
     */
    private $plainPassword;

    /**
     * @return string $plainPassword
     */
    public function getPlainPassword()
    {
        return $this->plainPassword;
    }

    /**
     * @param string $plainPassword
     *
     * @return string $plainPassword
     */
    public function setPlainPassword($plainPassword)
    {
        $this->plainPassword = $plainPassword;
        if ($plainPassword != '') {
            $this->getHandler()->encodePassword();
        }

        return $this;
    }

    /**
     * @var boolean
     * @ORM\Column(type="boolean", nullable=false, options={"default" = true})
     */
    protected $enabled = true;

    /**
     * Checks whether the user is enabled.
     *
     * Internally, if this method returns false, the authentication system
     * will throw a DisabledException and prevent login.
     *
     * @return bool    true if the user is enabled, false otherwise
     *
     * @see DisabledException
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * @param boolean $enabled
     *
     * @return boolean $enabled
     */
    public function setEnabled($enabled)
    {
        $this->enabled = (boolean) $enabled;

        return $this;
    }

    /**
     * @var \DateTime
     * @ORM\Column(name="last_login", type="datetime", nullable=true)
     */
    private $lastLogin;

    /**
     * @return \DateTime $lastLogin
     */
    public function getLastLogin()
    {
        return $this->lastLogin;
    }

    /**
     * @param \DateTime $lastLogin
     *
     * @return \DateTime $lastLogin
     */
    public function setLastLogin($lastLogin)
    {
        $this->lastLogin = $lastLogin;

        return $this;
    }

    /**
     * @ORM\Column(name="confirmation_token", type="string", unique=true, nullable=true)
     * @var string
     */
    protected $confirmationToken;

    /**
     * Get random string sent to the user email address in order to verify it.
     *
     * @return string
     */
    public function getConfirmationToken()
    {
        return $this->confirmationToken;
    }

    /**
     * Set random string sent to the user email address in order to verify it.
     *
     * @param string $confirmationToken
     */
    public function setConfirmationToken($confirmationToken)
    {
        $this->confirmationToken = $confirmationToken;

        return $this;
    }

    /**
     * @ORM\Column(name="password_requested_at", type="datetime", nullable=true)
     * @var \DateTime
     */
    protected $passwordRequestedAt;

    /**
     * Sets the timestamp that the user requested a password reset.
     *
     * @param \DateTime|null $date
     */
    public function setPasswordRequestedAt(\DateTime $date = null)
    {
        $this->passwordRequestedAt = $date;

        return $this;
    }
    /**
     * Gets the timestamp that the user requested a password reset.
     *
     * @return null|\DateTime
     */
    public function getPasswordRequestedAt()
    {
        return $this->passwordRequestedAt;
    }

    /**
     * Check if password reset request has expired.
     *
     * @param  int $ttl Password request time to live.
     *
     * @return boolean
     */
    public function isPasswordRequestNonExpired($ttl)
    {
        return $this->getPasswordRequestedAt() instanceof \DateTime &&
        $this->getPasswordRequestedAt()->getTimestamp() + $ttl > time();
    }

    /**
     * @ORM\ManyToMany(targetEntity="RZ\Roadiz\Core\Entities\Role")
     * @ORM\JoinTable(name="users_roles",
     *      joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="role_id", referencedColumnName="id", onDelete="CASCADE")}
     * )
     */
    private $roles;

    /**
     * Names of current User roles
     * to be compatible with symfony security scheme
     * @var Array
     */
    private $rolesNames = null;

    /**
     * Get roles entities
     * @return ArrayCollection
     */
    public function getRolesEntities()
    {
        return $this->roles;
    }

    /**
     * Get roles names as a simple array, combining groups roles.
     *
     * @return array
     */
    public function getRoles()
    {

        $this->rolesNames = [];
        foreach ($this->getRolesEntities() as $role) {
            if (null !== $role) {
                $this->rolesNames[] = $role->getName();
            }
        }

        foreach ($this->getGroups() as $group) {
            // User roles > Groups roles
            $this->rolesNames = array_merge($group->getRoles(), $this->rolesNames);
        }

        // we need to make sure to have at least one role
        $this->rolesNames[] = Role::ROLE_DEFAULT;
        $this->rolesNames = array_unique($this->rolesNames);

        return $this->rolesNames;
    }

    /**
     * Add a role object to current user.
     * @param RZ\Roadiz\Core\Entities\Role $role
     *
     * @return $this
     */
    public function addRole(Role $role)
    {
        if (!$this->getRolesEntities()->contains($role)) {
            $this->getRolesEntities()->add($role);
        }

        return $this;
    }

    /**
     * Remove role from current user.
     * @param RZ\Roadiz\Core\Entities\Role $role
     *
     * @return $this
     */
    public function removeRole(Role $role)
    {
        if ($this->getRolesEntities()->contains($role)) {
            $this->getRolesEntities()->removeElement($role);
        }

        return $this;
    }

    /**
     * Removes sensitive data from the user.
     *
     * @return void
     */
    public function eraseCredentials()
    {
        $this->setPlainPassword('');
    }

    /**
     * @ORM\ManyToMany(targetEntity="RZ\Roadiz\Core\Entities\Group", inversedBy="users")
     * @ORM\JoinTable(name="users_groups",
     *      joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="group_id", referencedColumnName="id")}
     * )
     * @var ArrayCollection
     */
    private $groups;

    /**
     * @return ArrayCollection
     */
    public function getGroups()
    {
        return $this->groups;
    }

    /**
     * Insert user into group.
     * @param RZ\Roadiz\Core\Entities\Group $group
     *
     * @return $this
     */
    public function addGroup(Group $group)
    {
        if (!$this->getGroups()->contains($group)) {
            $this->getGroups()->add($group);
        }

        return $this;
    }

    /**
     * Remove user from group
     * @param RZ\Roadiz\Core\Entities\Group  $group
     *
     * @return $this
     */
    public function removeGroup(Group $group)
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
     */
    public function getGroupNames()
    {
        $names = [];
        foreach ($this->getGroups() as $group) {
            $names[] = $group->getName();
        }

        return $names;
    }

    /**
     * @var boolean
     * @ORM\Column(type="boolean", nullable=false, options={"default" = false})
     */
    private $expired = false;

    /**
     * Return strictly forced expiration status.
     *
     * @return boolean
     */
    public function getExpired()
    {
        return $this->expired;
    }

    /**
     * @param boolean $expired
     */
    public function setExpired($expired)
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
     */
    public function isAccountNonExpired()
    {
        if ($this->expiresAt !== null &&
            $this->expiresAt->getTimestamp() < time()) {
            return false;
        }

        return !$this->expired;
    }

    /**
     * @var boolean
     * @ORM\Column(type="boolean", nullable=false, options={"default" = false})
     */
    private $locked = false;

    /**
     * Checks whether the user is locked.
     *
     * Internally, if this method returns false, the authentication system
     * will throw a LockedException and prevent login.
     *
     * @return bool true if the user is not locked, false otherwise
     *
     * @see LockedException
     */
    public function isAccountNonLocked()
    {
        return !$this->locked;
    }

    public function setLocked($locked)
    {
        $this->locked = (boolean) $locked;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param User $user
     *
     * @return boolean
     */
    public function equals(User $user)
    {
        return (
            $this->username == $user->getUsername() ||
            $this->email == $user->getEmail()
        );
    }

    /**
     * @ORM\Column(name="credentials_expires_at", type="datetime", nullable=true)
     * @var \DateTime
     */
    private $credentialsExpiresAt;

    /**
     * @param \DateTime $date
     *
     * @return User
     */
    public function setCredentialsExpiresAt(\DateTime $date = null)
    {
        $this->credentialsExpiresAt = $date;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCredentialsExpiresAt()
    {
        return $this->credentialsExpiresAt;
    }

    /**
     * @var boolean
     * @ORM\Column(type="boolean", name="credentials_expired", nullable=false, options={"default" = false})
     */
    private $credentialsExpired = false;

    /**
     * Return strictly forced credentials expiration status.
     *
     * @return boolean
     */
    public function getCredentialsExpired()
    {
        return $this->credentialsExpired;
    }

    /**
     * @param boolean $newcredentialsExpired
     */
    public function setCredentialsExpired($newcredentialsExpired)
    {
        $this->credentialsExpired = $newcredentialsExpired;

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
    public function isCredentialsNonExpired()
    {
        if ($this->credentialsExpiresAt !== null &&
            $this->credentialsExpiresAt->getTimestamp() < time()) {
            return false;
        }

        return !$this->credentialsExpired;
    }

    /**
     * @ORM\Column(name="expires_at", type="datetime", nullable=true)
     * @var \DateTime
     */
    private $expiresAt;

    /**
     * @param \DateTime $date
     *
     * @return User
     */
    public function setExpiresAt(\DateTime $date = null)
    {
        $this->expiresAt = $date;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getExpiresAt()
    {
        return $this->expiresAt;
    }

    /**
     * @ORM\ManyToOne(targetEntity="RZ\Roadiz\Core\Entities\Node")
     * @ORM\JoinColumn(name="chroot_id", referencedColumnName="id", onDelete="SET NULL")
     *
     * @var RZ\Roadiz\Core\Entities\Node
     */
    private $chroot;

    /**
     * @param RZ\Roadiz\Core\Entities\Node $chroot
     *
     * @return RZ\Roadiz\Core\Entities\Node
     */
    public function setChroot(Node $chroot = null)
    {
        $this->chroot = $chroot;

        return $this;
    }

    /**
     * @return RZ\Roadiz\Core\Entities\Node
     */
    public function getChroot()
    {
        return $this->chroot;
    }

    /**
     * @ORM\PrePersist
     */
    public function prePersist()
    {
        parent::prePersist();

        $saltGenerator = new SaltGenerator();
        $this->salt = $saltGenerator->generateSalt();

        /*
         * If no plain password is present, we must generate one
         */
        if ($this->getPlainPassword() == '') {
            $passwordGenerator = new PasswordGenerator();
            $this->setPlainPassword($passwordGenerator->generatePassword(12));
        }

        $this->getHandler()->encodePassword();
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->roles = new ArrayCollection();
        $this->groups = new ArrayCollection();
    }

    /**
     * @return RZ\Roadiz\Core\Handlers\UserHandler
     */
    public function getHandler()
    {
        return new UserHandler($this);
    }

    /**
     * @return RZ\Roadiz\Core\Viewers\UserViewer
     */
    public function getViewer()
    {
        return new UserViewer($this);
    }

    /**
     * @return string $text
     */
    public function __toString()
    {
        $text = $this->getUsername() . ' <' . $this->getEmail() . '>' . PHP_EOL;
        $text .= "Roles: " . implode(', ', $this->getRoles());

        return $text;
    }
}
