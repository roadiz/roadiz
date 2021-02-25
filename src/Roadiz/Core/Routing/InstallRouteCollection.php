<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Routing;

class InstallRouteCollection extends DeferredRouteCollection
{
    /**
     * @var class-string
     */
    protected string $installClassname;

    /**
     * @param class-string $installClassname
     */
    public function __construct(string $installClassname)
    {
        $this->installClassname = $installClassname;
    }

    /**
     * {@inheritdoc}
     */
    public function parseResources(): void
    {
        if (class_exists($this->installClassname)) {
            $collection = call_user_func([$this->installClassname, 'getRoutes']);
            if (null !== $collection) {
                $this->addCollection($collection);
            }
        } else {
            throw new \RuntimeException("Install class “" . $this->installClassname . "” does not exist.", 1);
        }
    }
}
