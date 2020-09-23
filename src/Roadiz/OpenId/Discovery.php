<?php
declare(strict_types=1);

namespace RZ\Roadiz\OpenId;

use Doctrine\Common\Cache\CacheProvider;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Jose\Component\Core\JWKSet;
use RZ\Roadiz\Core\Bags\LazyParameterBag;

/**
 * @package RZ\Roadiz\OpenId
 * @see https://accounts.google.com/.well-known/openid-configuration
 */
class Discovery extends LazyParameterBag
{
    const CACHE_KEY = Discovery::class . '_parameters';

    /**
     * @var string
     */
    protected $discoveryUri;
    /**
     * @var CacheProvider|null
     */
    protected $cacheProvider;
    /**
     * @var array|null
     */
    private $jwksData;

    /**
     * Discovery constructor.
     *
     * @param string             $discoveryUri
     * @param CacheProvider|null $cacheProvider
     */
    public function __construct(string $discoveryUri, ?CacheProvider $cacheProvider = null)
    {
        parent::__construct();
        $this->ready = false;
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
        return \extension_loaded('gmp') && $this->has('jwks_uri');
    }

    /**
     * @return JWKSet|null
     */
    public function getJWKSet(): ?JWKSet
    {
        $jwksData = $this->getJWKData();
        if (null !== $jwksData) {
            return JWKSet::createFromKeyData($jwksData);
        }
        return null;
    }

    /**
     * @return array|null
     */
    public function getJWKData(): ?array
    {
        if (null === $this->jwksData && $this->has('jwks_uri')) {
            $cacheKey = md5($this->get('jwks_uri'));
            if (null !== $this->cacheProvider && $this->cacheProvider->contains($cacheKey)) {
                $this->jwksData = $this->cacheProvider->fetch($cacheKey);
            } else {
                $client = new Client([
                    // You can set any number of default request options.
                    'timeout'  => 2.0,
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