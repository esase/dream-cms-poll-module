<?php

/**
 * EXHIBIT A. Common Public Attribution License Version 1.0
 * The contents of this file are subject to the Common Public Attribution License Version 1.0 (the “License”);
 * you may not use this file except in compliance with the License. You may obtain a copy of the License at
 * http://www.dream-cms.kg/en/license. The License is based on the Mozilla Public License Version 1.1
 * but Sections 14 and 15 have been added to cover use of software over a computer network and provide for
 * limited attribution for the Original Developer. In addition, Exhibit A has been modified to be consistent
 * with Exhibit B. Software distributed under the License is distributed on an “AS IS” basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License for the specific language
 * governing rights and limitations under the License. The Original Code is Dream CMS software.
 * The Initial Developer of the Original Code is Dream CMS (http://www.dream-cms.kg).
 * All portions of the code written by Dream CMS are Copyright (c) 2014. All Rights Reserved.
 * EXHIBIT B. Attribution Information
 * Attribution Copyright Notice: Copyright 2014 Dream CMS. All rights reserved.
 * Attribution Phrase (not exceeding 10 words): Powered by Dream CMS software
 * Attribution URL: http://www.dream-cms.kg/
 * Graphic Image as provided in the Covered Code.
 * Display of Attribution Information is required in Larger Works which are defined in the CPAL as a work
 * which combines Covered Code or portions thereof with code not governed by the terms of the CPAL.
 */
namespace Poll\Controller;

use Application\Controller\ApplicationAbstractAdministrationController;
use Zend\View\Model\ViewModel;

class PollAdministrationController extends ApplicationAbstractAdministrationController
{
    /**
     * Model instance
     *
     * @var \Poll\Model\PollAdministration
     */
    protected $model;

    /**
     * Get model
     *
     * @return \Poll\Model\PollAdministration
     */
    protected function getModel()
    {
        if (!$this->model) {
            $this->model = $this->getServiceLocator()
                ->get('Application\Model\ModelManager')
                ->getInstance('Poll\Model\PollAdministration');
        }

        return $this->model;
    }

    /**
     * Default action
     */
    public function indexAction()
    {
        // redirect to list action
        return $this->redirectTo('polls-administration', 'list-questions');
    }

    /**
     * Polls questions list
     */
    public function listQuestionsAction()
    {
        // check the permission and increase permission's actions track
        if (true !== ($result = $this->aclCheckPermission())) {
            return $result;
        }

        $filters = [];

        // get a filter form
        $filterForm = $this->getServiceLocator()
            ->get('Application\Form\FormManager')
            ->getInstance('Poll\Form\PollQuestionFilter');

        $request = $this->getRequest();
        $filterForm->getForm()->setData($request->getQuery(), false);

        // check the filter form validation
        if ($filterForm->getForm()->isValid()) {
            $filters = $filterForm->getForm()->getData();
        }

        // get data
        $paginator = $this->getModel()->getQuestions($this->
                getPage(), $this->getPerPage(), $this->getOrderBy(), $this->getOrderType(), $filters);

        return new ViewModel([
            'filter_form' => $filterForm->getForm(),
            'paginator' => $paginator,
            'order_by' => $this->getOrderBy(),
            'order_type' => $this->getOrderType(),
            'per_page' => $this->getPerPage()
        ]);
    }

    /**
     * Delete selected questions
     */
    public function deleteQuestionsAction()
    {
        $request = $this->getRequest();

        if ($request->isPost() &&
                $this->applicationCsrf()->isTokenValid($request->getPost('csrf'))) {

            if (null !== ($questionsIds = $request->getPost('questions', null))) {
                // delete selected questions
                $deleteResult = false;
                $deletedCount = 0;

                foreach ($questionsIds as $questionId) {
                    // get question info
                    if (null == ($questionInfo = $this->getModel()->getQuestionInfo($questionId))) {
                        continue;
                    }

                    // check the permission and increase permission's actions track
                    if (true !== ($result = $this->aclCheckPermission(null, true, false))) {
                        $this->flashMessenger()
                            ->setNamespace('error')
                            ->addMessage($this->getTranslator()->translate('Access Denied'));

                        break;
                    }

                    // delete the question
                    if (true !== ($deleteResult = $this->getModel()->deleteQuestion($questionInfo['id']))) {
                        $this->flashMessenger()
                            ->setNamespace('error')
                            ->addMessage(($deleteResult ? $this->getTranslator()->translate($deleteResult)
                                : $this->getTranslator()->translate('Error occurred')));

                        break;
                    }

                    $deletedCount++;
                }

                if (true === $deleteResult) {
                    $message = $deletedCount > 1
                        ? 'Selected questions have been deleted'
                        : 'The selected question has been deleted';

                    $this->flashMessenger()
                        ->setNamespace('success')
                        ->addMessage($this->getTranslator()->translate($message));
                }
            }
        }

        // redirect back
        return $request->isXmlHttpRequest()
            ? $this->getResponse()
            : $this->redirectTo('polls-administration', 'list-questions', [], true);
    }

    /**
     * Edit question action
     */
    public function editQuestionAction()
    {
        // get the question info
        if (null == ($question = $this->
                getModel()->getQuestionInfo($this->getSlug()))) {

            return $this->redirectTo('polls-administration', 'list-questions');
        }

        // get the question form
        $questionForm = $this->getServiceLocator()
            ->get('Application\Form\FormManager')
            ->getInstance('Poll\Form\PollQuestion')
            ->setModel($this->getModel())
            ->setCategoryId($question['id']);

        $questionForm->getForm()->setData($question);

        $request = $this->getRequest();

        // validate the form
        if ($request->isPost()) {
            // fill the form with received values
            $questionForm->getForm()->setData($request->getPost(), false);

            // save data
            if ($questionForm->getForm()->isValid()) {
                // check the permission and increase permission's actions track
                if (true !== ($result = $this->aclCheckPermission())) {
                    return $result;
                }

                // edit the question
                if (true === ($result = $this->getModel()->
                            editQuestion($question['id'], $questionForm->getForm()->getData()))) {

                    $this->flashMessenger()
                        ->setNamespace('success')
                        ->addMessage($this->getTranslator()->translate('Question has been edited'));
                }
                else {
                    $this->flashMessenger()
                        ->setNamespace('error')
                        ->addMessage($this->getTranslator()->translate($result));
                }

                return $this->redirectTo('polls-administration', 'edit-question', [
                    'slug' => $question['id']
                ]);
            }
        }

        return new ViewModel([
            'csrf_token' => $this->applicationCsrf()->getToken(),
            'question' => $question,
            'question_form' => $questionForm->getForm()
        ]);
    }

    /**
     * Add new question action
     */
    public function addQuestionAction()
    {
        // get a question form
        $questionForm = $this->getServiceLocator()
            ->get('Application\Form\FormManager')
            ->getInstance('Poll\Form\PollQuestion')
            ->setModel($this->getModel());

        $request  = $this->getRequest();

        // validate the form
        if ($request->isPost()) {
            // fill the form with received values
            $questionForm->getForm()->setData($request->getPost(), false);

            // save data
            if ($questionForm->getForm()->isValid()) {
                // check the permission and increase permission's actions track
                if (true !== ($result = $this->aclCheckPermission())) {
                    return $result;
                }

                // add a new question
                if (true === ($result = $this->getModel()->addQuestion($questionForm->getForm()->getData()))) {
                    $this->flashMessenger()
                        ->setNamespace('success')
                        ->addMessage($this->getTranslator()->translate('Question has been added'));
                }
                else {
                    $this->flashMessenger()
                        ->setNamespace('error')
                        ->addMessage($this->getTranslator()->translate($result));
                }

                return $this->redirectTo('polls-administration', 'add-question');
            }
        }

        return new ViewModel([
            'question_form' => $questionForm->getForm()
        ]);
    }

    /**
     * Browse answers
     */
    public function browseAnswersAction()
    {
        // check the permission and increase permission's actions track
        if (true !== ($result = $this->aclCheckPermission())) {
            return $result;
        }

        // get the question info
        if (null == ($question = $this->getModel()->getQuestionInfo($this->getSlug()))) {
            return $this->redirectTo('polls-administration', 'list-questions');
        }

        // get data
        $paginator = $this->getModel()->getAnswers($question['id'],
                $this->getPage(), $this->getPerPage(), $this->getOrderBy(), $this->getOrderType());

        return new ViewModel([
            'csrf_token' => $this->applicationCsrf()->getToken(),
            'question' => $question,
            'paginator' => $paginator,
            'order_by' => $this->getOrderBy(),
            'order_type' => $this->getOrderType(),
            'per_page' => $this->getPerPage()
        ]);
    }

    /**
     * Add answer action
     */
    public function addAnswerAction()
    {
        // get the question info
        if (null == ($question = $this->getModel()->
                getQuestionInfo($this->params()->fromQuery('question', -1)))) {

            return $this->redirectTo('polls-administration', 'list-questions');
        }

        // get an answer form
        $answerForm = $this->getServiceLocator()
            ->get('Application\Form\FormManager')
            ->getInstance('Poll\Form\PollAnswer');

        $request  = $this->getRequest();

        // validate the form
        if ($request->isPost()) {
            // fill the form with received values
            $answerForm->getForm()->setData($request->getPost(), false);

            // save data
            if ($answerForm->getForm()->isValid()) {
                // check the permission and increase permission's actions track
                if (true !== ($result = $this->aclCheckPermission())) {
                    return $result;
                }

                // add the answer
                if (true === ($result = $this->getModel()->addAnswer($question['id'], $answerForm->getForm()->getData()))) {
                    $this->flashMessenger()
                        ->setNamespace('success')
                        ->addMessage($this->getTranslator()->translate('Answer has been added'));
                }
                else {
                    $this->flashMessenger()
                        ->setNamespace('error')
                        ->addMessage($this->getTranslator()->translate($result));
                }

                return $this->redirectTo('polls-administration', 'add-answer', [], false, [
                    'question' => $question['id']
                ]);
            }
        }

        return new ViewModel([
            'csrf_token' => $this->applicationCsrf()->getToken(),
            'question' => $question,
            'answer_form' => $answerForm->getForm()
        ]);
    }

    /**
     * Delete selected answers
     */
    public function deleteAnswersAction()
    {
        $request = $this->getRequest();

        if ($request->isPost() &&
                $this->applicationCsrf()->isTokenValid($request->getPost('csrf'))) {

            if (null !== ($answersIds = $request->getPost('answers', null))) {
                // delete selected answers
                $deleteResult = false;
                $deletedCount = 0;

                foreach ($answersIds as $answerId) {
                    // get answer info
                    if (null == ($answerInfo = $this->getModel()->getAnswerInfo($answerId))) {
                        continue;
                    }

                    // check the permission and increase permission's actions track
                    if (true !== ($result = $this->aclCheckPermission(null, true, false))) {
                        $this->flashMessenger()
                            ->setNamespace('error')
                            ->addMessage($this->getTranslator()->translate('Access Denied'));

                        break;
                    }

                    // delete the answer
                    if (true !== ($deleteResult = $this->getModel()->deleteAnswer($answerId))) {
                        $this->flashMessenger()
                            ->setNamespace('error')
                            ->addMessage(($deleteResult ? $this->getTranslator()->translate($deleteResult)
                                : $this->getTranslator()->translate('Error occurred')));

                        break;
                    }

                    $deletedCount++;
                }

                if (true === $deleteResult) {
                    $message = $deletedCount > 1
                        ? 'Selected answers have been deleted'
                        : 'The selected answer has been deleted';

                    $this->flashMessenger()
                        ->setNamespace('success')
                        ->addMessage($this->getTranslator()->translate($message));
                }
            }
        }

        // redirect back
        return $request->isXmlHttpRequest()
            ? $this->getResponse()
            : $this->redirectTo('polls-administration', 'browse-answers', [], true);
    }

    /**
     * Edit answer action
     */
    public function editAnswerAction()
    {
        // get the answer info
        if (null == ($answer = $this->getModel()->getAnswerInfo($this->getSlug()))) {
            return $this->redirectTo('polls-administration', 'list-questions');
        }

        // get an answer form
        $answerForm = $this->getServiceLocator()
            ->get('Application\Form\FormManager')
            ->getInstance('Poll\Form\PollAnswer');

        // fill the form with default values
        $answerForm->getForm()->setData($answer);
        $request = $this->getRequest();

        // validate the form
        if ($request->isPost()) {
            // fill the form with received values
            $answerForm->getForm()->setData($request->getPost(), false);

            // save data
            if ($answerForm->getForm()->isValid()) {
                // check the permission and increase permission's actions track
                if (true !== ($result = $this->aclCheckPermission())) {
                    return $result;
                }

                // edit the answer
                if (true === ($result =
                        $this->getModel()->editAnswer($answer['id'], $answerForm->getForm()->getData()))) {

                    $this->flashMessenger()
                        ->setNamespace('success')
                        ->addMessage($this->getTranslator()->translate('Answer has been edited'));
                }
                else {
                    $this->flashMessenger()
                        ->setNamespace('error')
                        ->addMessage($this->getTranslator()->translate($result));
                }

                return $this->redirectTo('polls-administration', 'edit-answer', [
                    'slug' => $answer['id']
                ]);
            }
        }

        return new ViewModel([
            'csrf_token' => $this->applicationCsrf()->getToken(),
            'answer_form' => $answerForm->getForm(),
            'answer' => $answer
        ]);
    }
}