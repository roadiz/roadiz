<?php 

namespace RZ\Renzo\Core\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use RZ\Renzo\Core\AbstractEntities\Human;	
use RZ\Renzo\Core\Entities\Role;
use RZ\Renzo\Core\Handlers\UserHandler;

use Symfony\Component\Security\Core\User\AdvancedUserInterface;
/**
 * @Entity
 * @Table(name="users")
 * @HasLifecycleCallbacks
 */
class User extends Human implements AdvancedUserInterface
{
	const ANONYMOUS =           0;
	const SUPER_ADMINISTRATOR = 10;
	const BACKEND_USER =        20;
	const FRONTEND_USER =       30;

	/**
	 * @Column(type="string", unique=true)
	 */
	private $username;
	/**
	 * @return
	 */
	public function getUsername() {
	    return $this->username;
	}
	/**
	 * @param $username 
	 */
	public function setUsername($username) {
	    $this->username = $username;
	
	    return $this;
	}

	/**
     * The salt to use for hashing
     *
     * @var string
     * @ORM\Column(name="salt", type="string")     
     */
    private $salt;
    /**
     * @return string
     */
    public function getSalt() {
        return $this->salt;
    }
    /**
     * @param $newsalt
     */
    public function setSalt($salt) {
        $this->salt = $salt;
    
        return $this;
    }

	/**
	 * Encrypted password.
	 * 
	 * @Column(type="string", nullable=false)
	 */
	private $password;
	/**
	 * @return
	 */
	public function getPassword() {
	    return $this->password;
	}
	/**
	 * @param $password 
	 */
	public function setPassword($password) {
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
     * @return [type] [description]
     */
    public function getPlainPassword() {
        return $this->plainPassword;
    }
    /**
     * @param $newplainPassword
     */
    public function setPlainPassword($plainPassword) {
        $this->plainPassword = $plainPassword;
    
        return $this;
    }

    /**
     * @var boolean
     * @Column(type="boolean")         
     */
    protected $enabled = 1;
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
    public function isEnabled() {
        return $this->enabled;
    }
    /**
     * @param $newenabled
     */
    public function setEnabled($enabled) {
        $this->enabled = (boolean)$enabled;
    
        return $this;
    }

	/**
	 * @Column(type="integer")
	 */
	private $rank = User::ANONYMOUS;
	/**
	 * @return [type] [description]
	 */
	public function getRank() {
	    return $this->rank;
	}
	/**
	 * @param [type] $newnodeName [description]
	 */
	public function setRank($rank) {
	    $this->rank = $rank;
	
	    return $this;
	}

	/**
     * @var \DateTime
     * @Column(name="last_login", type="datetime", nullable=true)       
     */
    private $lastLogin;
    /**
     * @return \DateTime
     */
    public function getLastLogin() {
        return $this->lastLogin;
    }
    /**
     * @param \DateTime $newlastLogin
     */
    public function setLastLogin($lastLogin) {
        $this->lastLogin = $lastLogin;
    
        return $this;
    }
	
	/**
	 * @ManyToMany(targetEntity="RZ\Renzo\Core\Entities\Role")
	 * @JoinTable(name="users_roles",
     *      joinColumns={@JoinColumn(name="user_id", referencedColumnName="id")},
     *      inverseJoinColumns={@JoinColumn(name="role_id", referencedColumnName="id")}
     * )
	 */
	private $roles;
	/**
	 * @return ArrayCollection
	 */
	public function getRoles() {
	    return $this->roles;
	}

	/**
	 * Removes sensitive data from the user.
	 * @return void
	 */
	public function eraseCredentials()
	{
		$this->setPlainPassword('');
	}

	/**
	 * @ManyToMany(targetEntity="RZ\Renzo\Core\Entities\Permission")
	 * @JoinTable(name="users_permissions",
     *      joinColumns={@JoinColumn(name="user_id", referencedColumnName="id")},
     *      inverseJoinColumns={@JoinColumn(name="permission_id", referencedColumnName="id")}
     * )
	 */
	private $permissions;
	/**
	 * @return ArrayCollection
	 */
	public function getPermissions() {
	    return $this->permissions;
	}

	/**
     * @var boolean
     * @Column(type="boolean")         
     */
	private $expired = false;
	/**
     * Checks whether the user's account has expired.
     *
     * Internally, if this method returns false, the authentication system
     * will throw an AccountExpiredException and prevent login.
     *
     * @return bool    true if the user's account is non expired, false otherwise
     *
     * @see AccountExpiredException
     */
    public function isAccountNonExpired(){
    	return !$this->expired;
    }

    /**
     * @var boolean
     * @Column(type="boolean")         
     */
    private $locked = false;
    /**
     * Checks whether the user is locked.
     *
     * Internally, if this method returns false, the authentication system
     * will throw a LockedException and prevent login.
     *
     * @return bool    true if the user is not locked, false otherwise
     *
     * @see LockedException
     */
    public function isAccountNonLocked(){
    	return !$this->locked;
    }

    /**
     * @var boolean
     * @Column(type="boolean", name="credentials_expired")         
     */
    private $credentialsExpired = false;
    /**
     * Checks whether the user's credentials (password) has expired.
     *
     * Internally, if this method returns false, the authentication system
     * will throw a CredentialsExpiredException and prevent login.
     *
     * @return bool    true if the user's credentials are non expired, false otherwise
     *
     * @see CredentialsExpiredException
     */
    public function isCredentialsNonExpired(){
    	return !$this->credentialsExpired;
    }

    /** 
     * @PrePersist
     */
    public function prePersist()
    {   
        parent::prePersist();
        /*
         * If a plain password is present, we must encode it before persisting entity
         */
        if ($this->getPlainPassword() != '') {
            $this->getHandler()->encodePassword();
        }
        else {
            throw new Exception("No password has been filled for user.", 1);   
        }
    }
	/** 
     * @PrePersist
     */
    public function preUpdate()
    {   
        parent::preUpdate();
        /*
         * If a plain password is present, we must encode it before persisting entity
         */
        if ($this->getPlainPassword() != '') {
            $this->getHandler()->encodePassword();
        }
        else {
            // Do not change password if no plain password filled
        }
    }

	public function __construct()
    {
    	parent::__construct();

    	$this->roles = new ArrayCollection();
    	$this->permissions = new ArrayCollection();

    	$this->salt = sha1(uniqid(rand(), true));
    }

    /**
     * @return RZ\Renzo\Core\Handlers\UserHandler
     */
    public function getHandler()
    {
    	return new UserHandler( $this );
    }
}