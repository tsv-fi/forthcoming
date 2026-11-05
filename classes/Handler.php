<?php
/**
 * @file classes/Handler.php
 *
 * Copyright (c) 2014-2024 Simon Fraser University
 * Copyright (c) 2014-2024 John Willinsky
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
use PKP\security\Role;
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

        if (!$this->forthcomingIssueId) {
            return;
        }

        $collector = Repo::submission()->getCollector();

        $submissions = $collector
            ->filterByContextIds([$contextId])
            ->filterByIssueIds([$this->forthcomingIssueId])
            ->filterByStatus([Submission::STATUS_PUBLISHED])
            ->orderBy($collector::ORDERBY_DATE_PUBLISHED, $collector::ORDER_DIR_ASC)
            ->getMany()
            ->filter(fn (Submission $submission) => (int) ($publication = $submission->getCurrentPublication())?->getData('issueId') === $this->forthcomingIssueId && $publication->getData('datePublished'))
            ->toArray();

        // Repo::userGroup()->getByRoleIds() uses cursor(), which skips
        // loading settings (e.g. the group name shown next to authors)
        $authorUserGroups = UserGroup::withRoleIds([Role::ROLE_ID_AUTHOR])
            ->withContextIds([$contextId])
            ->get();

        $templateMgr->assign([
            'forthcoming' => $submissions,
            'authorUserGroups' => $authorUserGroups,
            'issue' => Repo::issue()->get($this->forthcomingIssueId),
        ]);
        $templateMgr->display($this->plugin->getTemplateResource('content.tpl'));
    }
}
