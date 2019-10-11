<?php
/**
 * Copyright (c) 2019. Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
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
 * @file JavascriptRenderer.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */

declare(strict_types=1);

namespace RZ\Roadiz\Utils\DebugBar;

use DebugBar\JavascriptRenderer as BaseJavascriptRenderer;

class JavascriptRenderer extends BaseJavascriptRenderer
{
    /**
     * Renders the html to include needed assets
     *
     * Only useful if Assetic is not used
     *
     * @return string
     */
    public function renderHead()
    {
        list($cssFiles, $jsFiles, $inlineCss, $inlineJs, $inlineHead) = $this->getAssets(null, self::RELATIVE_URL);
        $html = '';

        foreach ($cssFiles as $file) {
            $html .= sprintf('<link rel="stylesheet" type="text/css" href="%s">' . "\n", $file);
        }

        foreach ($inlineCss as $content) {
            $html .= sprintf('<style type="text/css">%s</style>' . "\n", $content);
        }

        foreach ($jsFiles as $file) {
            $html .= sprintf('<script src="%s"></script>' . "\n", $file);
        }

        foreach ($inlineJs as $content) {
            $html .= sprintf('<script>%s</script>' . "\n", $content);
        }

        foreach ($inlineHead as $content) {
            $html .= $content . "\n";
        }

        if ($this->enableJqueryNoConflict && !$this->useRequireJs) {
            $html .= '<script>jQuery.noConflict(true);</script>' . "\n";
        }

        return $html;
    }

    /**
     * Returns the code needed to display the debug bar
     *
     * AJAX request should not render the initialization code.
     *
     * @param boolean $initialize Whether or not to render the debug bar initialization code
     * @param boolean $renderStackedData Whether or not to render the stacked data
     * @return string
     */
    public function render($initialize = true, $renderStackedData = true)
    {
        $js = '';

        if ($initialize) {
            $js = $this->getJsInitializationCode();
        }

        if ($renderStackedData && $this->debugBar->hasStackedData()) {
            foreach ($this->debugBar->getStackedData() as $id => $data) {
                $js .= $this->getAddDatasetCode($id, $data, '(stacked)');
            }
        }

        $suffix = !$initialize ? '(ajax)' : null;
        $js .= $this->getAddDatasetCode($this->debugBar->getCurrentRequestId(), $this->debugBar->getData(), $suffix);

        if ($this->useRequireJs) {
            return "<script>\nrequire(['debugbar'], function(PhpDebugBar){ $js });\n</script>\n";
        } else {
            return "<script>\n$js\n</script>\n";
        }
    }
}
