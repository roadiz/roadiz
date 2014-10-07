<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file NodesController.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace Themes\Rozier\Controllers;

use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Entities\Node;
use RZ\Renzo\Core\Entities\Tag;
use RZ\Renzo\Core\Entities\NodeType;
use RZ\Renzo\Core\Entities\NodeTypeField;
use RZ\Renzo\Core\Entities\UrlAlias;
use RZ\Renzo\Core\Entities\Translation;
use RZ\Renzo\Core\Handlers\NodeHandler;
use RZ\Renzo\Core\Utils\StringHandler;
use RZ\Renzo\Core\ListManagers\EntityListManager;

use Themes\Rozier\Widgets\NodeTreeWidget;
use Themes\Rozier\RozierApp;

use RZ\Renzo\Core\Exceptions\EntityAlreadyExistsException;
use RZ\Renzo\Core\Exceptions\NoTranslationAvailableException;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use \Symfony\Component\Form\Form;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Translation\Translator;

/**
 * Nodes sources controller.
 *
 * {@inheritdoc}
 */
class NodesSourcesController extends RozierApp
{

    /**
     * Return an edition form for requested node.
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param int                                      $nodeId
     * @param int                                      $translationId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function editSourceAction(Request $request, $nodeId, $translationId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_NODES');

        $translation = $this->getService('em')
                ->find('RZ\Renzo\Core\Entities\Translation', (int) $translationId);

        if ($translation !== null) {

            $gnode = $this->getService('em')
                ->find('RZ\Renzo\Core\Entities\Node', (int) $nodeId);

            $source = $this->getService('em')
                ->getRepository('RZ\Renzo\Core\Entities\NodesSources')
                ->findOneBy(array('translation'=>$translation, 'node.id'=>(int) $nodeId));

            if (null !== $source &&
                null !== $translation) {

                $node = $source->getNode();

                $this->assignation['translation'] = $translation;
                $this->assignation['available_translations'] = $gnode->getHandler()->getAvailableTranslations();
                $this->assignation['node'] = $node;
                $this->assignation['source'] = $source;

                /*
                 * Form
                 */
                $form = $this->buildEditSourceForm($node, $source);
                $form->handleRequest();

                if ($form->isValid()) {
                    $this->editNodeSource($form->getData(), $source);

                    $msg = $this->getTranslator()->trans('node_source.%node_source%.updated.%translation%', array(
                        '%node_source%'=>$source->getNode()->getNodeName(),
                        '%translation%'=>$source->getTranslation()->getName()
                    ));
                    $request->getSession()->getFlashBag()->add('confirm', $msg);
                    $this->getService('logger')->info($msg);
                    /*
                     * Force redirect to avoid resending form when refreshing page
                     */
                    $response = new RedirectResponse(
                        $this->getService('urlGenerator')->generate(
                            'nodesEditSourcePage',
                            array('nodeId' => $node->getId(), 'translationId'=>$translation->getId())
                        )
                    );
                    $response->prepare($request);

                    return $response->send();
                }

                $this->assignation['form'] = $form->createView();
                //$this->getService('em')->detach($node);

                return new Response(
                    $this->getTwig()->render('nodes/editSource.html.twig', $this->assignation),
                    Response::HTTP_OK,
                    array('content-type' => 'text/html')
                );
            }
        }

        return $this->throw404();
    }

    /**
     * Edit node source parameters.
     *
     * @param array                               $data
     * @param RZ\Renzo\Core\Entities\NodesSources $nodeSource
     *
     * @return void
     */
    private function editNodeSource($data, $nodeSource)
    {
        if (isset($data['title'])) {
            $nodeSource->setTitle($data['title']);
        }

        $fields = $nodeSource->getNode()->getNodeType()->getFields();
        foreach ($fields as $field) {
            if (isset($data[$field->getName()])) {
                static::setValueFromFieldType($data, $nodeSource, $field);
            }
        }

        $this->getService('em')->flush();

        // Update Solr Serach engine if setup
        if (true === $this->getKernel()->pingSolrServer()) {
            $solrSource = new \RZ\Renzo\Core\SearchEngine\SolariumNodeSource(
                $nodeSource,
                $this->getService('solr')
            );
            $solrSource->getDocumentFromIndex();
            $solrSource->updateAndCommit();
        }
    }

    /**
     * @param RZ\Renzo\Core\Entities\Node         $node
     * @param RZ\Renzo\Core\Entities\NodesSources $source
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildEditSourceForm(Node $node, $source)
    {
        $fields = $node->getNodeType()->getFields();
        /*
         * Create source default values
         */
        $sourceDefaults = array(
            'title' => $source->getTitle()
        );
        foreach ($fields as $field) {
            if (!$field->isVirtual()) {
                $getter = $field->getGetterName();
                $sourceDefaults[$field->getName()] = $source->$getter();
            }
        }

        /*
         * Create subform for source
         */
        $sourceBuilder = $this->getService('formFactory')
            ->createNamedBuilder('source', 'form', $sourceDefaults)
            ->add(
                'title',
                'text',
                array(
                    'label' => $this->getTranslator()->trans('title'),
                    'required' => false
                )
            );
        foreach ($fields as $field) {
            $sourceBuilder->add(
                $field->getName(),
                static::getFormTypeFromFieldType($source, $field, $this),
                static::getFormOptionsFromFieldType($source, $field, $this->getTranslator())
            );
        }

        return $sourceBuilder->getForm();
    }

    /**
     * @param mixed         $nodeSource
     * @param NodeTypeField $field
     * @param AppController $controller
     *
     * @return AbstractType
     */
    public static function getFormTypeFromFieldType($nodeSource, NodeTypeField $field, $controller)
    {
        switch ($field->getType()) {
            case NodeTypeField::DOCUMENTS_T:
                $documents = $nodeSource->getHandler()
                                ->getDocumentsFromFieldName($field->getName());

                return new \RZ\Renzo\CMS\Forms\DocumentsType($documents);
            case NodeTypeField::CHILDREN_T:
                /*
                 * NodeTreeType is a virtual type which is only available
                 * with Rozier backend theme.
                 */
                return new \Themes\Rozier\Forms\NodeTreeType(
                    $nodeSource,
                    $field,
                    $controller
                );

            case NodeTypeField::MARKDOWN_T:
                return new \RZ\Renzo\CMS\Forms\MarkdownType();
            case NodeTypeField::ENUM_T:
                return new \RZ\Renzo\CMS\Forms\EnumerationType($field);
            case NodeTypeField::MULTIPLE_T:
                return new \RZ\Renzo\CMS\Forms\MultipleEnumerationType($field);

            default:
                return NodeTypeField::$typeToForm[$field->getType()];
        }
    }

    public static function getFormOptionsFromFieldType(
        $nodeSource,
        NodeTypeField $field,
        Translator $translator
    ) {
        switch ($field->getType()) {
            case NodeTypeField::ENUM_T:
                return array(
                    'label' => $field->getLabel(),
                    'empty_value' => $translator->trans('choose.value'),
                    'required' => false
                );

            default:
                return array(
                    'label' => $field->getLabel(),
                    'required' => false
                );
        }
    }

    /**
     * Fill node-source content according to field type.
     * @param array         $data
     * @param NodesSources  $nodeSource
     * @param NodeTypeField $field
     *
     * @return void
     */
    public static function setValueFromFieldType($data, $nodeSource, NodeTypeField $field)
    {
        switch ($field->getType()) {
            case NodeTypeField::DOCUMENTS_T:
                $nodeSource->getHandler()->cleanDocumentsFromField($field);

                foreach ($data[$field->getName()] as $documentId) {
                    $tempDoc = Kernel::getService('em')
                        ->find('RZ\Renzo\Core\Entities\Document', (int) $documentId);
                    if ($tempDoc !== null) {
                        $nodeSource->getHandler()->addDocumentForField($tempDoc, $field);
                    }
                }
                break;
            case NodeTypeField::CHILDREN_T:
                break;
            default:
                $setter = $field->getSetterName();
                $nodeSource->$setter( $data[$field->getName()] );
                break;
        }
    }
}
