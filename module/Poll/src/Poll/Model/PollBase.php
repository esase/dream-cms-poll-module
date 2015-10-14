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
namespace Poll\Model;

use Application\Model\ApplicationAbstractBase;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Predicate\NotIn as NotInPredicate;

class PollBase extends ApplicationAbstractBase
{
    /**
     * Is question free
     *
     * @param string $question
     * @param integer $questionId
     * @return boolean
     */
    public function isQuestionFree($question, $questionId = 0)
    {
        $select = $this->select();
        $select->from('poll_question')
            ->columns([
                'id'
            ])
            ->where([
                'question' => $question,
                'language' => $this->getCurrentLanguage()
            ]);

        if ($questionId) {
            $select->where([
                new NotInPredicate('id', [$questionId])
            ]);
        }

        $statement = $this->prepareStatementForSqlObject($select);
        $resultSet = new ResultSet;
        $resultSet->initialize($statement->execute());

        return $resultSet->current() ? false : true;
    }

    /**
     * Get question info
     *
     * @param integer $id
     * @param boolean $currentLanguage
     * @return array
     */
    public function getQuestionInfo($id, $currentLanguage = true)
    {
        $select = $this->select();
        $select->from('poll_question')
            ->columns([
                'id',
                'question'
            ])
            ->where([
                'id' => $id
            ]);

        if ($currentLanguage) {
            $select->where([
                'language' => $this->getCurrentLanguage()
            ]);
        }

        $statement = $this->prepareStatementForSqlObject($select);
        $result = $statement->execute();

        return $result->current();
    }

    /**
     * Get answer info
     *
     * @param integer $id
     * @param boolean $currentLanguage
     * @return array
     */
    public function getAnswerInfo($id, $currentLanguage = true)
    {
        $select = $this->select();
        $select->from(['a' => 'poll_answer'])
            ->columns([
                'id',
                'answer',
                'question_id',
                'created',
                'order'
            ])
            ->join(
                ['b' => 'poll_question'],
                'a.question_id = b.id',
                [
                    'question'
                ]
            )
            ->where([
                'a.id' => $id
            ]);

        if ($currentLanguage) {
            $select->where([
                'b.language' => $this->getCurrentLanguage()
            ]);
        }

        $statement = $this->prepareStatementForSqlObject($select);
        $result = $statement->execute();

        return $result->current();
    }
}