<?php

/**
 * @file classes/submission/PKPSubmissionDAO.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PKPSubmissionDAO
 * @ingroup submission
 * @see Submission
 *
 * @brief Operations for retrieving and modifying Submission objects.
 */

import('lib.pkp.classes.submission.PKPSubmission');
import('lib.pkp.classes.db.SchemaDAO');
import('lib.pkp.classes.plugins.PKPPubIdPluginDAO');

define('ORDERBY_DATE_PUBLISHED', 'datePublished');
define('ORDERBY_TITLE', 'title');

abstract class PKPSubmissionDAO extends SchemaDAO implements PKPPubIdPluginDAO {
	var $cache;
	var $authorDao;

	/** @copydoc SchemaDAO::$schemaName */
	public $schemaName = SCHEMA_SUBMISSION;

	/** @copydoc SchemaDAO::$tableName */
	public $tableName = 'submissions';

	/** @copydoc SchemaDAO::$settingsTableName */
	public $settingsTableName = 'submission_settings';

	/** @copydoc SchemaDAO::$primaryKeyColumn */
	public $primaryKeyColumn = 'submission_id';

	/** @copydoc SchemaDAO::$primaryTableColumns */
	public $primaryTableColumns = [
		'id' => 'submission_id',
		'contextId' => 'context_id',
		'currentPublicationId' => 'current_publication_id',
		'dateStatusModified' => 'date_status_modified',
		'dateSubmitted' => 'date_submitted',
		'lastModified' => 'last_modified',
		'stageId' => 'stage_id',
		'status' => 'status',
		'submissionProgress' => 'submission_progress',
	];

	/**
	 * Constructor.
	 */
	function __construct() {
		parent::__construct();
		$this->authorDao = DAORegistry::getDAO('AuthorDAO');
	}

	/**
	 * Callback for a cache miss.
	 * @param $cache Cache
	 * @param $id string
	 * @return Submission
	 */
	function _cacheMiss($cache, $id) {
		$submission = $this->getById($id, null, false);
		$cache->setCache($id, $submission);
		return $submission;
	}

	/**
	 * Get the submission cache.
	 * @return Cache
	 */
	function _getCache() {
		if (!isset($this->cache)) {
			$cacheManager = CacheManager::getManager();
			$this->cache = $cacheManager->getObjectCache('submissions', 0, array(&$this, '_cacheMiss'));
		}
		return $this->cache;
	}

	/**
	 * Instantiate a new data object.
	 * @return Submission
	 */
	abstract function newDataObject();

	/**
	 * @copydoc SchemaDAO::_fromRow()
	 */
	function _fromRow($row) {
		$submission = parent::_fromRow($row);
		$submission->setData('publications', Services::get('publication')->getMany(['submissionIds' => $submission->getId()]));

		return $submission;
	}

	/**
	 * Get the latest revision id for a submission
	 * @param $submissionId int
	 * @param $contextId int
	 * @return int
	 */
	function getCurrentSubmissionVersionById($submissionId) {
		if (!$submissionId)
			return null;

		$submission = $this->getById($submissionId);

		if ($submission)
			return $submission->getCurrentSubmissionVersion();

		return null;
	}

	/**
	 * Delete a submission.
	 * @param $submission Submission
	 */
	function deleteObject($submission) {
		return $this->deleteById($submission->getId());
	}

	/**
	 * Delete a submission by ID.
	 * @param $submissionId int
	 */
	function deleteById($submissionId) {
		parent::deleteById($submissionId);

		// Delete publications
		$publications = Services::get('publication')->getMany(['submissionIds' => $submissionId]);
		foreach ($publications as $publication) {
			DAORegistry::getDAO('PublicationDAO')->deleteObject($publication);
		}

		// Delete submission files.
		$submission = $this->getById($submissionId);
		assert(is_a($submission, 'Submission'));
		// 'deleteAllRevisionsBySubmissionId' has to be called before 'rmtree'
		// because SubmissionFileDaoDelegate::deleteObjects checks the file
		// and returns false if the file is not there, which makes the foreach loop in
		// SubmissionFileDAO::_deleteInternally not run till the end.
		$submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO'); /* @var $submissionFileDao SubmissionFileDAO */
		$submissionFileDao->deleteAllRevisionsBySubmissionId($submissionId);
		import('lib.pkp.classes.file.SubmissionFileManager');
		$submissionFileManager = new SubmissionFileManager($submission->getContextId(), $submission->getId());
		$submissionFileManager->rmtree($submissionFileManager->getBasePath());

		// $this->authorDao->deleteBySubmissionId($submissionId);

		$reviewRoundDao = DAORegistry::getDAO('ReviewRoundDAO');
		$reviewRoundDao->deleteBySubmissionId($submissionId);

		$editDecisionDao = DAORegistry::getDAO('EditDecisionDAO');
		$editDecisionDao->deleteDecisionsBySubmissionId($submissionId);

		$reviewAssignmentDao = DAORegistry::getDAO('ReviewAssignmentDAO');
		$reviewAssignmentDao->deleteBySubmissionId($submissionId);

		// Delete the queries associated with a submission
		$queryDao = DAORegistry::getDAO('QueryDAO');
		$queryDao->deleteByAssoc(ASSOC_TYPE_SUBMISSION, $submissionId);

		// Delete the stage assignments.
		$stageAssignmentDao = DAORegistry::getDAO('StageAssignmentDAO');
		$stageAssignments = $stageAssignmentDao->getBySubmissionAndStageId($submissionId);
		while ($stageAssignment = $stageAssignments->next()) {
			$stageAssignmentDao->deleteObject($stageAssignment);
		}

		$noteDao = DAORegistry::getDAO('NoteDAO');
		$noteDao->deleteByAssoc(ASSOC_TYPE_SUBMISSION, $submissionId);

		$submissionCommentDao = DAORegistry::getDAO('SubmissionCommentDAO');
		$submissionCommentDao->deleteBySubmissionId($submissionId);

		// Delete any outstanding notifications for this submission
		$notificationDao = DAORegistry::getDAO('NotificationDAO');
		$notificationDao->deleteByAssoc(ASSOC_TYPE_SUBMISSION, $submissionId);

		$submissionEventLogDao = DAORegistry::getDAO('SubmissionEventLogDAO');
		$submissionEventLogDao->deleteByAssoc(ASSOC_TYPE_SUBMISSION, $submissionId);

		$submissionEmailLogDao = DAORegistry::getDAO('SubmissionEmailLogDAO');
		$submissionEmailLogDao->deleteByAssoc(ASSOC_TYPE_SUBMISSION, $submissionId);

		// // Delete controlled vocab lists assigned to this submission
		// $submissionKeywordDao = DAORegistry::getDAO('SubmissionKeywordDAO');
		// $submissionKeywordVocab = $submissionKeywordDao->getBySymbolic(CONTROLLED_VOCAB_SUBMISSION_KEYWORD, ASSOC_TYPE_SUBMISSION, $submissionId);
		// if (isset($submissionKeywordVocab)) {
		// 	$submissionKeywordDao->deleteObject($submissionKeywordVocab);
		// }

		// $submissionDisciplineDao = DAORegistry::getDAO('SubmissionDisciplineDAO');
		// $submissionDisciplineVocab = $submissionDisciplineDao->getBySymbolic(CONTROLLED_VOCAB_SUBMISSION_DISCIPLINE, ASSOC_TYPE_SUBMISSION, $submissionId);
		// if (isset($submissionDisciplineVocab)) {
		// 	$submissionDisciplineDao->deleteObject($submissionDisciplineVocab);
		// }

		// $submissionAgencyDao = DAORegistry::getDAO('SubmissionAgencyDAO');
		// $submissionAgencyVocab = $submissionAgencyDao->getBySymbolic(CONTROLLED_VOCAB_SUBMISSION_AGENCY, ASSOC_TYPE_SUBMISSION, $submissionId);
		// if (isset($submissionAgencyVocab)) {
		// 	$submissionAgencyDao->deleteObject($submissionAgencyVocab);
		// }

		// $submissionLanguageDao = DAORegistry::getDAO('SubmissionLanguageDAO');
		// $submissionLanguageVocab = $submissionLanguageDao->getBySymbolic(CONTROLLED_VOCAB_SUBMISSION_LANGUAGE, ASSOC_TYPE_SUBMISSION, $submissionId);
		// if (isset($submissionLanguageVocab)) {
		// 	$submissionLanguageDao->deleteObject($submissionLanguageVocab);
		// }

		// $submissionSubjectDao = DAORegistry::getDAO('SubmissionSubjectDAO');
		// $submissionSubjectVocab = $submissionSubjectDao->getBySymbolic(CONTROLLED_VOCAB_SUBMISSION_SUBJECT, ASSOC_TYPE_SUBMISSION, $submissionId);
		// if (isset($submissionSubjectVocab)) {
		// 	$submissionSubjectDao->deleteObject($submissionSubjectVocab);
		// }
	}

	/**
	 * @copydoc PKPPubIdPluginDAO::pubIdExists()
	 */
	function pubIdExists($pubIdType, $pubId, $excludePubObjectId, $contextId) {
		$result = $this->retrieve(
			'SELECT COUNT(*)
			FROM submission_settings sst
				INNER JOIN submissions s ON sst.submission_id = s.submission_id
			WHERE sst.setting_name = ? and sst.setting_value = ? and sst.submission_id <> ? AND s.context_id = ?',
			array(
				'pub-id::'.$pubIdType,
				$pubId,
				(int) $excludePubObjectId,
				(int) $contextId
			)
		);
		$returner = $result->fields[0] ? true : false;
		$result->Close();
		return $returner;
	}

	/**
	 * @copydoc PKPPubIdPluginDAO::changePubId()
	 */
	function changePubId($pubObjectId, $pubIdType, $pubId) {
		$idFields = array(
			'submission_id', 'submission_version', 'locale', 'setting_name'
		);
		$updateArray = array(
			'submission_id' => (int) $pubObjectId,
			'locale' => '',
			'setting_name' => 'pub-id::'.$pubIdType,
			'setting_type' => 'string',
			'setting_value' => (string)$pubId
		);
		$this->replace('submission_settings', $updateArray, $idFields);
		$this->flushCache();
	}

	/**
	 * @copydoc PKPPubIdPluginDAO::deletePubId()
	 */
	function deletePubId($pubObjectId, $pubIdType) {
		$settingName = 'pub-id::'.$pubIdType;
		$this->update(
			'DELETE FROM submission_settings WHERE setting_name = ? AND submission_id = ?',
			array(
				$settingName,
				(int)$pubObjectId
			)
		);
		$this->flushCache();
	}

	/**
	 * @copydoc PKPPubIdPluginDAO::deleteAllPubIds()
	 */
	function deleteAllPubIds($contextId, $pubIdType) {
		$settingName = 'pub-id::'.$pubIdType;

		$submissions = $this->getByContextId($contextId);
		while ($submission = $submissions->next()) {
			$this->update(
				'DELETE FROM submission_settings WHERE setting_name = ? AND submission_id = ?',
				array(
					$settingName,
					(int)$submission->getId()
				)
			);
		}
		$this->flushCache();
	}

	/**
	 * Get the ID of the last inserted submission.
	 * @return int
	 */
	function getInsertId() {
		return $this->_getInsertId('submissions', 'submission_id');
	}

	/**
	 * Flush the submission cache.
	 */
	function flushCache() {
		// Because both published_submissions and submissions are
		// cached by submission ID, flush both caches on update.
		$cache = $this->_getCache();
		$cache->flush();
	}

	/**
	 * Get all submissions for a context.
	 * @param $contextId int
	 * @return DAOResultFactory containing matching Submissions
	 */
	function getByContextId($contextId) {
		$result = $this->retrieve(
			'SELECT * FROM ' . $this->tableName . ' WHERE context_id = ?',
			[(int) $contextId]
		);

		return new DAOResultFactory($result, $this, '_fromRow');
	}

	/*
	 * Delete all submissions by context ID.
	 * @param $contextId int
	 */
	function deleteByContextId($contextId) {
		$submissions = $this->getByContextId($contextId);
		while ($submission = $submissions->next()) {
			$this->deleteById($submission->getId());
		}
	}

	/**
	 * Reset the attached licenses of all submissions in a context to context defaults.
	 * @param $contextId int
	 */
	function resetPermissions($contextId) {
		$submissions = $this->getByContextId($contextId);
		while ($submission = $submissions->next()) {
			$submission->setCopyrightYear($submission->_getContextLicenseFieldValue(null, PERMISSIONS_FIELD_COPYRIGHT_YEAR));
			$submission->setCopyrightHolder($submission->_getContextLicenseFieldValue(null, PERMISSIONS_FIELD_COPYRIGHT_HOLDER), null);
			$submission->setLicenseURL($submission->_getContextLicenseFieldValue(null, PERMISSIONS_FIELD_LICENSE_URL));
			$this->updateObject($submission);
		}
		$this->flushCache();
	}

	/**
	 * Get default sort option.
	 * @return string
	 */
	function getDefaultSortOption() {
		return $this->getSortOption(ORDERBY_DATE_PUBLISHED, SORT_DIRECTION_DESC);
	}

	/**
	 * Map a column heading value to a database value for sorting
	 * @param $sortBy string
	 * @return string
	 */
	function getSortMapping($sortBy) {
		switch ($sortBy) {
			case ORDERBY_TITLE:
				return 'st.setting_value';
			case ORDERBY_DATE_PUBLISHED:
				return 'ps.date_published';
			default: return null;
		}
	}

	/**
	 * Get possible sort options.
	 * @return array
	 */
	function getSortSelectOptions() {
		return array(
			$this->getSortOption(ORDERBY_TITLE, SORT_DIRECTION_ASC) => __('catalog.sortBy.titleAsc'),
			$this->getSortOption(ORDERBY_TITLE, SORT_DIRECTION_DESC) => __('catalog.sortBy.titleDesc'),
			$this->getSortOption(ORDERBY_DATE_PUBLISHED, SORT_DIRECTION_ASC) => __('catalog.sortBy.datePublishedAsc'),
			$this->getSortOption(ORDERBY_DATE_PUBLISHED, SORT_DIRECTION_DESC) => __('catalog.sortBy.datePublishedDesc'),
		);
	}

	/**
	 * Get sort option.
	 * @param $sortBy string
	 * @param $sortDir int
	 * @return string
	 */
	function getSortOption($sortBy, $sortDir) {
		return $sortBy .'-' . $sortDir;
	}

	/**
	 * Get sort way for a sort option.
	 * @param $sortOption string concat(sortBy, '-', sortDir)
	 * @return string
	 */
	function getSortBy($sortOption) {
		list($sortBy, $sortDir) = explode("-", $sortOption);
		return $sortBy;
	}

	/**
	 * Get sort direction for a sort option.
	 * @param $sortOption string concat(sortBy, '-', sortDir)
	 * @return int
	 */
	function getSortDirection($sortOption) {
		list($sortBy, $sortDir) = explode("-", $sortOption);
		return $sortDir;
	}
}
