<?php
declare(strict_types=1);

namespace RZ\Roadiz\OpenId;

use Doctrine\Common\Cache\CacheProvider;
use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * @package RZ\Roadiz\OpenId
 * @see https://accounts.google.com/.well-known/openid-configuration
 */
class Discovery extends ParameterBag
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
     * @var bool
     */
    private $ready;

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
            $client = new Client([
                // You can set any number of default request options.
                'timeout'  => 2.0,
            ]);
            $response = $client->get($this->discoveryUri);
            $parameters = json_decode($response->getBody()->getContents(), true);
            if (null !== $this->cacheProvider) {
                $this->cacheProvider->save(static::CACHE_KEY, $parameters);
            }
        }

        $this->parameters = [];
        foreach ($parameters as $key => $parameter) {
            $this->parameters[$key] = $parameter;
        }
        $this->ready = true;
    }

    /**
     * @param string $key
     * @param mixed $default
     * @return bool|mixed
     */
    public function get($key, $default = false)
    {
        if (!$this->ready) {
            $this->populateParameters();
        }

        return parent::get($key, $default);
    }

    /**
     * @return array
     */
    public function all(): array
    {
        if (!$this->ready) {
            $this->populateParameters();
        }

        return parent::all();
    }

    public function reset(): void
    {
        $this->parameters = [];
        $this->ready = false;
    }
}
