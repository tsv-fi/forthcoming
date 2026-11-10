<?php

/**
 * @file classes/SettingsForm.php
 *
 * Copyright (c) 2014-2024 Simon Fraser University
 * Copyright (c) 2014-2024 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @package plugins.generic.forthcoming
 *
 * @class SettingsForm
 *
 * @brief ForthcomingPlugin settings class
 */

namespace APP\plugins\generic\forthcoming\classes;

use APP\core\Application;
use APP\facades\Repo;
use APP\issue\Issue;
use APP\notification\Notification;
use APP\notification\NotificationManager;
use APP\plugins\generic\forthcoming\ForthcomingPlugin;
use APP\template\TemplateManager;
use PKP\form\Form;
use PKP\form\validation\FormValidatorCSRF;
use PKP\form\validation\FormValidatorPost;

class SettingsForm extends Form
{
    /**
     * Constructor
     */
    public function __construct(public ForthcomingPlugin $plugin)
    {
        parent::__construct($plugin->getTemplateResource('settings.tpl'));
        $this->addCheck(new FormValidatorPost($this));
        $this->addCheck(new FormValidatorCSRF($this));
    }

    /**
     * @copydoc Form::initData()
     */
    public function initData(): void
    {
        $contextId = Application::get()->getRequest()->getContext()->getId();
        $this->setData('forthcomingIssueId', $this->plugin->getSetting($contextId, 'forthcomingIssueId'));
        $this->setData('sortOrder', $this->plugin->getSetting($contextId, 'sortOrder') === 'desc' ? 'desc' : 'asc');
        parent::initData();
    }

    /**
     * @copydoc Form::readInputData()
     */
    public function readInputData(): void
    {
        $this->readUserVars(['forthcomingIssueId', 'sortOrder']);
        parent::readInputData();
    }

    /**
     * @copydoc Form::fetch()
     *
     * @param null|mixed $template
     */
    public function fetch($request, $template = null, $display = false): string
    {
        $templateMgr = TemplateManager::getManager($request);
        $contextId = Application::get()->getRequest()->getContext()->getId();

        $collector = Repo::issue()->getCollector();
        $issues = $collector
            ->filterByContextIds([$contextId])
            ->filterByPublished(true)
            ->orderBy($collector::ORDERBY_SEQUENCE)
            ->getMany()
            ->mapWithKeys(fn (Issue $issue) => [$issue->getId() => $issue->getIssueIdentification()])
            ->collect()
            ->prepend(__('common.none'), 0)
            ->toArray();

        $sortOrders = [
            'asc' => __('plugins.generic.forthcoming.settings.sortOrder.asc'),
            'desc' => __('plugins.generic.forthcoming.settings.sortOrder.desc'),
        ];

        $templateMgr->assign(['issues' => $issues, 'sortOrders' => $sortOrders, 'pluginName' => $this->plugin->getName()]);
        return parent::fetch($request, $template, $display);
    }

    /**
     * @copydoc Form::execute()
     */
    public function execute(...$functionArgs)
    {
        $contextId = Application::get()->getRequest()->getContext()->getId();
        $this->plugin->updateSetting($contextId, 'forthcomingIssueId', $this->getData('forthcomingIssueId'));
        $this->plugin->updateSetting($contextId, 'sortOrder', $this->getData('sortOrder') === 'desc' ? 'desc' : 'asc');

        $notificationMgr = new NotificationManager();
        $notificationMgr->createTrivialNotification(
            Application::get()->getRequest()->getUser()->getId(),
            Notification::NOTIFICATION_TYPE_SUCCESS,
            ['contents' => __('common.changesSaved')]
        );
        return parent::execute();
    }
}
