{**
 * templates/content.tpl
 *
 * Copyright (c) 2014-2024 Simon Fraser University
 * Copyright (c) 2014-2024 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display Forthcoming articles
 *}

{capture assign="pageTitle"}{translate key="plugins.generic.forthcoming.pageTitle"}{/capture}

{include file="frontend/components/header.tpl" pageTitleTranslated=$pageTitle}

<h1>{$pageTitle|escape}</h1>
<div class="page page_issue">
	{if $issue && $issue->getDatePublished()}
		<div class="obj_issue_toc">
			<div class="heading">
				<div class="published">
					<span class="label">
						{translate key="submissions.published"}:
					</span>
					<span class="value">
						{$issue->getDatePublished()|date_format:$dateFormatShort}
					</span>
				</div>
			</div>
		</div>
	{/if}
	<ul class="cmp_article_list articles">
	{foreach from=$forthcoming item=article}
		<li>
			{assign var=articlePath value=$article->getBestId()}
			{assign var=publication value=$article->getCurrentPublication()}
			{if (!$section.hideAuthor && $article->getData('hideAuthor') == \APP\submission\Submission::AUTHOR_TOC_DEFAULT) || $article->getData('hideAuthor') == $smarty.const.AUTHOR_TOC_SHOW}
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
								{if !$galley->getRemoteUrl() && !($file && in_array($file->getGenreId(), $primaryGenreIds))}
									{continue}
								{/if}
							{/if}
							<li>
								{assign var="hasArticleAccess" value=$hasAccess}
								{if $currentContext->getSetting('publishingMode') == \APP\journal\Journal::PUBLISHING_MODE_OPEN || $publication->getData('accessStatus') == \APP\submission\Submission::ARTICLE_ACCESS_OPEN}
									{assign var="hasArticleAccess" value=1}
								{/if}
								{include file="frontend/objects/galley_link.tpl" parent=$article labelledBy="article-{$article->getId()}" hasAccess=$hasArticleAccess purchaseFee=$currentJournal->getData('purchaseArticleFee') purchaseCurrency=$currentJournal->getData('currency')}
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
