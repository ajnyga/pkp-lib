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
		$context = $request->getContext();
		$templateMgr = TemplateManager::getManager($request);
		$publication = $submission->getCurrentPublication();

		if ($reviewAssignment->getReviewMethod() != SUBMISSION_REVIEW_METHOD_DOUBLEBLIND) { /* SUBMISSION_REVIEW_METHOD_BLIND or _OPEN */
			$userGroupDao = DAORegistry::getDAO('UserGroupDAO');
			$userGroups = $userGroupDao->getByContextId($context->getId());
			$templateMgr->assign('authors', $publication->getAuthorString($userGroups));
		}

		$templateMgr->assign('publication', $publication);

		if ($publication->getData('keywords')) {
			$additionalMetadata[] = array(__('common.keywords'), implode(', ', $publication->getData('keywords')[AppLocale::getLocale()]));
		}
		if ($publication->getData('subjects')) {
			$additionalMetadata[] = array(__('common.subjects'), implode(', ', $publication->getData('subjects')[AppLocale::getLocale()]));			
		}
		if ($publication->getData('disciplines')) {
			$additionalMetadata[] = array(__('common.discipline'), implode(', ', $publication->getData('disciplines')[AppLocale::getLocale()]));
		}
		if ($publication->getData('agencies')) {
			$additionalMetadata[] = array(__('submission.agencies'), implode(', ', $publication->getData('agencies')[AppLocale::getLocale()]));
		}
		if ($publication->getData('languages')) {
			$additionalMetadata[] = array(__('common.languages'), implode(', ', $publication->getData('languages')[AppLocale::getLocale()]));
		}		

		$templateMgr->assign('additionalMetadata', $additionalMetadata);

		return $templateMgr->fetchJson('controllers/modals/submission/viewSubmissionMetadata.tpl');

	}
}
