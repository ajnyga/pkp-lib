<?php

/**
 * @file controllers/grid/settings/user/form/UserInviteForm.inc.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class UserInviteForm
 * @ingroup controllers_grid_settings_user_invite_form
 *
 * @brief Form for inviting users to apply a role.
 */

import('lib.pkp.classes.form.Form');

class UserInviteForm extends Form {

	/**
	 * Constructor.
	 */
	function __construct() {
		parent::__construct('controllers/grid/settings/user/form/userInviteForm.tpl');

		$this->addCheck(new FormValidator($this, 'userGroupIds', 'required', 'manager.users.roleRequired'));
		$this->addCheck(new FormValidator($this, 'inviteEmail', 'required', 'email.required'));
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
		$userGroupDao = DAORegistry::getDAO('UserGroupDAO'); /* @var $userGroupDao UserGroupDAO */
		$userGroups = $userGroupDao->getByContextId($contextId);
		while ($userGroup = $userGroups->next()) {
			$allUserGroups[(int) $userGroup->getId()] = $userGroup->getLocalizedName();
		}

		$templateMgr->assign([
			'allUserGroups' => $allUserGroups,
		]);

		return $this->fetch($request);
	}

	/**
	 * @copydoc Form::execute()
	 */
	function execute(...$functionArgs) {

		error_log("UserInviteForm::execute");

		parent::execute(...$functionArgs);
	}
}


