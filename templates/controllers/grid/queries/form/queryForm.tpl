{**
 * templates/controllers/grid/queries/form/queryForm.tpl
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @brief Query grid form
 *
 * @uses $hasParticipants boolean Are any participants available
 * @uses $queryParticipantsListData array JSON-encoded data for the SelectUserListPanel
 *}

 {if !$hasParticipants}
		{translate key="submission.query.noParticipantOptions"}
 {else}
	<script>
		// Attach the handler.
		$(function() {ldelim}
			$('#queryForm').pkpHandler(
				'$.pkp.controllers.form.CancelActionAjaxFormHandler',
				{ldelim}
					cancelUrl: {if $isNew}'{url|escape:javascript op="deleteQuery" queryId=$queryId csrfToken=$csrfToken params=$actionArgs escape=false}'{else}null{/if}
				{rdelim}
			);
		{rdelim});
	</script>

	<form class="pkp_form" id="queryForm" method="post" action="{url op="updateQuery" queryId=$queryId params=$actionArgs}">
		{csrf}

		{include file="controllers/notification/inPlaceNotification.tpl" notificationId="queryFormNotification"}

<<<<<<< HEAD
		{fbvFormSection class="query_participants" title="editor.submission.stageParticipants" required="true"}
			<ul>
				{foreach from=$participantOptions item=participantOption}
					<li>
						{assign var="inputId" value="queryForm-user-"|concat:$participantOption.user->getId()}
						<label for="{$inputId}">
							<input type="checkbox" name="users[]" id="{$inputId}" value="{$participantOption.user->getId()}"{if $participantOption.isParticipant} checked="checked"{/if}>
							<span class="name">{$participantOption.user->getFullName()}</span>
							<span class="role">{$participantOption.userGroup}</span>
						</label>
					</li>
				{/foreach}
			</ul>
		{/fbvFormSection}
=======
		{if $queryParticipantsListData}
			{fbvFormSection}
				{assign var="uuid" value=""|uniqid|escape}
				<div id="queryParticipants-{$uuid}">
					<script type="text/javascript">
						pkp.registry.init('queryParticipants-{$uuid}', 'SelectListPanel', {$queryParticipantsListData});
					</script>
				</div>
			{/fbvFormSection}
		{/if}
>>>>>>> master

		{fbvFormArea id="queryContentsArea"}
			{fbvFormSection title="common.subject" for="subject" required="true"}
				{fbvElement type="text" id="subject" value=$subject required="true"}
			{/fbvFormSection}

			{fbvFormSection title="stageParticipants.notify.message" for="comment" required="true"}
				{fbvElement type="textarea" id="comment" rich=true value=$comment required="true"}
			{/fbvFormSection}
		{/fbvFormArea}

		{fbvFormArea id="queryNoteFilesArea"}
			{url|assign:queryNoteFilesGridUrl router=$smarty.const.ROUTE_COMPONENT component="grid.files.query.QueryNoteFilesGridHandler" op="fetchGrid" params=$actionArgs queryId=$queryId noteId=$noteId escape=false}
			{load_url_in_div id="queryNoteFilesGrid" url=$queryNoteFilesGridUrl}
		{/fbvFormArea}

		<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

		{fbvFormButtons id="addQueryButton"}

	</form>
{/if}
