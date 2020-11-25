<?php
declare(strict_types=1);

namespace RZ\Roadiz\Config;

class DotEnvConfigurationHandler extends ConfigurationHandler
{
    const ENV_PATTERN = '#^\%env\((?:default:(?<fallback>[^:]*):)?(?<cast>[a-z]+)?:?(?<env>[A-Za-z\-\_0-9]+)\)\%$#';

    /**
     * @param array $configuration
     * @return DotEnvConfigurationHandler
     */
    public function setConfiguration(array $configuration)
    {
        /*
         * Must resolve dot env BEFORE setting configuration and processing it.
         */
        return parent::setConfiguration(
            $this->resolveDotEnvPlaceholders($configuration)
        );
    }

    /**
     * @param array $unresolvedConfiguration
     * @return array
     * @see https://symfony.com/doc/current/configuration/env_var_processors.html#built-in-environment-variable-processors
     */
    protected function resolveDotEnvPlaceholders(array $unresolvedConfiguration): array
    {
        array_walk_recursive($unresolvedConfiguration, function (&$item) {
            if (is_string($item) && preg_match(static::ENV_PATTERN, $item, $matches) === 1) {
                $envName = trim($matches['env']);
                if (!key_exists($envName, $_ENV) || (is_string($_ENV[$envName]) && $_ENV[$envName] === '')) {
                    if (empty($matches['fallback']) || $matches['fallback'] === '') {
                        $item = null;
                    } else {
                        $item = $matches['fallback'];
                    }
                } else {
                    switch ($matches['cast']) {
                        case 'base64':
                            $item = base64_decode($_ENV[$envName]);
                            break;
                        case 'json':
                            $item = json_decode($_ENV[$envName], true);
                            break;
                        case 'bool':
                            $item = $this->getBooleanValue($_ENV[$envName]);
                            break;
                        case 'float':
                            $item = floatval($_ENV[$envName]);
                            break;
                        case 'int':
                            $item = intval($_ENV[$envName]);
                            break;
                        case 'string':
                            $item = (string) $_ENV[$envName];
                            break;
                        default:
                            $item = $_ENV[$envName];
                            break;
                    }
                }
            }
        });
        return $unresolvedConfiguration;
    }

    /**
     * @param mixed $value
     */
    protected function getBooleanValue($value): bool
    {
        if (\is_bool($value)) {
            return $value;
        }
        if (\in_array($value, ['true', 'on', 1, '1', 'ok'], true)) {
            return true;
        }
        return false;
    }

    /**
     * @param array|string|\stdClass $rawConfiguration
     * @return string
     */
    protected function generateConfigurationCacheSource($rawConfiguration): string
    {
        return '<?php return ' . var_export($rawConfiguration, true) . ';' . PHP_EOL;
    }
}
