{**
 * lib/pkp/templates/controllers/modals/submission/viewSubmissionMetadata.tpl
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display submission metadata.
 *}

<div id="viewSubmissionMetadata" class="">
	<h3>{$publication->getLocalizedFullTitle()|strip_unsafe_html}</h3>
	{if $authors}<h4>{$authors|escape}</h4>{/if}
	<div class="abstract">

	</div>
	{if $additionalMetadata}
		<table>
		{foreach $additionalMetadata as $metadata}
			<tr>
				{foreach $metadata as $metadataItem}
					{if $metadataItem@iteration % 2 != 0}
						<th scope="row">{$metadataItem}</th>
					{else}
						<td>{$metadataItem}</td>
					{/if}
				{/foreach}
			</tr>
		{/foreach}
		</table>
	{/if}
</div>
