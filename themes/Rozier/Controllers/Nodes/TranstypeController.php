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
 * @file TranstypeController.php
 * @author Ambroise Maupate
 */
namespace Themes\Rozier\Controllers\Nodes;

use Doctrine\Common\Collections\ArrayCollection;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodesSourcesDocuments;
use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Entities\NodeTypeField;
use Symfony\Component\HttpFoundation\Request;
use Themes\Rozier\Forms\TranstypeType;
use Themes\Rozier\RozierApp;

class TranstypeController extends RozierApp
{
    public function transtypeAction(Request $request, $nodeId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_NODES');

        $node = $this->getService('em')
            ->find('RZ\Roadiz\Core\Entities\Node', (int) $nodeId);
        $this->getService('em')->refresh($node);

        if (null === $node) {
            return $this->throw404();
        }

        $form = $this->createForm(new TranstypeType(), null, [
            'em' => $this->getService('em'),
            'currentType' => $node->getNodeType(),
        ]);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $data = $form->getData();

            $newNodeType = $this->getService('em')
                ->find('RZ\Roadiz\Core\Entities\NodeType', (int) $data['nodeTypeId']);

            $this->doTranstype($node, $newNodeType);
            $this->getService('em')->refresh($node);

            $msg = $this->getTranslator()->trans('%node%.transtyped_to.%type%', [
                '%node%' => $node->getNodeName(),
                '%type%' => $newNodeType->getName(),
            ]);
            $this->publishConfirmMessage($request, $msg);

            return $this->redirect($this->generateUrl(
                'nodesEditSourcePage',
                ['nodeId' => $node->getId(), 'translationId' => $node->getNodeSources()->first()->getTranslation()->getId()]
            ));
        }

        $this->assignation['form'] = $form->createView();
        $this->assignation['node'] = $node;
        $this->assignation['parentNode'] = $node->getParent();
        $this->assignation['type'] = $node->getNodeType();

        return $this->render('nodes/transtype.html.twig', $this->assignation);
    }

    protected function doTranstype(Node $node, NodeType $nodeType)
    {
        /*
         * Get an association between old fields and new fields
         * to find data that can be transfered during transtyping.
         */
        $fieldAssociations = [];
        $oldFields = $node->getNodeType()->getFields();
        $er = $this->getService('em')->getRepository('RZ\Roadiz\Core\Entities\NodeTypeField');

        foreach ($oldFields as $oldField) {
            $matchingField = $er->findOneBy([
                'nodeType' => $nodeType,
                'name' => $oldField->getName(),
                'type' => $oldField->getType(),
            ]);

            if (null !== $matchingField) {
                $fieldAssociations[] = [
                    $oldField, // old type field
                    $matchingField, // new type field
                ];
            }
        }

        foreach ($node->getNodeSources() as $existingSource) {
            $sourceClass = NodeType::getGeneratedEntitiesNamespace() . "\\" . $nodeType->getSourceEntityClassName();
            $source = new $sourceClass($node, $existingSource->getTranslation());
            $source->setTitle($existingSource->getTitle());
            $nsDocuments = new ArrayCollection();

            foreach ($fieldAssociations as $fields) {
                $oldField = $fields[0];
                $matchingField = $fields[1];

                if (!$oldField->isVirtual()) {
                    /*
                     * Copy simple data from source to another
                     */
                    $setter = $oldField->getSetterName();
                    $getter = $oldField->getGetterName();

                    $source->$setter($existingSource->$getter());
                } elseif ($oldField->getType() === NodeTypeField::DOCUMENTS_T) {
                    /*
                     * Copy documents.
                     */
                    $documents = $this->getService('em')
                        ->getRepository('RZ\Roadiz\Core\Entities\Document')
                        ->findByNodeSourceAndField($existingSource, $oldField);

                    foreach ($documents as $document) {
                        $nsDocuments->add(new NodesSourcesDocuments($source, $document, $matchingField));
                    }
                }
            }
            // First plan old source deletion.
            $this->getService('em')->remove($existingSource);
            $node->removeNodeSources($existingSource);
            $this->getService('em')->flush($existingSource);

            foreach ($nsDocuments as $nsDoc) {
                $source->getDocumentsByFields()->add($nsDoc);
                $this->getService('em')->persist($nsDoc);
            }

            $node->addNodeSources($source);
            $this->getService('em')->persist($source);
            $this->getService('em')->flush($source);
        }

        $node->setNodeType($nodeType);
        $this->getService('em')->flush();
    }
}
