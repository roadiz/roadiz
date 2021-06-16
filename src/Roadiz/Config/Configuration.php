<?php
declare(strict_types=1);

namespace RZ\Roadiz\Config;

use RZ\Roadiz\Core\KernelInterface;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    const INHERITANCE_TYPE_JOINED = 'joined';
    const INHERITANCE_TYPE_SINGLE_TABLE = 'single_table';

    protected KernelInterface $kernel;

    /**
     * @param KernelInterface $kernel
     */
    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder('roadiz');
        $root = $builder->getRootNode();

        $root->addDefaultsIfNotSet()
            ->children()
            ->scalarNode('appNamespace')
                ->defaultValue('roadiz_app')
            ->end()
            ->scalarNode('timezone')
                ->defaultValue('Europe/Paris')
            ->end()
            ->arrayNode('doctrine')->addDefaultsIfNotSet()
                ->children()
                    ->enumNode('driver')
                        ->isRequired()
                        ->defaultValue('pdo_mysql')
                        ->values(['pdo_mysql', 'pdo_sqlite', 'pdo_pgsql'])
                    ->end()
                    ->scalarNode('host')->defaultValue('localhost')->end()
                    ->scalarNode('user')->end()
                    ->scalarNode('password')->end()
                    ->scalarNode('application_name')->end()
                    ->scalarNode('dbname')->end()
                    ->scalarNode('unix_socket')->end()
                    ->scalarNode('server_version')->defaultValue('5.7')->end()
                    ->scalarNode('port')->defaultValue(null)->end()
                    ->scalarNode('path')->end()
                    ->booleanNode('logging')->defaultValue(false)->end()
                    ->booleanNode('profiling')->defaultValue(false)->end()
                    ->scalarNode('charset')->end()
                    ->arrayNode('options')
                        ->useAttributeAsKey('key')
                        ->prototype('scalar')->end()
                    ->end()
                    ->arrayNode('mapping_types')
                        ->useAttributeAsKey('name')
                        ->prototype('scalar')->end()
                    ->end()
                    ->arrayNode('default_table_options')
                        ->info("This option is used by the schema-tool and affects generated SQL. Possible keys include 'charset','collate', and 'engine'.")
                        ->useAttributeAsKey('name')
                        ->prototype('scalar')->end()
                    ->end()
                ->end()
            ->end()
            ->arrayNode('cacheDriver')
                ->addDefaultsIfNotSet()
                ->children()
                    ->enumNode('type')
                        ->values([null, 'array', 'apcu', 'apc', 'xcache', 'memcache', 'memcached', 'redis', 'php', 'file'])
                        ->defaultNull()
                        ->info('If null or empty, Roadiz will try to detect best cache driver available')
                    ->end()
                    ->scalarNode('host')->defaultNull()->end()
                    ->scalarNode('port')->defaultNull()->end()
                ->end()
            ->end()
            ->arrayNode('security')
                ->addDefaultsIfNotSet()
                ->children()
                    ->enumNode('algorithm')
                        ->values(['sha512', 'pbkdf2', 'bcrypt', 'argon2i'])
                        ->defaultValue('sha512')
                        ->info('User encoder algorithm to use.')
                    ->end()
                    ->scalarNode('encode_as_base64')
                        ->defaultValue(true)
                        ->info('When using sha512 or pbkdf2 algorithm')
                    ->end()
                    ->scalarNode('private_key_path')
                        ->beforeNormalization()
                            ->ifString()
                            ->then(function ($v) {
                                return $this->resolveKernelVars($v);
                            })
                        ->end()
                        ->defaultValue('conf/default.key')
                        ->info('Asymmetric cryptographic key location.')
                    ->end()
                    ->scalarNode('iterations')
                        ->defaultValue(5000)
                        ->info('When using sha512 or pbkdf2 algorithm')
                    ->end()
                    ->scalarNode('hash_algorithm')
                        ->defaultValue('sha512')
                        ->info('When using pbkdf2 algorithm')
                    ->end()
                    ->scalarNode('key_length')
                        ->defaultValue(40)
                        ->info('When using pbkdf2 algorithm')
                    ->end()
                    ->scalarNode('cost')
                        ->defaultValue(15)
                        ->info('When using bcrypt algorithm')
                    ->end()
                    ->scalarNode('secret')
                        ->defaultValue('change#this#secret#very#important')
                        ->isRequired()
                        ->cannotBeEmpty()
                    ->end()
                    ->scalarNode('session_name')
                        ->info(
                            <<<EOD
Name of the session (used as cookie name).
http://php.net/session.name
EOD
                        )
                        ->defaultValue('roadiz_token')
                        ->cannotBeEmpty()
                        ->beforeNormalization()
                            ->always()
                            ->then(function (string $v) {
                                return strtolower(preg_replace('#[^a-z^A-Z^_]#', '_', trim($v)));
                            })
                        ->end()
                    ->end()
                    ->booleanNode('session_cookie_secure')
                        ->info(
                            <<<EOD
Enable session cookie_secure ONLY if your website is served with HTTPS only
http://php.net/session.cookie-secure
EOD
                        )
                        ->defaultValue(false)
                    ->end()
                    ->booleanNode('session_cookie_httponly')
                        ->info(
                            <<<EOD
Whether or not to add the httpOnly flag to the cookie, which makes it inaccessible to browser scripting languages such as JavaScript.
http://php.net/session.cookie-httponly
EOD
                        )
                        ->defaultValue(true)
                    ->end()
                ->end()
            ->end()
            ->arrayNode('entities')
                ->prototype('scalar')
                    ->cannotBeEmpty()
                ->end()
                ->info('Doctrine entities search paths. Append yours here if you want to create custom entities in your theme.')
            ->end()
            ->scalarNode('rememberMeLifetime')
                ->defaultValue(2592000)
                ->cannotBeEmpty()
                ->info('Lifetime of remember-me cookie in seconds (default 30 days)')
            ->end()
            ->arrayNode('additionalServiceProviders')
                ->prototype('scalar')
                    ->cannotBeEmpty()
                ->end()
            ->end()
            ->arrayNode('additionalCommands')
                ->prototype('scalar')
                    ->cannotBeEmpty()
                ->end()
            ->end()
            ->append($this->addMonologNode())
            ->append($this->addMailerNode())
            ->append($this->addAssetsNode())
            ->append($this->addSolrNode())
            ->append($this->addReverseProxyCacheNode())
            ->append($this->addThemesNode())
            ->append($this->addInheritanceNode())
            ->append($this->addMessengerNode())
        ;
        return $builder;
    }

    /**
     * @return ArrayNodeDefinition|NodeDefinition
     */
    protected function addInheritanceNode()
    {
        $builder = new TreeBuilder('inheritance');
        $node = $builder->getRootNode();
        $node->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('type')
                    ->defaultValue(static::INHERITANCE_TYPE_JOINED)
                    ->info(<<<EOD
Doctrine inheritance strategy for creating NodesSources
classes table(s). BE CAREFUL, if you change this
setting after filling content in your website, all
node-sources data will be lost.
EOD
                    )
                    ->validate()
                    ->ifNotInArray([
                        static::INHERITANCE_TYPE_JOINED,
                        static::INHERITANCE_TYPE_SINGLE_TABLE
                    ])
                    ->thenInvalid('The %s inheritance type is not supported ("joined", "single_table" are accepted).')
                ->end()
            ->end()
        ;
        return $node;
    }

    /**
     * @return ArrayNodeDefinition|NodeDefinition
     */
    protected function addMailerNode()
    {
        $builder = new TreeBuilder('mailer');
        $node = $builder->getRootNode();
        $node->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('url')->defaultNull()->end()
                ->enumNode('type')
                    ->values([null, 'smtp'])
                    ->defaultNull()
                ->end()
                ->scalarNode('host')->defaultValue('localhost')->end()
                ->integerNode('port')->treatNullLike(25)->defaultValue(25)->end()
                ->scalarNode('encryption')
                    ->defaultNull()
                        ->validate()
                        ->ifNotInArray(['tls', 'ssl', null, false])
                        ->thenInvalid('The %s encryption is not supported')
                    ->end()
                ->end()
                ->scalarNode('auth_mode')
                    ->defaultNull()
                        ->validate()
                        ->ifNotInArray(['plain', 'login', 'cram-md5', null])
                        ->thenInvalid('The %s authentication mode is not supported')
                    ->end()
                ->end()
                ->scalarNode('username')->end()
                ->scalarNode('password')->end()
            ->end();

        return $node;
    }

    /**
     * @return ArrayNodeDefinition|NodeDefinition
     */
    protected function addAssetsNode()
    {
        $builder = new TreeBuilder('assetsProcessing');
        $node = $builder->getRootNode();
        $node->addDefaultsIfNotSet()
            ->children()
                ->enumNode('driver')
                    ->values(['gd', 'imagick'])
                    ->defaultValue('gd')
                    ->info('GD does not support TIFF and PSD formats, but iMagick must be installed')
                ->end()
                ->integerNode('defaultQuality')
                    ->min(10)
                    ->max(100)
                    ->defaultValue(95)
                ->end()
                ->integerNode('maxPixelSize')
                    ->min(600)
                    ->defaultValue(2500)
                    ->info('Pixel width limit after Roadiz should create a smaller copy')
                ->end()
                ->scalarNode('jpegoptimPath')->defaultNull()->end()
                ->scalarNode('pngquantPath')->defaultNull()->end()
            ->arrayNode('subscribers')
                ->prototype('array')
                    ->children()
                        ->scalarNode('class')->isRequired()->cannotBeEmpty()->end()
                        ->arrayNode('args')
                            ->prototype('scalar')->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $node;
    }

    /**
     * @return ArrayNodeDefinition|NodeDefinition
     */
    protected function addSolrNode()
    {
        $builder = new TreeBuilder('solr');
        $node = $builder->getRootNode();

        $node->children()
                ->arrayNode('endpoint')
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('host')->defaultValue('127.0.0.1')->end()
                            ->scalarNode('username')->end()
                            ->scalarNode('password')->end()
                            ->scalarNode('core')->isRequired()->end()
                            ->enumNode('scheme')
                                ->values(['http', 'https'])
                                ->defaultValue('http')
                            ->end()
                            ->scalarNode('timeout')->defaultValue(3)->end()
                            ->scalarNode('port')->defaultValue(8983)->end()
                            ->scalarNode('path')->defaultValue('/')->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $node;
    }

    /**
     * @return ArrayNodeDefinition|NodeDefinition
     */
    protected function addReverseProxyCacheNode()
    {
        $builder = new TreeBuilder('reverseProxyCache');
        $node = $builder->getRootNode();

        $node->children()
                ->arrayNode('frontend')
                    ->isRequired()
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                    ->children()
                        ->scalarNode('host')
                            ->isRequired()
                            ->defaultValue('localhost')
                        ->end()
                        ->scalarNode('domainName')
                            ->isRequired()
                            ->defaultValue('localhost')
                        ->end()
                        ->scalarNode('timeout')->defaultValue(3)->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('cloudflare')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('version')
                            ->defaultValue('v4')
                        ->end()
                        ->scalarNode('zone')
                            ->isRequired()
                        ->end()
                        ->scalarNode('bearer')->end()
                        ->scalarNode('email')->end()
                        ->scalarNode('key')->end()
                        ->scalarNode('timeout')
                            ->defaultValue(3)
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $node;
    }

    /**
     * @return ArrayNodeDefinition|NodeDefinition
     */
    protected function addMessengerNode()
    {
        $builder = new TreeBuilder('messenger');
        $node = $builder->getRootNode()->addDefaultsIfNotSet();

        $node->children()
            ->scalarNode('failure_transport')
                ->defaultValue('failed_default')
            ->end()
            ->arrayNode('transports')
                ->useAttributeAsKey('name')
                ->defaultValue([
                    'default' => [
                        'dsn' => 'sync://',
                        'options' => []
                    ],
                    'failed_default' => [
                        'dsn' => 'doctrine://default?queue_name=failed_default',
                        'options' => []
                    ]
                ])
                ->prototype('array')
                ->children()
                    ->scalarNode('dsn')
                        ->isRequired()
                    ->end()
                    ->arrayNode('options')
                    ->end()
                ->end()
            ->end()->end()
            ->arrayNode('routing')
                ->useAttributeAsKey('name')
                ->prototype('scalar')
                    ->cannotBeEmpty()
                    ->info('Map message names to a transport')
                ->end()
            ->end()
        ->end();

        return $node;
    }

    /**
     * @return ArrayNodeDefinition|NodeDefinition
     */
    protected function addThemesNode()
    {
        $builder = new TreeBuilder('themes');
        $node = $builder->getRootNode();

        $node
            ->defaultValue([])
            ->prototype('array')
            ->children()
                ->scalarNode('classname')
                    ->info('Full qualified theme class (this must start with \ character and ends with App suffix)')
                    ->isRequired()
                    ->validate()
                        ->ifTrue(function (string $s) {
                            return preg_match('/^\\\[a-zA-Z\\\]+App$/', trim($s)) !== 1 || !class_exists($s);
                        })
                        ->thenInvalid('Theme class does not exist or classname is invalid: must start with \ character and ends with App suffix.')
                    ->end()
                ->end()
                ->scalarNode('hostname')
                    ->defaultValue('*')
                ->end()
                ->scalarNode('routePrefix')
                    ->defaultValue('')
                ->end()
            ->end()
            ->end();

        return $node;
    }

    /**
     * @return ArrayNodeDefinition|NodeDefinition
     */
    protected function addMonologNode()
    {
        $builder = new TreeBuilder('monolog');
        $node = $builder->getRootNode();

        $node->children()
                ->arrayNode('handlers')
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                        ->children()
                            ->enumNode('type')
                                ->values([
                                    'default',
                                    'stream',
                                    'rotating_file',
                                    'syslog',
                                    'gelf',
                                    'sentry',
                                ])
                                ->isRequired()
                                ->cannotBeEmpty()
                                ->defaultValue('default')
                            ->end()
                            ->enumNode('level')
                                ->beforeNormalization()
                                    ->ifString()
                                    ->then(function ($v) {
                                        return strtoupper($v);
                                    })
                                ->end()
                                ->values([
                                    'DEBUG',
                                    'INFO',
                                    'NOTICE',
                                    'WARNING',
                                    'ERROR',
                                    'CRITICAL',
                                    'ALERT',
                                    'EMERGENCY',
                                ])
                                ->isRequired()
                                ->defaultValue('INFO')
                            ->end()
                            ->scalarNode('url')->end()
                            ->scalarNode('ident')->end()
                            ->scalarNode('path')
                                ->beforeNormalization()
                                    ->ifString()
                                    ->then(function ($v) {
                                        return $this->resolveKernelVars($v);
                                    })
                                ->end()
                            ->end()
                            ->scalarNode('max_files')
                                ->defaultValue(10)
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $node;
    }

    /**
     * @param string $resolvable
     * @return string
     */
    protected function resolveKernelVars(string $resolvable): string
    {
        return str_replace([
            '%kernel.name%',
            '%kernel.project_dir%',
            '%kernel.cache_dir%',
            '%kernel.root_dir%',
            '%kernel.log_dir%',
            '%kernel.logs_dir%',
            '%kernel.environment%',
        ], [
            $this->kernel->getName(),
            $this->kernel->getProjectDir(),
            $this->kernel->getCacheDir(),
            $this->kernel->getRootDir(),
            $this->kernel->getLogDir(),
            $this->kernel->getLogDir(),
            $this->kernel->getEnvironment(),
        ], $resolvable);
    }
}
