<?php

/**
 * @file controllers/grid/settings/user/form/InviteUserForm.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class InviteUserForm
 * @ingroup controllers_grid_settings_invite_user_form
 *
 * @brief Form for inviting users to apply a role.
 */

import('lib.pkp.classes.form.Form');

class InviteUserForm extends Form {

	/** @var Id of the user */
	var $userId;

	/**
	 * Constructor.
	 */
	function __construct($request, $userId = null) {
		parent::__construct('controllers/grid/settings/user/form/inviteUserForm.tpl');

		$this->userId = isset($userId) ? (int) $userId : null;

		if ($userId == null) {
			$this->addCheck(new FormValidator($this, 'inviteEmail', 'required', 'email.required'));
		}
		$this->addCheck(new FormValidator($this, 'userGroupIds', 'required', 'manager.users.inviteRoleRequired'));
		$this->addCheck(new FormValidatorPost($this));
		$this->addCheck(new FormValidatorCSRF($this));
	}

	/**
	 * Assign form data to user-submitted data.
	 * @see Form::readInputData()
	 */
	function readInputData() {
		$this->readUserVars(
			array(
				'userId',
				'inviteEmail',
				'userGroupIds'
			)
		);
		parent::readInputData();
	}

	/**
	 * @copydoc Form::display
	 */
	function display($request = null, $template = null) {

		$context = $request->getContext();
		$contextId = $context ? $context->getId() : CONTEXT_ID_NONE;
		$templateMgr = TemplateManager::getManager($request);
		$allUserGroups = [];
		$assignedUserGroups = [];

		// Fetch all user groups
		$userGroupDao = DAORegistry::getDAO('UserGroupDAO'); /* @var $userGroupDao UserGroupDAO */
		$userGroups = $userGroupDao->getByContextId($contextId);
		while ($userGroup = $userGroups->next()) {
			$allUserGroups[(int) $userGroup->getId()] = $userGroup->getLocalizedName();
		}

		// If userId is given fetch assigned user groups and name
		if ($this->userId){

			$userGroupDao = DAORegistry::getDAO('UserGroupDAO'); /* @var $userGroupDao UserGroupDAO */
			$userGroups = $userGroupDao->getByUserId($this->userId, $contextId);
			while ($userGroup = $userGroups->next()) {
				$assignedUserGroups[] = (int) $userGroup->getId();
			}

			$userDao = DAORegistry::getDAO('UserDAO'); /* @var $userDao UserDAO */
			$user = $userDao->getById($this->userId);

			$templateMgr->assign([
				'fullName' => $user->getFullName(),
			]);
		}

		$templateMgr->assign([
			'userId' => $this->userId,
			'allUserGroups' => $allUserGroups,
			'assignedUserGroups' => $assignedUserGroups,
		]);

		return $this->fetch($request);
	}

	/**
	 * @copydoc Form::execute()
	 */
	function execute(...$functionArgs) {

		/*
		error_log("UserInviteForm::execute");
		error_log(print_r($this->getData('userId'), true));
		error_log(print_r($this->getData('inviteEmail'), true));
		error_log(print_r($this->getData('userGroupIds'), true));
		*/

		parent::execute(...$functionArgs);
	}
}


