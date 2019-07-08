{**
 * templates/authorDashboard/authorDashboard.tpl
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display the author dashboard.
 *}
{strip}
	{assign var=primaryAuthor value=$submission->getPrimaryAuthor()}
	{if !$primaryAuthor}
		{assign var=authors value=$submission->getAuthors()}
		{assign var=primaryAuthor value=$authors[0]}
	{/if}
	{assign var=submissionTitleSafe value=$submission->getLocalizedTitle()|strip_unsafe_html}
	{if $primaryAuthor}
		{assign var="pageTitleTranslated" value=$primaryAuthor->getFullName()|concat:", ":$submissionTitleSafe}
	{else}
		{assign var="pageTitleTranslated" value=$submissionTitleSafe}
	{/if}
	{include file="common/header.tpl" suppressPageTitle=true}
{/strip}

<div class="pkp_page_content">
	{assign var="uuid" value=""|uniqid|escape}
	<div id="workflow-{$uuid}" class="pkpWorkflow">
		<pkp-header :is-one-line="true" class="pkpWorkflow__header">
			<h1 class="pkpWorkflow__identification">
				<span class="pkpWorkflow__identificationId">{{ submission.id }}</span>
				<span class="pkpWorkflow__identificationDivider">/</span>
				<span class="pkpWorkflow__identificationAuthor">
					{{ currentPublication.authorsStringShort }}
				</span>
				<span class="pkpWorkflow__identificationDivider">/</span>
				<span class="pkpWorkflow__identificationTitle">
					{{ localizeSubmission(currentPublication.title, currentPublication.locale) }}
				</span>
			</h1>
			<template slot="actions">
				<pkp-button
					v-if="uploadFileUrl"
					:label="i18n.uploadFile"
					@click="openFileUpload"
				></pkp-button>
				<pkp-button
					label="{translate key="editor.submissionLibrary"}"
					@click="openLibrary"
				></pkp-button>
			</template>
		</pkp-header>
		<tabs>
			<tab id="workflow" label="{translate key="manager.workflow"}">

				{include file="controllers/notification/inPlaceNotification.tpl" notificationId="authorDashboardNotification" requestOptions=$authorDashboardNotificationRequestOptions}

				{assign var=selectedTabIndex value=0}
				{foreach from=$workflowStages item=stage}
					{if $stage.id < $submission->getStageId()}
						{assign var=selectedTabIndex value=$selectedTabIndex+1}
					{/if}
				{/foreach}

				<script type="text/javascript">
					// Attach the JS file tab handler.
					$(function() {ldelim}
						$('#stageTabs').pkpHandler(
							'$.pkp.controllers.tab.workflow.WorkflowTabHandler',
							{ldelim}
								selected: {$selectedTabIndex},
								emptyLastTab: true
							{rdelim}
						);
					{rdelim});
				</script>
				<div id="stageTabs" class="pkp_controllers_tab">
					<ul>
						{foreach from=$workflowStages item=stage}
							<li class="pkp_workflow_{$stage.path} stageId{$stage.id}{if $stage.statusKey} initiated{/if}">
								<a name="stage-{$stage.path}" class="{$stage.path} stageId{$stage.id}" href="{url router=$smarty.const.ROUTE_COMPONENT component="tab.authorDashboard.AuthorDashboardTabHandler" op="fetchTab" submissionId=$submission->getId() stageId=$stage.id escape=false}">
									{translate key=$stage.translationKey}
									{if $stage.statusKey}
										<span class="pkp_screen_reader">
											{translate key=$stage.statusKey}
										</span>
									{/if}
								</a>
							</li>
						{/foreach}
					</ul>
				</div>
			</tab>
			<tab id="publication" label="{translate key="submission.issueEntry.publicationMetadata"}">
				<div class="pkpPublication" ref="publication" aria-live="polite">
					<pkp-header class="pkpPublication__header">
						<span v-if="submission.publications.length > 1" class="pkpPublication__version">
							<strong tabindex="0">{{ i18n.version }}</strong> {{ workingPublication.id }}
							<dropdown
								class="pkpPublication__versions"
								label="{translate key="publication.version.all"}"
								:is-link="true"
								submenu-label="{translate key="common.submenu"}"
							>
								<ul>
									<li v-for="publication in submission.publications" :key="publication.id">
										<button
											class="pkpDropdown__action"
											:disabled="publication.id === workingPublicationId"
											@click="setWorkingPublicationId(publication)"
										>
											{{ publication.id }} /
											<template v-if="publication.isPublished">
												{translate key="publication.status.published"}
											</template>
											<template v-else-if="!isPublished && publication.issueId">
												{translate key="submissions.scheduled"}
											</template>
											<template v-else>
												{translate key="publication.status.unpublished"}
											</template>
										</button>
									</li>
								</ul>
							</dropdown>
						</span>
						<span class="pkpPublication__status">
							<strong>{{ i18n.status }}</strong>
							<span v-if="!isPublished && !workingPublication.issueId">{translate key="publication.status.unscheduled"}</span>
							<span v-else-if="!isPublished && workingPublication.issueId">{translate key="submissions.scheduled"}</span>
							<span v-else-if="workingPublication.isPublished" class="pkpPublication__statusPublished">{translate key="publication.status.published"}</span>
							<span v-else class="pkpPublication__statusUnpublished">{translate key="publication.status.unpublished"}</span>
						</span>
					</pkp-header>
					<div
						v-if="workingPublication.isPublished"
						class="pkpPublication__versionPublished"
					>
						{translate key="publication.editDisabled"}
					</div>
					<tabs :is-side-tabs="true" class="pkpPublication__tabs">
						<tab id="titleAbstract" label="{translate key="publication.titleAbstract"}">
							.
						</tab>
						<tab id="contributors" label="{translate key="publication.contributors"}">
							<div id="contributors-grid" ref="contributors">
								<spinner></spinner>
							</div>
						</tab>
						<tab id="metadata" label="{translate key="submission.informationCenter.metadata"}">
							.
						</tab>
						<tab v-if="supportsReferences" id="citations" label="{translate key="submission.citations"}">
							.
						</tab>
						<tab id="identifiers" label="{translate key="submission.identifiers"}">
							.
						</tab>
						<tab id="galleys" label="{translate key="submission.layout.galleys"}">
							<div id="representations-grid" ref="representations">
								<spinner></spinner>
							</div>
						</tab>
						<tab id="license" label="{translate key="publication.publicationLicense"}">
							.
						</tab>
						{call_hook name="Template::Workflow::Publication"}
					</tabs>
					<span class="pkpPublication__mask" :class="publicationMaskClasses">
						<spinner></spinner>
					</span>
				</div>
			</tab>
			{call_hook name="Template::Workflow"}
		</tabs>
	</div>
	<script type="text/javascript">
		pkp.registry.init('workflow-{$uuid}', 'WorkflowContainer', {$workflowData|json_encode});
	</script>
</div>

{include file="common/footer.tpl"}
