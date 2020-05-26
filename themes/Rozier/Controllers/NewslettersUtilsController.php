<?php
declare(strict_types=1);

namespace Themes\Rozier\Controllers;

use InlineStyle\InlineStyle;
use RZ\Roadiz\CMS\Controllers\AppController;
use RZ\Roadiz\CMS\Controllers\NewsletterRendererInterface;
use RZ\Roadiz\Core\Entities\Newsletter;
use RZ\Roadiz\Core\Handlers\NewsletterHandler;
use RZ\Roadiz\Utils\DomHandler;
use RZ\Roadiz\Utils\Theme\ThemeResolverInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Themes\Rozier\RozierApp;

/**
 * Class NewslettersUtilsController
 *
 * @package Themes\Rozier\Controllers
 */
class NewslettersUtilsController extends RozierApp
{
    /**
     * Duplicate node by ID.
     *
     * @param Request $request
     * @param int     $newsletterId
     *
     * @return Response
     */
    public function duplicateAction(Request $request, $newsletterId)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_NEWSLETTERS');
        $translation = $this->get('defaultTranslation');
        /** @var Newsletter $existingNewsletter */
        $existingNewsletter = $this->get('em')->find(Newsletter::class, (int) $newsletterId);
        if (null === $existingNewsletter) {
            throw $this->createNotFoundException();
        }

        try {
            /** @var NewsletterHandler $handler */
            $handler = $this->get('newsletter.handler');
            $handler->setNewsletter($existingNewsletter);

            $newNewsletter = $handler->duplicate();

            $msg = $this->getTranslator()->trans("duplicated.newsletter.%name%", [
                '%name%' => $existingNewsletter->getNode()->getNodeName(),
            ]);

            $this->publishConfirmMessage($request, $msg);

            return $this->redirect($this->get('urlGenerator')
                                            ->generate(
                                                'newslettersEditPage',
                                                [
                                                    "newsletterId" => $newNewsletter->getId(),
                                                    "translationId" => $translation->getId(),
                                                ]
                                            ));
        } catch (\Exception $e) {
            $this->publishErrorMessage($request, $e->getMessage());

            return $this->redirect($this->get('urlGenerator')
                                            ->generate(
                                                'newslettersEditPage',
                                                [
                                                    "newsletterId" => $existingNewsletter->getId(),
                                                    "translationId" => $translation->getId(),
                                                ]
                                            ));
        }
    }

    /**
     * @return string
     */
    private function getBaseNamespace()
    {
        /** @var ThemeResolverInterface $themeResolver */
        $themeResolver = $this->get('themeResolver');
        $frontendThemes = $themeResolver->getFrontendThemes();
        if (count($frontendThemes) > 0) {
            // get first not static frontend
            $theme = $themeResolver->getFrontendThemes()[0];
            $baseNamespace = explode("\\", $theme->getClassName());
            // remove last elem of the array
            array_pop($baseNamespace);

            return implode("\\", $baseNamespace);
        }
        throw new \RuntimeException('There is no theme registered to render newsletters.');
    }

    /**
     * @param Request $request
     * @param Newsletter $newsletter
     *
     * @return string
     */
    private function getNewsletterHTML(Request $request, Newsletter $newsletter): string
    {
        $baseNamespace = $this->getBaseNamespace();

        // make namespace of the newsletter from the default dynamic theme namespace and newsletter notetype
        $classname = $baseNamespace
        . "\\NewsletterControllers\\"
        . $newsletter->getNode()->getNodeType()->getName()
        . "Controller";
        if (class_exists($classname)) {
            $front = new $classname();
            if ($front instanceof AppController &&
                $front instanceof NewsletterRendererInterface) {
                $this->get('twig.loaderFileSystem')->prependPath($front::getViewsFolder());
                $front->setContainer($this->getContainer());
                $front->prepareBaseAssignation();
                return $front->makeHtml($request, $newsletter);
            }
        }

        throw new \RuntimeException(sprintf(
            '""%s" class does not inherit "%s" or does not implements "%s" interface.',
            $classname,
            AppController::class,
            NewsletterRendererInterface::class
        ));
    }

    /**
     * Preview a newsletter
     *
     * @param Request $request
     * @param int     $newsletterId
     *
     * @return Response
     */
    public function previewAction(Request $request, $newsletterId)
    {
        $newsletter = $this->get("em")->find(
            Newsletter::class,
            $newsletterId
        );

        return new Response(
            $this->getNewsletterHTML($request, $newsletter),
            Response::HTTP_OK,
            ['content-type' => 'text/html']
        );
    }

    /**
     * Export the newsletter in HTML with or without inline CSS
     *
     * @param Request $request
     * @param int     $newsletterId
     * @param int     $inline
     *
     * @return Response
     */
    public function exportAction(Request $request, $newsletterId, $inline)
    {
        $newsletter = $this->get("em")->find(
            Newsletter::class,
            $newsletterId
        );

        $filename = $newsletter->getNode()->getNodeName();
        $content = $this->getNewsletterHTML($request, $newsletter);

        // Get all css link in the newsletter
        $cssContent = DomHandler::getExternalStyles($content);

        if ((boolean) $inline === true) {
            // inline newsletter html with css

            $htmldoc = new InlineStyle($content);
            $htmldoc->applyStylesheet($cssContent);
            $htmldoc = $htmldoc->getHtml();

            $filename .= "-inlined";

            $content = $htmldoc;
        }

        // Remove all link element and add style balise with all css file content
        $htmldoc = DomHandler::replaceExternalStylesheetsWithStyle($content, $cssContent);

        // Generate response
        $response = new Response();

        // Set headers
        $response->headers->set('Content-type', "text/html");
        $response->headers->set('Content-Disposition', 'attachment; filename= "' . $filename . '.html";');

        $response->setContent($htmldoc);

        return $response;
    }
}
