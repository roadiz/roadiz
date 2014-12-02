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

use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Entities\NodeTypeField;

use Themes\Rozier\RozierApp;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
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
                ->find('RZ\Roadiz\Core\Entities\Translation', (int) $translationId);

        if ($translation !== null) {

            /*
             * Here we need to directly select nodeSource
             * if not doctrine will grab a cache tag because of NodeTreeWidget
             * that is initialized before calling route method.
             */
            $gnode = $this->getService('em')
                ->find('RZ\Roadiz\Core\Entities\Node', (int) $nodeId);

            $source = $this->getService('em')
                ->getRepository('RZ\Roadiz\Core\Entities\NodesSources')
                ->findOneBy(array('translation'=>$translation, 'node'=>$gnode));

            if (null !== $source) {

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
     * @param RZ\Roadiz\Core\Entities\NodesSources $nodeSource
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
                static::setValueFromFieldType($data[$field->getName()], $nodeSource, $field);
            } else {
                static::setValueFromFieldType(null, $nodeSource, $field);
            }
        }

        $this->getService('em')->flush();

        // Update Solr Serach engine if setup
        if (true === $this->getKernel()->pingSolrServer()) {
            $solrSource = new \RZ\Roadiz\Core\SearchEngine\SolariumNodeSource(
                $nodeSource,
                $this->getService('solr')
            );
            $solrSource->getDocumentFromIndex();
            $solrSource->updateAndCommit();
        }
    }

    /**
     * @param RZ\Roadiz\Core\Entities\Node         $node
     * @param RZ\Roadiz\Core\Entities\NodesSources $source
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
                    'required' => false,
                    'attr' => array(
                        'data-desc' => ''
                    )
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

                return new \RZ\Roadiz\CMS\Forms\DocumentsType($documents);
            case NodeTypeField::NODES_T:
                $nodes = $nodeSource->getNode()->getHandler()
                                ->getNodesFromFieldName($field->getName());

                return new \RZ\Roadiz\CMS\Forms\NodesType($nodes);
            case NodeTypeField::CUSTOM_FORMS_T:
                $customForms = $nodeSource->getNode()->getHandler()
                                ->getCustomFormsFromFieldName($field->getName());

                return new \RZ\Roadiz\CMS\Forms\CustomFormsNodesType($customForms);
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
                return new \RZ\Roadiz\CMS\Forms\MarkdownType();
            case NodeTypeField::ENUM_T:
                return new \RZ\Roadiz\CMS\Forms\EnumerationType($field);
            case NodeTypeField::MULTIPLE_T:
                return new \RZ\Roadiz\CMS\Forms\MultipleEnumerationType($field);

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
                    'required' => false,
                    'attr' => array(
                        'data-desc' => $field->getDescription()
                    )
                );
            case NodeTypeField::DATETIME_T:
                return array(
                    'label' => $field->getLabel(),
                    'years' => range(date('Y')-10, date('Y')+10),
                    'required' => false,
                    'attr' => array(
                        'data-desc' => $field->getDescription(),
                        'class' => 'rz-datetime-field'
                    )
                );
            case NodeTypeField::INTEGER_T:
                return array(
                    'label' => $field->getLabel(),
                    'required' => false,
                    'constraints' => array(
                        new Type('integer')
                    )
                );
            case NodeTypeField::DECIMAL_T:
                return array(
                    'label' => $field->getLabel(),
                    'required' => false,
                    'constraints' => array(
                        new Type('double')
                    )
                );
            case NodeTypeField::COLOUR_T:
                return array(
                    'label' => $field->getLabel(),
                    'required' => false,
                    'attr' => array(
                        'data-desc' => $field->getDescription(),
                        'class' => 'colorpicker-input'
                    )
                );
            case NodeTypeField::GEOTAG_T:
                return array(
                    'label' => $field->getLabel(),
                    'required' => false,
                    'attr' => array(
                        'data-desc' => $field->getDescription(),
                        'class' => 'rz-geotag-field'
                    )
                );
            case NodeTypeField::MARKDOWN_T:
                return array(
                    'label' => $field->getLabel(),
                    'required' => false,
                    'attr' => array(
                        'class'           => 'markdown_textarea',
                        'data-desc'       => $field->getDescription(),
                        'data-min-length' => $field->getMinLength(),
                        'data-max-length' => $field->getMaxLength()
                    )
                );

            default:
                return array(
                    'label' => $field->getLabel(),
                    'required' => false,
                    'attr' => array(
                        'data-desc'       => $field->getDescription(),
                        'data-min-length' => $field->getMinLength(),
                        'data-max-length' => $field->getMaxLength()
                    )
                );
        }
    }

    /**
     * Fill node-source content according to field type.
     * @param mixed         $dataValue
     * @param NodesSources  $nodeSource
     * @param NodeTypeField $field
     *
     * @return void
     */
    public static function setValueFromFieldType($dataValue, $nodeSource, NodeTypeField $field)
    {
        switch ($field->getType()) {
            case NodeTypeField::DOCUMENTS_T:
                $hdlr = $nodeSource->getHandler();
                $hdlr->cleanDocumentsFromField($field);
                if (is_array($dataValue)) {
                    foreach ($dataValue as $documentId) {
                        $tempDoc = Kernel::getService('em')
                                        ->find('RZ\Roadiz\Core\Entities\Document', (int) $documentId);
                        if ($tempDoc !== null) {
                            $hdlr->addDocumentForField($tempDoc, $field);
                        }
                    }
                }
                break;
            case NodeTypeField::CUSTOM_FORMS_T:
                $hdlr = $nodeSource->getNode()->getHandler();
                $hdlr->cleanCustomFormsFromField($field);
                if (is_array($dataValue)) {
                    foreach ($dataValue as $customFormId) {
                        $tempCForm = Kernel::getService('em')
                                        ->find('RZ\Roadiz\Core\Entities\CustomForm', (int) $customFormId);
                        if ($tempCForm !== null) {
                            $hdlr->addCustomFormForField($tempCForm, $field);
                        }
                    }
                }
                break;
            case NodeTypeField::NODES_T:
                $hdlr = $nodeSource->getNode()->getHandler();
                $hdlr->cleanNodesFromField($field);

                if (is_array($dataValue)) {
                    foreach ($dataValue as $nodeId) {
                        $tempNode = Kernel::getService('em')
                                        ->find('RZ\Roadiz\Core\Entities\Node', (int) $nodeId);
                        if ($tempNode !== null) {
                            $hdlr->addNodeForField($tempNode, $field);
                        }
                    }
                }
                break;
            case NodeTypeField::CHILDREN_T:
                break;
            default:
                $setter = $field->getSetterName();
                $nodeSource->$setter( $dataValue );
                break;
        }
    }
}
