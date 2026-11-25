{**
 * templates/content.tpl
 *
 * Copyright (c) 2014-2026 Simon Fraser University
 * Copyright (c) 2014-2026 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * Display Forthcoming articles
 *}

{capture assign="pageTitle"}{translate key="plugins.generic.forthcoming.pageTitle"}{/capture}

{include file="frontend/components/header.tpl" pageTitleTranslated=$pageTitle}

<h1>{$pageTitle|escape}</h1>
<div class="page page_issue">
	<ul class="cmp_article_list articles">
	{foreach from=$forthcoming item=article}
		<li>
			{assign var=articlePath value=$article->getBestId()}
			{assign var=publication value=$article->getCurrentPublication()}
			{assign var="showAuthor" value=false}
			{if !$publication->getData('hideAuthor') && !$sectionHideAuthor[$publication->getData('sectionId')]}
				{assign var="showAuthor" value=true}
			{/if}

			<div class="obj_article_summary">
				<h2 class="title">
					<a id="article-{$article->getId()}" {if $journal}href="{url journal=$journal->getPath() page="article" op="view" path=$articlePath}"{else}href="{url page="article" op="view" path=$articlePath}"{/if}>
						{$publication->getLocalizedTitle(null, 'html')|strip_unsafe_html}
						{if $publication->getLocalizedSubtitle()}
							<span class="subtitle">
								{$publication->getLocalizedSubtitle(null, 'html')|strip_unsafe_html}
							</span>
						{/if}
					</a>
				</h2>

				{if $showAuthor || ($publication->getData('datePublished') && $showDatePublished)}
				<div class="meta">
					{if $showAuthor}
					<div class="authors">
						{$publication->getAuthorString($authorUserGroups)|escape}
					</div>
					{/if}

					{if $showDatePublished && $publication->getData('datePublished')}
						<div class="published">
							{$publication->getData('datePublished')|date_format:$dateFormatShort}
						</div>
					{/if}
				</div>
				{/if}

				{if !$hideGalleys}
					<ul class="galleys_links">
						{foreach from=$article->getGalleys() item=galley}
							{if $primaryGenreIds}
								{assign var="file" value=$galley->getFile()}
								{if !$galley->getData('urlRemote') &&!($file && in_array($file->getGenreId(), $primaryGenreIds))}
									{continue}
								{/if}
							{/if}
							<li>
								{include file="frontend/objects/galley_link.tpl" parent=$article labelledBy="article-{$article->getId()}" hasAccess=true}
							</li>
						{/foreach}
					</ul>
				{/if}

				{call_hook name="Templates::Issue::Issue::Article"}
			</div>
		</li>
	{/foreach}
	</ul>
</div>

{include file="frontend/components/footer.tpl"}
