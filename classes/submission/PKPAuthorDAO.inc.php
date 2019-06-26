<?php

/**
 * @file classes/submission/PKPAuthorDAO.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2000-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PKPAuthorDAO
 * @ingroup submission
 * @see PKPAuthor
 *
 * @brief Operations for retrieving and modifying PKPAuthor objects.
 */

import('lib.pkp.classes.db.SchemaDAO');
import('lib.pkp.classes.submission.PKPAuthor');

abstract class PKPAuthorDAO extends SchemaDAO {
	/** @copydoc SchemaDao::$schemaName */
	public $schemaName = SCHEMA_AUTHOR;

	/** @copydoc SchemaDao::$tableName */
	public $tableName = 'authors';

	/** @copydoc SchemaDao::$settingsTableName */
	public $settingsTableName = 'author_settings';

	/** @copydoc SchemaDao::$primaryKeyColumn */
	public $primaryKeyColumn = 'author_id';

	/** @copydoc SchemaDao::$primaryTableColumns */
	public $primaryTableColumns = [
		'id' => 'author_id',
		'email' => 'email',
		'includeInBrowse' => 'include_in_browse',
		'lastModified' => 'last_modified',
		'publicationId' => 'publication_id',
		'seq' => 'seq',
		'userGroupId' => 'user_group_id',
	];

	/**
	 * Get a new data object
	 * @return DataObject
	 */
	function newDataObject() {
		return new Author();
	}

	/**
	 * Retrieve all authors for a publication.
	 * @param $publicationId int Publication ID.
	 * @param $sortByAuthorId bool Use author Ids as indexes in the array
	 * @param $useIncludeInBrowse bool Whether to limit to just include_in_browse authors
	 * @return array Authors ordered by sequence
	 */
	function getByPublicationId($publicationId, $sortByAuthorId = false, $useIncludeInBrowse = false) {
		$authors = array();
		$params = array((int) $publicationId);
		if ($useIncludeInBrowse) $params[] = 1;

		$result = $this->retrieve(
			'SELECT DISTINCT a.*, ug.show_title, p.locale
			FROM authors a
				JOIN user_groups ug ON (a.user_group_id=ug.user_group_id)
				JOIN publications p ON (p.publication_id = a.publication_id)
				LEFT JOIN author_settings au ON (au.author_id = a.author_id)
			WHERE	a.publication_id = ? ' .
			($useIncludeInBrowse ? ' AND a.include_in_browse = ?' : '')
			. ' ORDER BY seq',
			$params
		);

		while (!$result->EOF) {
			$row = $result->getRowAssoc(false);
			if ($sortByAuthorId) {
				$authorId = $row['author_id'];
				$authors[$authorId] = $this->_fromRow($row);
			} else {
				$authors[] = $this->_fromRow($row);
			}
			$result->MoveNext();
		}

		$result->Close();
		return $authors;
	}

	/**
	 * Retrieve the number of authors assigned to a submission
	 * @param $submissionId int Submission ID.
	 * @return int
	 */
	function getAuthorCountBySubmissionId($submissionId, $submissionVersion = null) {
		$params = array((int) $submissionId);

		if ($submissionVersion) {
			$params[] = (int) $submissionVersion;
		}

		$result = $this->retrieve(
			'SELECT COUNT(*) FROM authors WHERE submission_id = ?'
			. ($submissionVersion ? ' AND submission_version = ? ' : ' AND is_current_submission_version = 1'),
			$params
		);

		$returner = $result->fields[0];

		$result->Close();
		return $returner;
	}

	/**
	 * Sequentially renumber a submission's authors in their sequence order.
	 * @param $submissionId int Submission ID.
	 */
	function resequenceAuthors($submissionId, $submissionVersion = null) {
		$params = array((int) $submissionId);

		if ($submissionVersion) {
			$params[] = (int) $submissionVersion;
		}

		$result = $this->retrieve(
			'SELECT author_id FROM authors WHERE submission_id = ?'
			. ($submissionVersion ? ' AND submission_version = ? ' : ' AND is_current_submission_version = 1')
			.' ORDER BY seq',
			$params
		);

		for ($i=1; !$result->EOF; $i++) {
			list($authorId) = $result->fields;
			$this->update(
				'UPDATE authors SET seq = ? WHERE author_id = ?',
				array(
					$i,
					$authorId
				)
			);

			$result->MoveNext();
		}
		$result->Close();
	}

	/**
	 * Retrieve the primary author for a submission.
	 * @param $submissionId int Submission ID.
	 * @return Author
	 */
	function getPrimaryContact($submissionId, $submissionVersion = null) {
		$params = array((int) $submissionId);

		if ($submissionVersion) {
			$params[] = (int) $submissionVersion;
		}

		$result = $this->retrieve(
			'SELECT a.*, ug.show_title, s.locale
			FROM authors a
				JOIN user_groups ug ON (a.user_group_id=ug.user_group_id)
				JOIN submissions s ON (s.submission_id = a.submission_id)
			WHERE a.submission_id = ?'
			. ($submissionVersion ? ' AND a.submission_version = ? ' : ' AND a.is_current_submission_version = 1')
			. ' AND a.primary_contact = 1',
			$params
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = $this->_fromRow($result->GetRowAssoc(false));
		}
		$result->Close();
		return $returner;
	}

	/**
	 * Remove other primary contacts from a submission and set to authorId
	 * @param $authorId int Author ID.
	 * @param $submissionId int Submission ID.
	 */
	function resetPrimaryContact($authorId, $submissionId, $submissionVersion = null) {
		$params = array((int) $submissionId);
		if ($submissionVersion) {
			$params[] = (int) $submissionVersion;
		}

		$this->update(
			'UPDATE authors SET primary_contact = 0
			WHERE primary_contact = 1
			AND submission_id = ?'
			. ($submissionVersion ? ' AND submission_version = ? ' : ' AND is_current_submission_version = 1'),
			$params
		);

		$params = array((int) $authorId, (int) $submissionId);

		$this->update(
			'UPDATE authors SET primary_contact = 1 WHERE author_id = ? AND submission_id = ?',
			$params
		);
	}

	/**
	 * Update author names when publication locale changes.
	 * @param $publicationId int
	 * @param $oldLocale string
	 * @param $newLocale string
	 */
	function changePublicationLocale($publicationId, $oldLocale, $newLocale) {
		$authors = $this->getByPublicationId($publicationId);
		foreach ($authors as $author) {
			if (empty($author->getGivenName($newLocale))) {
				if (empty($author->getFamilyName($newLocale)) && empty($author->getPreferredPublicName($newLocale))) {
					// if no name exists for the new locale
					// copy all names with the old locale to the new locale
					$author->setGivenName($author->getGivenName($oldLocale), $newLocale);
					$author->setFamilyName($author->getFamilyName($oldLocale), $newLocale);
					$author->setPreferredPublicName($author->getPreferredPublicName($oldLocale), $newLocale);
				} else {
					// if the given name does not exist, but one of the other names do exist
					// copy only the given name with the old locale to the new locale, because the given name is required
					$author->setGivenName($author->getGivenName($oldLocale), $newLocale);
				}
				$this->updateObject($author);
			}
		}
	}


	/**
	 * Get the ID of the last inserted author.
	 * @return int
	 */
	function getInsertId() {
		return $this->_getInsertId('authors', 'author_id');
	}

	/**
	 * Delete authors by submission.
	 * @param $submissionId int
	 */
	function deleteBySubmissionId($submissionId, $submissionVersion = null) {
		$authors = $this->getBySubmissionId($submissionId, false, false, $submissionVersion);
		foreach ($authors as $author) {
			$this->deleteObject($author);
		}
	}
}
