<?php
declare(strict_types=1);

namespace RZ\Roadiz\OpenId;

use CoderCat\JWKToPEM\Exception\Base64DecodeException;
use CoderCat\JWKToPEM\Exception\JWKConverterException;
use CoderCat\JWKToPEM\JWKConverter;
use Doctrine\Common\Cache\CacheProvider;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use RZ\Roadiz\Bag\LazyParameterBag;

/**
 * @package RZ\Roadiz\OpenId
 * @see https://accounts.google.com/.well-known/openid-configuration
 */
class Discovery extends LazyParameterBag
{
    const CACHE_KEY = Discovery::class . '_parameters';

    protected string $discoveryUri;
    protected ?CacheProvider $cacheProvider;
    protected ?array $jwksData = null;

    /**
     * @param string             $discoveryUri
     * @param CacheProvider|null $cacheProvider
     */
    public function __construct(string $discoveryUri, ?CacheProvider $cacheProvider = null)
    {
        parent::__construct();
        $this->discoveryUri = $discoveryUri;
        $this->cacheProvider = $cacheProvider;
    }

    protected function populateParameters()
    {
        if (null !== $this->cacheProvider && $this->cacheProvider->contains(static::CACHE_KEY)) {
            $parameters = $this->cacheProvider->fetch(static::CACHE_KEY);
        } else {
            try {
                $client = new Client([
                    // You can set any number of default request options.
                    'timeout'  => 2.0,
                ]);
                $response = $client->get($this->discoveryUri);
                $parameters = json_decode($response->getBody()->getContents(), true);
                if (null !== $this->cacheProvider) {
                    $this->cacheProvider->save(static::CACHE_KEY, $parameters);
                }
            } catch (RequestException $exception) {
                return;
            }
        }

        $this->parameters = [];
        foreach ($parameters as $key => $parameter) {
            $this->parameters[$key] = $parameter;
        }
        $this->ready = true;
    }

    /**
     * @return bool
     */
    public function canVerifySignature(): bool
    {
        return $this->has('jwks_uri');
    }

    /**
     * @return array<string>|null
     * @throws Base64DecodeException
     * @throws JWKConverterException
     * @throws GuzzleException
     * @see https://auth0.com/docs/tokens/json-web-tokens/json-web-key-sets
     */
    public function getPems(): ?array
    {
        $jwksData = $this->getJwksData();
        if (null !== $jwksData && isset($jwksData['keys'])) {
            $converter = new JWKConverter();
            return $converter->multipleToPEM($jwksData['keys']);
        }
        return null;
    }

    /**
     * @return array|null
     * @throws GuzzleException
     */
    protected function getJwksData(): ?array
    {
        if (null === $this->jwksData && $this->has('jwks_uri')) {
            $cacheKey = 'jwks_uri_' . md5($this->get('jwks_uri'));
            if (null !== $this->cacheProvider && $this->cacheProvider->contains($cacheKey)) {
                $this->jwksData = $this->cacheProvider->fetch($cacheKey);
            } else {
                $client = new Client([
                    // You can set any number of default request options.
                    'timeout'  => 3.0,
                ]);
                $response = $client->get($this->get('jwks_uri'));
                $this->jwksData = json_decode($response->getBody()->getContents(), true);
                if (null !== $this->cacheProvider) {
                    $this->cacheProvider->save($cacheKey, $this->jwksData, 3600);
                }
            }
        }
        return $this->jwksData;
    }
}
