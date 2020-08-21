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
        /*
         * https://openid.net/specs/openid-connect-core-1_0.html#StandardClaims
         */
        $this->name = $jwtToken->hasClaim('name') ? (string) $jwtToken->getClaim('name') : null;
        $this->issuer = $jwtToken->hasClaim('iss') ? (string) $jwtToken->getClaim('iss') : null;
        $this->givenName = $jwtToken->hasClaim('given_name') ? (string) $jwtToken->getClaim('given_name') : null;
        $this->familyName = $jwtToken->hasClaim('family_name') ? (string) $jwtToken->getClaim('family_name') : null;
        $this->middleName = $jwtToken->hasClaim('middle_name') ? (string) $jwtToken->getClaim('middle_name') : null;
        $this->nickname = $jwtToken->hasClaim('nickname') ? (string) $jwtToken->getClaim('nickname') : null;
        $this->profile = $jwtToken->hasClaim('profile') ? (string) $jwtToken->getClaim('profile') : null;
        $this->picture = $jwtToken->hasClaim('picture') ? (string) $jwtToken->getClaim('picture') : null;
        $this->locale = $jwtToken->hasClaim('locale') ? (string) $jwtToken->getClaim('locale') : null;
        $this->phoneNumber = $jwtToken->hasClaim('phone_number') ? (string) $jwtToken->getClaim('phone_number') : null;
        $this->address = $jwtToken->hasClaim('address') ? $jwtToken->getClaim('address') : null;
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
