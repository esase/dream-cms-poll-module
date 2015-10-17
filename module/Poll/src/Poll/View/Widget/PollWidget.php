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
namespace Poll\View\Widget;

use Page\View\Widget\PageAbstractWidget;

class PollWidget extends PageAbstractWidget
{
    /**
     * Model instance
     *
     * @var \Poll\Model\PollWidget
     */
    protected $model;

    /**
     * Get model
     *
     * @return \Poll\Model\PollWidget
     */
    protected function getModel()
    {
        if (!$this->model) {
            $this->model = $this->getServiceLocator()
                ->get('Application\Model\ModelManager')
                ->getInstance('Poll\Model\PollWidget');
        }

        return $this->model;
    }

    /**
     * Include js and css files
     *
     * @return void
     */
    public function includeJsCssFiles()
    {
        $this->getView()->layoutHeadScript()->
                appendFile($this->getView()->layoutAsset('poll.js', 'js', 'poll'));
    }

    /**
     * Get widget content
     *
     * @return string|boolean
     */
    public function getContent() 
    {
        //TODO: add permission for making votes
        // TODO: Add csrf key

        if (null != ($questionId = $this->getWidgetSetting('poll_question'))) {
            // get a question info
            if (null != ($questionInfo = $this->getModel()->getQuestionInfo($questionId))) {
                // get list of answers
                $answers = $this->getModel()->getAnswers($questionId);

                if (count($answers) > 1)
                {
                    // process post actions
                    if ($this->getRequest()->isPost()) {
                        if (false !== ($action = $this->
                                getRequest()->getPost('widget_action', false)) && $this->getRequest()->isXmlHttpRequest()) {

                            switch ($action) {
                                case 'make_vote' :
                                    return $this->getView()->json([
                                        'data' => $this->makeVote($questionId, $answers)
                                    ]);

                                default :
                            }
                        }
                    }

                    // process get actions
                    if (false !== ($action = $this->getRequest()->
                            getQuery('widget_action')) && $this->getRequest()->isXmlHttpRequest()) {

                        switch ($action) {
                            case 'get_answers' :
                                return $this->getView()->json([
                                    'data' => $this->getPollAnswers($answers)
                                ]);

                            case 'get_results' :
                            default :
                                return $this->getView()->json([
                                    'data' => $this->getPollResult($questionId, $answers)
                                ]);
                        }
                    }

                    return $this->getView()->partial('poll/widget/poll-init', [
                        'widget_url' => $this->getWidgetConnectionUrl(),
                        'connection_id' => $this->widgetConnectionId,
                        'question_info' => $questionInfo,
                        'answers' => $this->getPollAnswers($answers)
                    ]);
                }
            }
        }

        return false;
    }

    /**
     * Make vote
     *
     * @param integer $questionId
     * @param array $answers
     * @return string
     */
    protected function makeVote($questionId, $answers)
    {
        return $this->getPollResult($questionId, $answers);
    }

    /**
     * Get poll result
     *
     * @param integer $questionId
     * @param array $answers
     * @return string
     */
    protected function getPollResult($questionId, $answers)
    {
        return $this->getView()->partial('poll/widget/poll-results', [
            'question_id' => $questionId,
            'answers' => $answers,
        ]);
    }

    /**
     * Get poll answers
     *
     * @param array $answers
     * @return string
     */
    protected function getPollAnswers($answers)
    {
        return $this->getView()->partial('poll/widget/poll-answers', [
            'connection_id' => $this->widgetConnectionId,
            'answers' => $answers
        ]);
    }
}