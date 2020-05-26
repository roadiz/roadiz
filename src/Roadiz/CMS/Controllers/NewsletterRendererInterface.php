<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Controllers;

use RZ\Roadiz\Core\Entities\Newsletter;
use Symfony\Component\HttpFoundation\Request;

interface NewsletterRendererInterface
{
    /**
     * Generate HTML. The function name makeHtml is important because it will be used
     * by NewsletterUtilsController to get your newsletter HTML body.
     *
     * @param Request $request
     * @param Newsletter $newsletter
     *
     * @return string
     */
    public function makeHtml(Request $request, Newsletter $newsletter): string;
}
