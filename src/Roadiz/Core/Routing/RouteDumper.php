<?php
/**
 * Copyright © 2015, Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * Except as contained in this notice, the name of the ROADIZ shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from Ambroise Maupate and Julien Blanchet.
 *
 * @file RouteDumper.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Routing;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Routing\Generator\Dumper\PhpGeneratorDumper;
use Symfony\Component\Routing\Matcher\Dumper\PhpMatcherDumper;
use Symfony\Component\Routing\RouteCollection;

/**
 *
 */
class RouteDumper
{
    protected $routeCollection;
    protected $cacheDirectory;
    protected $fs;
    protected $matcherClassName;
    protected $generatorClassName;

    /**
     * @param RouteCollection $routeCollection
     * @param string          $cacheDirectory
     * @param string          $matcherClassName
     * @param string          $generatorClassName
     */
    public function __construct(
        RouteCollection $routeCollection,
        $cacheDirectory,
        $matcherClassName = 'GlobalUrlMatcher',
        $generatorClassName = 'GlobalUrlGenerator'
    ) {
        $this->routeCollection = $routeCollection;
        $this->cacheDirectory = $cacheDirectory;
        $this->matcherClassName = $matcherClassName;
        $this->generatorClassName = $generatorClassName;
        $this->fs = new Filesystem();
    }

    /**
     * Create routes cache directory.
     */
    protected function createCacheDirectory()
    {
        if (!$this->fs->exists($this->cacheDirectory)) {
            $this->fs->mkdir($this->cacheDirectory, 0755);
        }
    }

    /**
     * Dump route matcher and generators.
     */
    public function dump()
    {
        $this->createCacheDirectory();
        /*
         * Generate custom UrlMatcher
         */
        $dumper = new PhpMatcherDumper($this->routeCollection);
        $class = $dumper->dump([
            'class' => $this->matcherClassName,
        ]);
        $this->fs->dumpFile($this->cacheDirectory . '/' . $this->matcherClassName . '.php', $class);

        /*
         * Generate custom UrlGenerator
         */
        $dumper = new PhpGeneratorDumper($this->routeCollection);
        $class = $dumper->dump([
            'class' => $this->generatorClassName,
        ]);
        $this->fs->dumpFile($this->cacheDirectory . '/' . $this->generatorClassName . '.php', $class);
    }
}
