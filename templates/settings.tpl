{**
 * plugins/generic/forthcoming/settings.tpl
 *
 * Copyright (c) 2014-2026 Simon Fraser University
 * Copyright (c) 2014-2026 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * ForthcomingPlugin settings
 *
 *}
<script>
	$(function() {ldelim}
		$('#forthcomingPluginSettingsForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>

<form class="pkp_form" id="forthcomingPluginSettingsForm" method="post" action="{url router=PKP\core\PKPApplication::ROUTE_COMPONENT op="manage" category="generic" plugin=$pluginName verb="settings" save=true}">
	{csrf}

	{fbvFormArea id="forthcomingIssueIdFormArea"}
		{fbvElement type="select" id="forthcomingIssueId" translate=false from=$issues selected=$forthcomingIssueId label="plugins.generic.forthcoming.settings.description"}
	{/fbvFormArea}

	{fbvFormArea id="forthcomingSortOrderFormArea"}
		{fbvFormSection title="plugins.generic.forthcoming.settings.sortOrder"}
			{fbvElement type="select" id="sortOrder" translate=false from=$sortOrders selected=$sortOrder size=$fbvStyles.size.MEDIUM}
		{/fbvFormSection}
	{/fbvFormArea}

	{fbvFormButtons}
</form>
