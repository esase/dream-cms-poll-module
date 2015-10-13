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

use Poll\Event\PollEvent;
use Application\Utility\ApplicationErrorLogger;
use Application\Service\ApplicationSetting as SettingService;
use Application\Utility\ApplicationPagination as PaginationUtility;
use Zend\Paginator\Paginator;
use Zend\Paginator\Adapter\DbSelect as DbSelectPaginator;
use Zend\Db\Sql\Predicate\Like as LikePredicate;
use Zend\Db\Sql\Expression as Expression;
use Exception;

class PollAdministration extends PollBase
{
    /**
     * Add new question
     *
     * @param array $questionInfo
     *      string question
     * @return boolean|string
     */
    public function addQuestion(array $questionInfo)
    {
        try {
            $this->adapter->getDriver()->getConnection()->beginTransaction();

            $insert = $this->insert()
                ->into('poll_question')
                ->values(array_merge($questionInfo, [
                    'created' => time(),
                    'language' => $this->getCurrentLanguage()
                ]));

            $statement = $this->prepareStatementForSqlObject($insert);
            $statement->execute();
            $insertId = $this->adapter->getDriver()->getLastGeneratedValue();

            $this->adapter->getDriver()->getConnection()->commit();
        }
        catch (Exception $e) {
            $this->adapter->getDriver()->getConnection()->rollback();
            ApplicationErrorLogger::log($e);

            return $e->getMessage();
        }

        // fire the add question event
        PollEvent::fireAddQuestionEvent($insertId);

        return true;
    }

    /**
     * Delete a question
     *
     * @param integer $questionId
     * @return boolean|string
     */
    public function deleteQuestion($questionId)
    {
        try {
            $this->adapter->getDriver()->getConnection()->beginTransaction();

            $delete = $this->delete()
                ->from('poll_question')
                ->where([
                    'id' => $questionId
                ]);

            $statement = $this->prepareStatementForSqlObject($delete);
            $result = $statement->execute();

            $this->adapter->getDriver()->getConnection()->commit();
        }
        catch (Exception $e) {
            $this->adapter->getDriver()->getConnection()->rollback();
            ApplicationErrorLogger::log($e);

            return $e->getMessage();
        }

        $result =  $result->count() ? true : false;

        // fire the delete question event
        if ($result) {
            PollEvent::fireDeleteQuestionEvent($questionId);
        }

        return $result;
    }

    /**
     * Get questions
     *
     * @param integer $page
     * @param integer $perPage
     * @param string $orderBy
     * @param string $orderType
     * @param array $filters
     *      string question
     * @return \Zend\Paginator\Paginator
     */
    public function getQuestions($page = 1, $perPage = 0, $orderBy = null, $orderType = null, array $filters = [])
    {
        $orderFields = [
            'id',
            'question',
            'created',
            'answers'
        ];

        $orderType = !$orderType || $orderType == 'desc'
            ? 'desc'
            : 'asc';

        $orderBy = $orderBy && in_array($orderBy, $orderFields)
            ? $orderBy
            : 'id';

        $select = $this->select();
        $select->from(['a' => 'poll_question'])
            ->columns([
                'id',
                'question',
                'created'
            ])
            ->join(
                ['b' => 'poll_answer'],
                'a.id = b.question_id',
                [
                    'answers' => new Expression('count(b.id)')
                ],
                'left'
            )
            ->where([
                'a.language' => $this->getCurrentLanguage()
            ])
            ->group('a.id')
            ->order($orderBy . ' ' . $orderType);

        // filter by name
        if (!empty($filters['question'])) {
            $select->where([
                new LikePredicate('a.question', '%' . $filters['question'] . '%')
            ]);
        }

        $paginator = new Paginator(new DbSelectPaginator($select, $this->adapter));
        $paginator->setCurrentPageNumber($page);
        $paginator->setItemCountPerPage(PaginationUtility::processPerPage($perPage));
        $paginator->setPageRange(SettingService::getSetting('application_page_range'));

        return $paginator;
    }
}