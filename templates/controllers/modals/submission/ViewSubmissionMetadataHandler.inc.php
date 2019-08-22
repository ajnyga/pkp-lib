<?php
/**
 * @file controllers/modals/submission/ViewSubmissionMetadataHandler.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ViewSubmissionMetadataHandler
 * @ingroup controllers_modals_viewSubmissionMetadataHandler
 *
 * @brief Display submission metadata.
 */

// Import the base Handler.
import('classes.handler.Handler');

class ViewSubmissionMetadataHandler extends handler {

	/**
	 * Constructor.
	 */
	function __construct() {
		parent::__construct();
		$this->addRoleAssignment(array(ROLE_ID_REVIEWER), array('display'));
	}

	/**
	 * @copydoc PKPHandler::authorize()
	 */
	function authorize($request, &$args, $roleAssignments) {
		import('lib.pkp.classes.security.authorization.SubmissionAccessPolicy');
		$this->addPolicy(new SubmissionAccessPolicy($request, $args, $roleAssignments));
		return parent::authorize($request, $args, $roleAssignments);
	}

	/**
	 * Display metadata
	 */
	function display($args, $request) {
		$submission = $this->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION);
		$reviewAssignment = $this->getAuthorizedContextObject(ASSOC_TYPE_REVIEW_ASSIGNMENT);
		$templateMgr = TemplateManager::getManager($request);

		if ($reviewAssignment->getReviewMethod() != SUBMISSION_REVIEW_METHOD_DOUBLEBLIND) { /* SUBMISSION_REVIEW_METHOD_BLIND or _OPEN */
			$templateMgr->assign('authors', $submission->getAuthorString());
		}

		$templateMgr->assign(array(
			'title' => $submission->getLocalizedFullTitle(),
			'abstract' => $submission->getLocalizedAbstract(),
		));

		$publication = $submission->getCurrentPublication();
		$submissionKeywordDao = DAORegistry::getDAO('SubmissionKeywordDAO');
		$submissionSubjectDao = DAORegistry::getDAO('SubmissionSubjectDAO');
		$submissionDisciplineDao = DAORegistry::getDAO('SubmissionDisciplineDAO');
		$submissionAgencyDao = DAORegistry::getDAO('SubmissionAgencyDAO');
		$submissionLanguageDao = DAORegistry::getDAO('SubmissionLanguageDAO');

		$keywords = $submissionKeywordDao->getKeywords($publication->getId(), array(AppLocale::getLocale()));
		if ($keywords) {
			$additionalMetadata[] = array('Keywords', implode(', ', $keywords[AppLocale::getLocale()]));
		}
		$subjects = $submissionSubjectDao->getSubjects($publication->getId(), array(AppLocale::getLocale()));
		if ($subjects) {
			$additionalMetadata[] = array('Keywords', implode(', ', $subjects[AppLocale::getLocale()]));
		}
		$disciplines = $submissionDisciplineDao->getDisciplines($publication->getId(), array(AppLocale::getLocale()));
		if ($disciplines) {
			$additionalMetadata[] = array('Disciplines', implode(', ', $disciplines[AppLocale::getLocale()]));
		}
		$agencies = $submissionAgencyDao->getAgencies($publication->getId(), array(AppLocale::getLocale()));
		if ($agencies) {
			$additionalMetadata[] = array('Supporting Agencies', implode(', ', $agencies[AppLocale::getLocale()]));
		}
		$languages = $submissionLanguageDao->getLanguages($publication->getId(), array(AppLocale::getLocale()));
		if ($languages) {
			$additionalMetadata[] = array('Languages', implode(', ', $languages[AppLocale::getLocale()]));
		}		

		$templateMgr->assign('additionalMetadata', $additionalMetadata);

		return $templateMgr->fetchJson('controllers/modals/submission/viewSubmissionMetadata.tpl');

	}
}
