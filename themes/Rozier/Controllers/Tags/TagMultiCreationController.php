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
 * @file TagMultiCreationController.php
 * @author Ambroise Maupate
 */
namespace Themes\Rozier\Controllers\Tags;

use RZ\Roadiz\CMS\Forms\Constraints\UniqueTagName;
use RZ\Roadiz\Core\Entities\Tag;
use RZ\Roadiz\Core\Events\Tag\TagCreatedEvent;
use RZ\Roadiz\Utils\Tag\TagFactory;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Validator\Constraints\NotBlank;
use Themes\Rozier\RozierApp;

/**
 * {@inheritdoc}
 */
class TagMultiCreationController extends RozierApp
{
    public function addChildAction(Request $request, $parentTagId)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_TAGS');

        $translation = $this->get('defaultTranslation');
        $parentTag = $this->get('em')->find(Tag::class, (int) $parentTagId);

        if (null !== $parentTag) {
            $form = $this->buildAddForm();
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

    /**
     * @return \Symfony\Component\Form\Form
     */
    private function buildAddForm()
    {
        $builder = $this->createFormBuilder()
            ->add('names', TextareaType::class, [
                'label' => 'tags.names',
                'attr' => [
                    'placeholder' => 'write.every.tags.names.comma.separated',
                ],
                'constraints' => [
                    new NotBlank(),
                    new UniqueTagName([
                        'entityManager' => $this->get('em'),
                    ]),
                ],
            ]);

        return $builder->getForm();
    }
}
