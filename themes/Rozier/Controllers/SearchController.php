<?php
/**
 * Copyright © 2014, REZO ZERO
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
 * Except as contained in this notice, the name of the REZO ZERO shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from the REZO ZERO SARL.
 *
 * @file SearchController.php
 * @copyright REZO ZERO 2014
 * @author Maxime Constantinian
 */

namespace Themes\Rozier\Controllers;

use Themes\Rozier\RozierApp;
use RZ\Renzo\Core\Entities\Node;
use RZ\Renzo\Core\Entities\NodeType;
use RZ\Renzo\Core\ListManagers\EntityListManager;

use RZ\Renzo\CMS\Forms\NodeStatesType;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\Validator\Constraints\NotBlank;

class SearchController extends RozierApp
{
    public function isBlank($var) {
        return empty($var) && !is_numeric($var);
    }

    public function notBlank($var) {
        return !$this->isBlank($var);
    }

    public function processCriteria($data) {
        if (!empty($data["nodeName"])) {
            $data["nodeName"] = array("LIKE", "%" . $data["nodeName"] . "%");
        }
        if (!$this->isBlank($data["parent"])) {
            if ($data["parent"] == "null" || $data["parent"] == 0) {
                $data["parent"] = null;
            }
        }
        if (isset($data['visible'])) {
            $data['visible'] = (bool) $data['visible'];
        }
        return $data;
    }

    public function searchNodeAction(Request $request) {

        $form = $this->buildSimpleForm();
        $form->handleRequest();

        if ($form->isValid()) {

            // $data = array_filter($form->getData(), $this->isBlank);
            $data = array();
            foreach ($form->getData() as $key => $value) {
                if ($this->notBlank($value)) {
                    $data[$key] = $value;
                }
            }
            $data = $this->processCriteria($data);
            $listManager = new EntityListManager(
                $request,
                $this->getService('em'),
                'RZ\Renzo\Core\Entities\Node',
                $data
            );
            $listManager->handle();

            $this->assignation['filters'] = $listManager->getAssignation();
            $this->assignation['filters']['search'] = false;
            $this->assignation['nodes'] = $listManager->getEntities();

        }

        $this->assignation['form'] = $form->createView();

        return new Response(
            $this->getTwig()->render('search/list.html.twig', $this->assignation),
            Response::HTTP_OK,
            array('content-type' => 'text/html')
        );
    }

    function buildSimpleForm() {
        $builder = $this->getService('formFactory')
            ->createBuilder('form')
            ->add('status', new NodeStatesType(), array(
                'label' => $this->getTranslator()->trans('node.status'),
                'required' => false
                ))
            ->add('visible', 'choice', array(
                'label' => $this->getTranslator()->trans('node.visible'),
                'choices' => array(true => 'true', false => 'false'),
                'empty_value' => "ignore",
                'required' => false,
                'expanded' => true
                ))
            ->add('locked', 'choice', array(
                'label' => $this->getTranslator()->trans('node.locked'),
                'choices' => array(true => 'true', false => 'false'),
                'empty_value' => "ignore",
                'required' => false,
                'expanded' => true
                ))
            ->add('sterile', 'choice', array(
                'label' => $this->getTranslator()->trans('node.sterile'),
                'choices' => array(true => 'true', false => 'false'),
                'empty_value' => "ignore",
                'required' => false,
                'expanded' => true
                ))
            ->add('hideChildren', 'choice', array(
                'label' => $this->getTranslator()->trans('node.container'),
                'choices' => array(true => 'true', false => 'false'),
                'empty_value' => "ignore",
                'required' => false,
                'expanded' => true
                ))
            ->add('nodeName', 'text', array(
                'label' => $this->getTranslator()->trans('node.name'),
                'required' => false
                ))
            ->add('parent', 'text', array(
                'label' => $this->getTranslator()->trans('node.id.parent'),
                'required' => false
                ));

        return $builder->getForm();
    }
}