<?php
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
