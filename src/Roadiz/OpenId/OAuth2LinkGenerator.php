<?php
declare(strict_types=1);

namespace RZ\Roadiz\OpenId;

use RZ\Roadiz\OpenId\Authentication\OAuth2AuthenticationListener;
use RZ\Roadiz\OpenId\Exception\DiscoveryNotAvailableException;
use RZ\Roadiz\Random\TokenGenerator;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class OAuth2LinkGenerator
{
    protected ?Discovery $discovery;
    protected CsrfTokenManagerInterface $csrfTokenManager;
    protected ParameterBag $settingsBag;

    /**
     * @param Discovery|null $discovery
     * @param CsrfTokenManagerInterface $csrfTokenManager
     * @param ParameterBag $settingsBag
     */
    public function __construct(
        ?Discovery $discovery,
        CsrfTokenManagerInterface $csrfTokenManager,
        ParameterBag $settingsBag
    ) {
        $this->discovery = $discovery;
        $this->csrfTokenManager = $csrfTokenManager;
        $this->settingsBag = $settingsBag;
    }

    /**
     * @param Request $request
     *
     * @return bool
     */
    public function isSupported(Request $request): bool
    {
        return null !== $this->discovery;
    }

    /**
     * @param Request $request
     * @param string  $redirectUri
     * @param array   $state
     * @param string  $responseType
     *
     * @return string
     */
    public function generate(Request $request, string $redirectUri, array $state = [], string $responseType = 'code'): string
    {
        if (null !== $this->discovery &&
            in_array($responseType, $this->discovery->get('response_types_supported', []))) {
            $customScopes = $this->settingsBag->get('openid_scopes', null);
            if (null !== $customScopes && !empty($customScopes)) {
                $customScopes = array_intersect(
                    explode(' ', $customScopes),
                    $this->discovery->get('scopes_supported')
                );
            } else {
                $customScopes = $this->discovery->get('scopes_supported');
            }
            $stateToken = $this->csrfTokenManager->getToken(OAuth2AuthenticationListener::OAUTH_STATE_TOKEN);
            return $this->discovery->get('authorization_endpoint') . '?' . http_build_query([
                'response_type' => 'code',
                'hd' => $this->settingsBag->get('openid_hd', null),
                'state' => http_build_query(array_merge($state, [
                    'token' => $stateToken->getValue()
                ])),
                'nonce' => (new TokenGenerator())->generateToken(),
                'login_hint' => $request->get('email', null),
                'scope' => implode(' ', $customScopes),
                'client_id' => $this->settingsBag->get('oauth_client_id', null),
                'redirect_uri' => $redirectUri,
            ]);
        }
        throw new DiscoveryNotAvailableException(
            'OpenID discovery is not well configured or response_type is not supported by your identity provider'
        );
    }
}
