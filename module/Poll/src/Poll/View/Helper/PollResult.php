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
namespace Poll\View\Helper;

use Application\Service\ApplicationServiceLocator;
use Zend\View\Helper\AbstractHelper;

class PollResult extends AbstractHelper
{
    /**
     * Question id
     *
     * @var integer
     */
    protected $questionId;

    /**
     * Answer track
     *
     * @var array
     */
    protected $answerTrack;

    /**
     * Answer track amount
     *
     * @var integer
     */
    protected $answerTrackAmount;

    /**
     * Model instance
     *
     * @var \Poll\Model\PollWidget
     */
    protected $model;

    /**
     * Poll result
     *
     * @return \Poll\View\Helper\PollResult
     */
    public function __invoke()
    {
        return $this;
    }

    /**
     * Set question id
     *
     * @param integer $questionId
     * @return \Poll\View\Helper\PollResult
     */
    public function setQuestionId($questionId)
    {
        $this->questionId  = $questionId;
        $this->answerTrack = $this->getModel()->getAnswerTrack($questionId);
        $this->answerTrackAmount = array_sum($this->answerTrack);
    }

    /**
     * Get track value
     *
     * @param integer $answerId
     * @return integer
     */
    public function getTrackValue($answerId)
    {
        return !empty($this->answerTrack[$answerId])
            ? $this->answerTrack[$answerId]
            : 0;
    }

    /**
     * Get track stat
     *
     * @param integer $answerId
     * @return integer
     */
    public function getTrackStat($answerId)
    {
        $trackValue = $this->getTrackValue($answerId);

        return $trackValue
            ? $trackValue * 100 / $this->answerTrackAmount
            : 0;
    }

    /**
     * Get background color
     *
     * @param integer $index
     * @param string $prefix
     * @return string
     */
    public function getBackgroundColor($index, $prefix = 'poll')
    {
        $hash = md5($prefix . $index);

        $r = hexdec(substr($hash, 0, 2));
        $g = hexdec(substr($hash, 2, 2));
        $b = hexdec(substr($hash, 4, 2));

        return $r . ',' . $g . ',' . $b;
    }

    /**
     * Get model
     *
     * @return \Poll\Model\PollWidget
     */
    protected function getModel()
    {
        if (!$this->model) {
            $this->model = ApplicationServiceLocator::getServiceLocator()
                ->get('Application\Model\ModelManager')
                ->getInstance('Poll\Model\PollWidget');
        }

        return $this->model;
    }
}