<?php
declare(strict_types=1);

namespace RZ\Roadiz\OpenId\User;

use Lcobucci\JWT\Token;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use JMS\Serializer\Annotation as Serializer;

/**
 * @package RZ\Roadiz\Core\Authentication
 * @see https://openid.net/specs/openid-connect-core-1_0.html#StandardClaims
 */
class OpenIdAccount implements UserInterface, EquatableInterface
{
    /**
     * @var array<string>
     * @Serializer\Groups({"user"})
     */
    protected $roles;
    /**
     * @var string|null
     * @Serializer\Groups({"user"})
     */
    protected $issuer;
    /**
     * @var string
     * @Serializer\Groups({"user"})
     */
    protected $email;
    /**
     * @var string|null
     * @Serializer\Groups({"user"})
     */
    protected $name;
    /**
     * @var string|null
     * @Serializer\Groups({"user"})
     */
    protected $nickname;
    /**
     * @var string|null
     * @Serializer\Groups({"user"})
     */
    protected $website;
    /**
     * @var string|null
     * @Serializer\Groups({"user"})
     */
    protected $locale;
    /**
     * @var string|null
     * @Serializer\Groups({"user"})
     */
    protected $phoneNumber;
    /**
     * @var array|null
     * @Serializer\Groups({"user"})
     */
    protected $address;
    /**
     * @var string|null
     * @Serializer\Groups({"user"})
     */
    protected $familyName;
    /**
     * @var string|null
     * @Serializer\Groups({"user"})
     */
    protected $middleName;
    /**
     * @var string|null
     * @Serializer\Groups({"user"})
     */
    protected $givenName;
    /**
     * @var string|null
     * @Serializer\Groups({"user"})
     */
    protected $picture;
    /**
     * @var string|null
     * @Serializer\Groups({"user"})
     */
    protected $profile;
    /**
     * @var Token
     */
    protected $jwtToken;

    /**
     * OpenIdAccount constructor.
     *
     * @param string $email
     * @param array  $roles
     * @param Token  $jwtToken
     */
    public function __construct(
        string $email,
        array $roles,
        Token $jwtToken
    ) {
        $this->roles = $roles;
        $this->email = $email;
        $this->jwtToken = $jwtToken;
        if (!($jwtToken instanceof Token\Plain)) {
            throw new \InvalidArgumentException('Token must be an instance of ' . Token\Plain::class);
        }
        /*
         * https://openid.net/specs/openid-connect-core-1_0.html#StandardClaims
         */
        $claims = $jwtToken->claims();
        $this->name = $claims->has('name') ? (string) $claims->get('name') : null;
        $this->issuer = $claims->has('iss') ? (string) $claims->get('iss') : null;
        $this->givenName = $claims->has('given_name') ? (string) $claims->get('given_name') : null;
        $this->familyName = $claims->has('family_name') ? (string) $claims->get('family_name') : null;
        $this->middleName = $claims->has('middle_name') ? (string) $claims->get('middle_name') : null;
        $this->nickname = $claims->has('nickname') ? (string) $claims->get('nickname') : null;
        $this->profile = $claims->has('profile') ? (string) $claims->get('profile') : null;
        $this->picture = $claims->has('picture') ? (string) $claims->get('picture') : null;
        $this->locale = $claims->has('locale') ? (string) $claims->get('locale') : null;
        $this->phoneNumber = $claims->has('phone_number') ? (string) $claims->get('phone_number') : null;
        $this->address = $claims->has('address') ? $claims->get('address') : null;
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
    public function getFamilyName(): ?string
    {
        return $this->familyName;
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

    /**
     * @return string|null
     */
    public function getNickname(): ?string
    {
        return $this->nickname;
    }

    /**
     * @return string|null
     */
    public function getWebsite(): ?string
    {
        return $this->website;
    }

    /**
     * @return string|null
     */
    public function getLocale(): ?string
    {
        return $this->locale;
    }

    /**
     * @return string|null
     */
    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    /**
     * @return array|null
     */
    public function getAddress(): ?array
    {
        return $this->address;
    }

    /**
     * @return string|null
     */
    public function getMiddleName(): ?string
    {
        return $this->middleName;
    }

    /**
     * @return string|null
     */
    public function getProfile(): ?string
    {
        return $this->profile;
    }

    /**
     * @return Token
     */
    public function getJwtToken(): Token
    {
        return $this->jwtToken;
    }

    /**
     * @return string|null
     */
    public function getIssuer(): ?string
    {
        return $this->issuer;
    }

    public function isEqualTo(UserInterface $user)
    {
        if (!$user instanceof OpenIdAccount) {
            return false;
        }

        if ($this->getEmail() !== $user->getEmail()) {
            return false;
        }

        if (array_diff($this->getRoles(), $user->getRoles())) {
            return false;
        }

        if ($this->getJwtToken() !== $user->getJwtToken()) {
            return false;
        }

        return true;
    }
}
