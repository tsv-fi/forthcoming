<?php

/**
 * @file classes/Handler.php
 *
 * Copyright (c) 2014-2026 Simon Fraser University
 * Copyright (c) 2014-2026 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @package plugins.generic.forthcoming
 *
 * @class Handler
 *
 * @brief Find forthcoming content and display it when requested.
 */

namespace APP\plugins\generic\forthcoming\classes;

use APP\core\Request;
use APP\facades\Repo;
use APP\plugins\generic\forthcoming\ForthcomingPlugin;
use APP\submission\Submission;
use APP\template\TemplateManager;
use PKP\db\DAORegistry;
use PKP\security\Role;
use PKP\submission\GenreDAO;
use PKP\userGroup\UserGroup;

class Handler extends \APP\handler\Handler
{
    public function __construct(
        public ForthcomingPlugin $plugin,
        public int $forthcomingIssueId
    ) {
        parent::__construct();
    }

    /**
     * @copydoc PKPHandler::index()
     *
     * @param Request $request Request
     */
    public function index($args, $request): void
    {
        $contextId = $request->getContext()->getId();
        $templateMgr = TemplateManager::getManager($request);
        $this->setupTemplate($request);

        $collector = Repo::submission()->getCollector();
        $orderDirection = $this->plugin->getSetting($contextId, 'sortOrder') === 'desc'
            ? $collector::ORDER_DIR_DESC
            : $collector::ORDER_DIR_ASC;

        $submissions = $collector
            ->filterByContextIds([$contextId])
            ->filterByIssueIds([$this->forthcomingIssueId])
            ->filterByStatus([Submission::STATUS_PUBLISHED])
            ->orderBy($collector::ORDERBY_DATE_PUBLISHED, $orderDirection)
            ->getMany()
            ->filter(fn (Submission $submission) => (int) ($publication = $submission->getCurrentPublication())?->getData('issueId') === $this->forthcomingIssueId && $publication->getData('datePublished'))
            ->toArray();

        // Repo::userGroup()->getByRoleIds() uses cursor(), which skips
        // loading settings (e.g. the group name shown next to authors)
        $authorUserGroups = UserGroup::withRoleIds([Role::ROLE_ID_AUTHOR])
            ->withContextIds([$contextId])
            ->get();

        $sectionHideAuthor = Repo::section()->getCollector()
            ->filterByContextIds([$contextId])
            ->getMany()
            ->mapWithKeys(fn ($section) => [$section->getId() => (bool) $section->getData('hideAuthor')])
            ->toArray();

        /** @var GenreDAO $genreDao */
        $genreDao = DAORegistry::getDAO('GenreDAO');
        $primaryGenreIds = array_map(
            fn ($genre) => $genre->getId(),
            $genreDao->getPrimaryByContextId($contextId)->toArray()
        );

        $templateMgr->assign([
            'forthcoming' => $submissions,
            'authorUserGroups' => $authorUserGroups,
            'sectionHideAuthor' => $sectionHideAuthor,
            'primaryGenreIds' => $primaryGenreIds,
            'showDatePublished' => true,
        ]);
        $templateMgr->display($this->plugin->getTemplateResource('content.tpl'));
    }
}
