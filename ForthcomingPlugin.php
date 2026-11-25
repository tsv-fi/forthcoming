<?php

/**
 * @file ForthcomingPlugin.php
 *
 * Copyright (c) 2014-2026 Simon Fraser University
 * Copyright (c) 2014-2026 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @package plugins.generic.forthcoming
 *
 * @class ForthcomingPlugin
 *
 * @brief ForthcomingPlugin main class
 */

namespace APP\plugins\generic\forthcoming;

use APP\core\Application;
use APP\plugins\generic\forthcoming\classes\Handler;
use APP\plugins\generic\forthcoming\classes\SettingsForm;
use PKP\core\JSONMessage;
use PKP\linkAction\LinkAction;
use PKP\linkAction\request\AjaxModal;
use PKP\navigationMenu\NavigationMenuItem;
use PKP\plugins\GenericPlugin;
use PKP\plugins\Hook;

class ForthcomingPlugin extends GenericPlugin
{
    /** Navigation menu item type for the Forthcoming listing page */
    public const NMI_TYPE_FORTHCOMING = 'NMI_TYPE_FORTHCOMING';

    /**
     * @copydoc Plugin::getDisplayName()
     */
    public function getDisplayName(): string
    {
        return __('plugins.generic.forthcoming.displayName');
    }

    /**
     * @copydoc Plugin::getDescription()
     */
    public function getDescription(): string
    {
        return __('plugins.generic.forthcoming.description');
    }

    /**
     * @copydoc Plugin::register()
     *
     * @param null|mixed $mainContextId
     */
    public function register($category, $path, $mainContextId = null): bool
    {
        if (!parent::register($category, $path, $mainContextId)) {
            return false;
        }

        if ($this->getEnabled($mainContextId)) {
            Hook::add('LoadHandler', [$this, 'loadHandler']);
            Hook::add('NavigationMenus::itemTypes', [$this, 'addMenuItemTypes']);
            Hook::add('NavigationMenus::displaySettings', [$this, 'setMenuItemDisplayDetails']);

            # Add Forthcoming label to article summary, hide Forthcoming issue from public issue archive and mark in backend archive, redirect calls to issue landing page to custom Forthcoming handler
            Hook::add('TemplateManager::display', [$this, 'displayTemplate']);
        }
        return true;
    }

    /**
     * Handle Forthcoming issue in archive listings
     */
    public function displayTemplate(string $hookName, array $params): bool
    {
        /** @var \APP\template\TemplateManager $templateManager */
        [$templateManager, $template] = $params;
        $request = Application::get()->getRequest();
        $contextId = $request->getContext()?->getId();
        $forthcomingIssueId = (int) $this->getSetting($contextId, 'forthcomingIssueId');
        if (!$forthcomingIssueId) {
            return Hook::CONTINUE;
        }

        switch ($template) {
            // Redirect default issue toc page to Forthcoming page
            case 'frontend/pages/issue.tpl':
                $issue = $templateManager->getTemplateVars('issue');
                if ($issue?->getId() === $forthcomingIssueId) {
                    $router = $request->getRouter();
                    $request->redirectUrl($router->url($request, null, 'forthcoming'));
                }
                break;

                // Article landing page
            case 'frontend/pages/article.tpl':
                $publication = $templateManager->getTemplateVars('publication');
                if ((int) $publication?->getData('issueId') === $forthcomingIssueId) {
                    $templateManager->addStyleSheet(
                        'forthcomingLabelStyles',
                        '.forthcomingLabel span { border-radius: 5px; background: #ebebeb; color: #262626; padding: 6px; }',
                        ['inline' => true, 'contexts' => 'frontend']
                    );
                    $templateManager->registerFilter('output', [$this, 'articleLandingPageFilter']);
                }
                break;

                // Remove Forthcoming issue from the list of issues
            case 'frontend/pages/issueArchive.tpl':
                $issues = $templateManager->getTemplateVars('issues');
                $total = $templateManager->getTemplateVars('total');
                foreach ($issues as $key => $issue) {
                    if ($issue->getId() === (int) $forthcomingIssueId) {
                        unset($issues[$key]);
                        --$total;
                        break;
                    }
                }
                $templateManager->assign(['issues' => $issues, 'total' => $total]);
                break;

                // Backend archive display
            case 'manageIssues/issues.tpl':
                $forthcomingIssueBackendStyles = "span#cell-{$forthcomingIssueId}-identification:after { font-family: FontAwesome; content: \"\\f005\"; }";
                $templateManager->addStylesheet(
                    'forthcomingIssueBackendStyles',
                    $forthcomingIssueBackendStyles,
                    ['inline' => true, 'contexts' => 'backend']
                );
                break;
        }

        return Hook::CONTINUE;
    }

    /**
     * Setup the plugin handler
     */
    public function loadHandler($hookName, $args): bool
    {
        $page = $args[0];
        if ($page !== 'forthcoming') {
            return Hook::CONTINUE;
        }

        $request = $this->getRequest();
        $contextId = $request->getContext()?->getId();
        if (!$contextId) {
            return Hook::CONTINUE;
        }
        $forthcomingIssueId = (int) $this->getSetting($contextId, 'forthcomingIssueId');

        if ($forthcomingIssueId) {
            $args[3] = new Handler($this, $forthcomingIssueId);
            return Hook::ABORT;
        }

        $router = $request->getRouter();
        $request->redirectUrl($router->url($request, null, 'index'));
        return Hook::CONTINUE;
    }

    /**
     * Add a "Forthcoming" label to the article's landing page
     */
    public function articleLandingPageFilter($output, $templateMgr): string
    {
        if (!preg_match('/<h1[^>]+class="page_title"[^>]*>/', $output, $matches, PREG_OFFSET_CAPTURE)) {
            return $output;
        }

        $offset = $matches[0][1];
        $newOutput = substr($output, 0, $offset);
        $newOutput .= '<div class="forthcomingLabel"><span>' . htmlspecialchars(__('plugins.generic.forthcoming.label')) . '</span></div><br />';
        $newOutput .= substr($output, $offset);
        return $newOutput;
    }

    /**
     * Add the Forthcoming listing page to the navigation menu item types
     */
    public function addMenuItemTypes(string $hookName, array $args): bool
    {
        $types = & $args[0];
        $types[self::NMI_TYPE_FORTHCOMING] = [
            'title' => __('plugins.generic.forthcoming.pageTitle'),
            'description' => __('plugins.generic.forthcoming.navMenuItem.description'),
        ];
        return Hook::CONTINUE;
    }

    /**
     * Set the URL of the Forthcoming menu item, and hide it when no Forthcoming issue is set
     */
    public function setMenuItemDisplayDetails(string $hookName, array $args): bool
    {
        /** @var NavigationMenuItem $navigationMenuItem */
        [$navigationMenuItem] = $args;
        if ($navigationMenuItem->getType() !== self::NMI_TYPE_FORTHCOMING) {
            return Hook::CONTINUE;
        }

        $request = Application::get()->getRequest();
        $contextId = $request->getContext()?->getId();
        if (!$contextId || !$this->getSetting($contextId, 'forthcomingIssueId')) {
            $navigationMenuItem->setIsDisplayed(false);
            return Hook::CONTINUE;
        }

        $navigationMenuItem->setUrl($request->getRouter()->url($request, null, 'forthcoming'));
        return Hook::CONTINUE;
    }

    /**
     * @copydoc Plugin::getActions()
     */
    public function getActions($request, $actionArgs): array
    {
        $actions = parent::getActions($request, $actionArgs);
        if (!$this->getEnabled()) {
            return $actions;
        }

        $router = $request->getRouter();
        $url = $router->url($request, null, null, 'manage', null, ['verb' => 'settings', 'plugin' => $this->getName(), 'category' => 'generic']);
        array_unshift($actions, new LinkAction('settings', new AjaxModal($url, $this->getDisplayName()), __('manager.plugins.settings')));
        return $actions;
    }

    /**
     * @copydoc Plugin::manage()
     */
    public function manage($args, $request): JSONMessage
    {
        if ($request->getUserVar('verb') !== 'settings') {
            return parent::manage($args, $request);
        }

        $form = new SettingsForm($this);
        if (!$request->getUserVar('save')) {
            $form->initData();
            return new JSONMessage(true, $form->fetch($request));
        }

        $form->readInputData();
        if (!$form->validate()) {
            return new JSONMessage(true, $form->fetch($request));
        }

        $form->execute();
        return new JSONMessage(true);
    }
}
