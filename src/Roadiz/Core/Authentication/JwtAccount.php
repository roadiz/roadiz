<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Authentication;

use Symfony\Component\Security\Core\User\UserInterface;

class JwtAccount implements UserInterface
{
    /**
     * @var array<string>
     */
    protected $roles;
    /**
     * @var string
     */
    protected $email;
    /**
     * @var string|null
     */
    protected $name;
    /**
     * @var string|null
     */
    protected $firstName;
    /**
     * @var string|null
     */
    protected $givenName;
    /**
     * @var string|null
     */
    protected $picture;

    /**
     * JwtAccount constructor.
     *
     * @param string      $email
     * @param array       $roles
     * @param string|null $name
     * @param string|null $firstName
     * @param string|null $givenName
     * @param string|null $picture
     */
    public function __construct(
        string $email,
        array $roles,
        ?string $name,
        ?string $firstName,
        ?string $givenName,
        ?string $picture
    ) {
        $this->roles = $roles;
        $this->email = $email;
        $this->name = $name;
        $this->firstName = $firstName;
        $this->givenName = $givenName;
        $this->picture = $picture;
    }

    /**
     * @inheritDoc
     */
    public function getRoles()
    {
        return $this->roles;
    }

    /**
     * @inheritDoc
     */
    public function getPassword()
    {
        return '';
    }

    /**
     * @inheritDoc
     */
    public function getSalt()
    {
        return '';
    }

    /**
     * @inheritDoc
     */
    public function getUsername()
    {
        return $this->email;
    }

    /**
     * @inheritDoc
     */
    public function eraseCredentials()
    {
        return;
    }

    /**
     * @return string
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @return string
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    /**
     * @return string
     */
    public function getGivenName(): ?string
    {
        return $this->givenName;
    }

    /**
     * @return string
     */
    public function getPicture(): ?string
    {
        return $this->picture;
    }
}
