<?php

/**
 * @file classes/publication/PKPPublicationDAO.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PKPPublicationDAO
 * @ingroup core
 * @see DAO
 *
 * @brief Operations for retrieving and modifying publication objects.
 */
import('lib.pkp.classes.db.SchemaDAO');
import('lib.pkp.classes.publication.Publication');

class PKPPublicationDAO extends SchemaDAO {
	/** @copydoc SchemaDao::$schemaName */
	public $schemaName = SCHEMA_PUBLICATION;

	/** @copydoc SchemaDao::$tableName */
	public $tableName = 'publications';

	/** @copydoc SchemaDao::$settingsTableName */
	public $settingsTableName = 'publication_settings';

	/** @copydoc SchemaDao::$primaryKeyColumn */
	public $primaryKeyColumn = 'publication_id';

	/** @copydoc SchemaDao::$primaryTableColumns */
  public $primaryTableColumns = [
    'id' => 'publication_id',
    'accessStatus' => 'access_status',
    'datePublished' => 'date_published',
		'lastModified' => 'last_modified',
		'locale' => 'locale',
    'primaryContactId' => 'primary_contact_id',
    'publicationDateType' => 'publication_date_type',
    'publicationType' => 'publication_type',
    'sectionId' => 'section_id',
    'submissionId' => 'submission_id',
	];

	/** @var array List of properties that are stored in the controlled_vocab tables. */
	public $controlledVocabProps = ['disciplines', 'keywords', 'language', 'subject', 'supportingAgences'];

	/**
	 * Create a new DataObject of the appropriate class
	 *
	 * @return DataObject
	 */
	public function newDataObject() {
		return new Publication();
	}

	/**
	 * @copydoc SchemaDAO::_fromRow()
	 */
	public function _fromRow($primaryRow) {
		$publication = parent::_fromRow($primaryRow);

		// Get authors
		$publication->setData('authors', Services::get('author')->getMany(['publicationIds' => $publication->getId()]));

		// Get controlled vocab metadata (eg - keywords, etc)
		$submissionKeywordDao = DAORegistry::getDAO('SubmissionKeywordDAO');
		$publication->setData('keywords', $submissionKeywordDao->getKeywords($publication->getId(), ['en_US', 'fr_CA']));

		// ...todo: disciplines, language, subject, supportingAgencies

		return $publication;
	}

	/**
	 * @copydoc SchemaDAO::insertObject()
	 */
	public function insertObject($publication) {

		// Remove the controlled vocabulary from the publication to save it separately
		$controlledVocabKeyedArray = array_flip($this->controlledVocabProps);
		$controlledVocabProps = array_intersect_key($publication->_data, $controlledVocabKeyedArray);
		$publication->_data = array_diff_key($publication->_data, $controlledVocabKeyedArray);

		parent::insertObject($publication);

		// Update the controlled vocabularly
		if (!empty($controlledVocabProps)) {
			foreach ($controlledVocabProps as $prop => $value) {
				switch ($prop) {
					case 'keywords':
						DAORegistry::getDAO('SubmissionKeywordDAO')->insertKeywords($value, $publication->getId());
						break;
					// ...todo: disciplines, language, subject, supportingAgencies
				}
			}
		}

		return $publication->getId();
	}

	/**
	 * @copydoc SchemaDAO::updateObject()
	 */
	public function updateObject($publication)	{

		// Remove the controlled vocabulary from the publication to save it separately
		$controlledVocabKeyedArray = array_flip($this->controlledVocabProps);
		$controlledVocabProps = array_intersect_key($publication->_data, $controlledVocabKeyedArray);
		$publication->_data = array_diff_key($publication->_data, $controlledVocabKeyedArray);

		parent::updateObject($publication);

		// Update the controlled vocabularly
		if (!empty($controlledVocabProps)) {
			foreach ($controlledVocabProps as $prop => $value) {
				switch ($prop) {
					case 'keywords':
						DAORegistry::getDAO('SubmissionKeywordDAO')->insertKeywords($value, $publication->getId());
						break;
					// ...todo: disciplines, language, subject, supportingAgencies
				}
			}
		}
	}

	/**
	 * @copydoc	SchemaDAO::deleteById()
	 */
	public function deleteById($publicationId) {
		parent::deleteById($publicationId);

		// Delete the controlled vocabulary
		DAORegistry::getDAO('SubmissionKeywordDAO')->deleteByPublicationId($publicationId);

		// ...todo: disciplines, language, subject, supportingAgencies
		// ...todo: galleys, participants
	}
}
