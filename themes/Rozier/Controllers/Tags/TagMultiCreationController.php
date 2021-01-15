<?php
declare(strict_types=1);

namespace Themes\Rozier\Controllers\Tags;

use RZ\Roadiz\Core\Entities\Tag;
use RZ\Roadiz\Core\Events\Tag\TagCreatedEvent;
use RZ\Roadiz\Utils\Tag\TagFactory;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Themes\Rozier\Forms\MultiTagType;
use Themes\Rozier\RozierApp;

/**
 * @package Themes\Rozier\Controllers\Tags
 */
class TagMultiCreationController extends RozierApp
{
    /**
     * @param Request $request
     * @param int $parentTagId
     * @return RedirectResponse|Response|null
     * @throws \Twig\Error\RuntimeError
     */
    public function addChildAction(Request $request, int $parentTagId)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_TAGS');

        $translation = $this->get('defaultTranslation');
        $parentTag = $this->get('em')->find(Tag::class, $parentTagId);

        if (null !== $parentTag) {
            $form = $this->createForm(MultiTagType::class);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                try {
                    $data = $form->getData();
                    $names = explode(',', $data['names']);
                    $names = array_map('trim', $names);
                    $names = array_filter($names);
                    $names = array_unique($names);

                    /*
                     * Get latest position to add tags after.
                     */
                    $latestPosition = $this->get('em')
                        ->getRepository(Tag::class)
                        ->findLatestPositionInParent($parentTag);

                    $tagsArray = [];
                    /** @var TagFactory $tagFactory */
                    $tagFactory = $this->get(TagFactory::class);
                    foreach ($names as $name) {
                        $tagsArray[] = $tagFactory->create($name, $translation, $parentTag, $latestPosition);
                        $this->get('em')->flush();
                    }

                    /*
                     * Dispatch event and msg
                     */
                    foreach ($tagsArray as $tag) {
                        /*
                         * Dispatch event
                         */
                        $this->get('dispatcher')->dispatch(new TagCreatedEvent($tag));

                        $msg = $this->getTranslator()->trans('child.tag.%name%.created', ['%name%' => $tag->getTagName()]);
                        $this->publishConfirmMessage($request, $msg);
                    }

                    return $this->redirect($this->generateUrl('tagsTreePage', ['tagId' => $parentTagId]));
                } catch (\InvalidArgumentException $e) {
                    $form->addError(new FormError($e->getMessage()));
                }
            }

            $this->assignation['translation'] = $translation;
            $this->assignation['form'] = $form->createView();
            $this->assignation['tag'] = $parentTag;

            return $this->render('tags/add-multiple.html.twig', $this->assignation);
        }

        throw new ResourceNotFoundException();
    }
}
