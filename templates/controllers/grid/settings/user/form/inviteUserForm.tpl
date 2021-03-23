{**
 * templates/controllers/grid/settings/user/form/inviteUserForm.tpl
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * Form for managing roles for a newly created user.
 *}
<script type="text/javascript">
	$(function() {ldelim}
		// Attach the form handler.
		$('#inviteUserForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>
<form class="pkp_form" id="inviteUserForm" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT component="grid.settings.user.UserGridHandler" op="sendInvite"}">
	{csrf}

	{include file="controllers/notification/inPlaceNotification.tpl" notificationId="userRoleFormNotification"}

	{if $userId}
		<p>{$fullName|escape}</p>
		<input type="hidden" id="userId" name="userId" value="{$userId|escape}" />
	{else}
		{fbvFormSection title="grid.user.inviteEmail" size=$fbvStyles.size.MEDIUM}
			{fbvElement type="text" id="inviteEmail" name="inviteEmail"}
		{/fbvFormSection}
	{/if}

	{fbvFormSection}
		{fbvFormSection list=true title="grid.user.userRoles"}
			{foreach from=$allUserGroups item="userGroup" key="id"}
				{fbvElement type="checkbox" id="userGroupIds[]" value=$id checked=in_array($id, $assignedUserGroups) disabled=in_array($id, $assignedUserGroups) label=$userGroup translate=false}
			{/foreach}
		{/fbvFormSection}
	{/fbvFormSection}

	{fbvFormButtons submitText="grid.action.inviteUser"}
</form>
