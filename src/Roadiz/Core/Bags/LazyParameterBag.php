<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Bags;

use Symfony\Component\HttpFoundation\ParameterBag;

abstract class LazyParameterBag extends ParameterBag
{
    /**
     * @var bool
     */
    protected $ready;

    abstract protected function populateParameters();

    public function __construct()
    {
        parent::__construct();
        $this->ready = false;
    }

    /**
     * @param string $key
     * @param mixed|null $default
     * @return mixed|null
     */
    public function get(string $key, $default = null)
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

    /**
     * @param string $key
     *
     * @return bool
     */
    public function has(string $key)
    {
        if (!$this->ready) {
            $this->populateParameters();
        }

        return parent::has($key);
    }

    /**
     * @return array
     */
    public function keys()
    {
        if (!$this->ready) {
            $this->populateParameters();
        }

        return parent::keys();
    }

    /**
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        if (!$this->ready) {
            $this->populateParameters();
        }

        return parent::getIterator();
    }

    /**
     * @return int
     */
    public function count()
    {
        if (!$this->ready) {
            $this->populateParameters();
        }

        return parent::count();
    }

    /**
     * @param string $key
     * @param null   $default
     * @param int    $filter
     * @param array  $options
     *
     * @return mixed
     */
    public function filter(string $key, $default = null, $filter = FILTER_DEFAULT, $options = [])
    {
        if (!$this->ready) {
            $this->populateParameters();
        }

        return parent::filter($key, $default, $filter, $options);
    }

    public function reset(): void
    {
        $this->parameters = [];
        $this->ready = false;
    }
}
