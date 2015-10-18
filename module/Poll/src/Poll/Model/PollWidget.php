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

use Application\Utility\ApplicationErrorLogger;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Expression as Expression;
use Zend\Http\PhpEnvironment\RemoteAddress;
use Exception;

class PollWidget extends PollBase
{
    /**
     * Is answer vote exist
     *
     * @param integer $questionId
     * @return boolean
     */
    public function isAnswerVoteExist($questionId)
    {
        $remote = new RemoteAddress;
        $remote->setUseProxy(true);

        $select = $this->select();
        $select->from('poll_answer_track')
            ->columns([
                'id'
            ])
            ->where([
                'question_id' => $questionId,
                'ip' => inet_pton($remote->getIpAddress())
            ]);

        $statement = $this->prepareStatementForSqlObject($select);
        $resultSet = new ResultSet;
        $resultSet->initialize($statement->execute());

        return !empty($resultSet->current());
    }

    /**
     * Add answer vote
     *
     * @param integer $questionId
     * @param integer $answerId
     * @return string|boolean
     */
    public function addAnswerVote($questionId, $answerId)
    {
        try {
            $this->adapter->getDriver()->getConnection()->beginTransaction();

            $remote = new RemoteAddress;
            $remote->setUseProxy(true);

            // add a track info
            $insert = $this->insert()
                ->into('poll_answer_track')
                ->values([
                    'question_id' => $questionId,
                    'answer_id' => $answerId,
                    'ip' => inet_pton($remote->getIpAddress()),
                    'created' => time()
                ]);

            $statement = $this->prepareStatementForSqlObject($insert);
            $statement->execute();

            $this->adapter->getDriver()->getConnection()->commit();
        }
        catch (Exception $e) {
            $this->adapter->getDriver()->getConnection()->rollback();
            ApplicationErrorLogger::log($e);

            return $e->getMessage();
        }

        return true;
    }

    /**
     * Get answer track
     *
     * @param integer $questionId
     * @return array
     */
    public function getAnswerTrack($questionId)
    {
        $processedData = [];

        $select = $this->select();
        $select->from('poll_answer_track')
            ->columns([
                'answer_id',
                'answer_count' => new Expression('count(answer_id)')
            ])
            ->where([
                'question_id' => $questionId
            ])
            ->group('answer_id');

        $statement = $this->prepareStatementForSqlObject($select);
        $resultSet = new ResultSet;
        $resultSet->initialize($statement->execute());

        if (!$resultSet->count()) {
            return $processedData;
        }

        // process track data
        foreach($resultSet as $data)
        {
            $processedData[$data->answer_id] = $data->answer_count;
        }

        return $processedData;
    }

    /**
     * Get answers
     *
     * @param integer $questionId
     * @return array
     */
    public function getAnswers($questionId)
    {
        $select = $this->select();
        $select->from('poll_answer')
            ->columns([
                'id',
                'answer'
            ])
            ->where([
                'question_id' => $questionId
            ])
            ->order('order');

        $statement = $this->prepareStatementForSqlObject($select);
        $resultSet = new ResultSet;
        $resultSet->initialize($statement->execute());

        return $resultSet->toArray();
    }
}