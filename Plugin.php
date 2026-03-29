<?php

namespace Kanboard\Plugin\AssignColorsByDayOfWeek;

use Kanboard\Core\Plugin\Base;
use Kanboard\Plugin\AssignColorsByDayOfWeek\Action\AssignColorsByDayOfWeek;

/**
 * AssignColorsByDayOfWeek Kanboard plugin bootstrap.
 *
 * Registers the AssignColorsByDayOfWeek action with Kanboard's ActionManager
 * and provides plugin metadata for the plugin manager UI.
 *
 * @package Kanboard\Plugin\AssignColorsByDayOfWeek
 * @author  Bradley Campbell <oss-projects+kanboard@bradleyscampbell.net>
 */
class Plugin extends Base
{
    /**
     * Register the AssignColorsByDayOfWeek action with the ActionManager.
     *
     * @return void
     */
    public function initialize()
    {
        $this->actionManager->register(new AssignColorsByDayOfWeek($this->container));
    }

    /**
     * Return the plugin identifier name (no spaces).
     *
     * @return string
     */
    public function getPluginName()
    {
        return 'AssignColorsByDayOfWeek';
    }

    /**
     * Return the human-readable plugin description shown in the plugin manager UI.
     *
     * @return string
     */
    public function getPluginDescription()
    {
        return t('Assign task card colors by day of week');
    }

    /**
     * Return the plugin author name and contact address.
     *
     * @return string
     */
    public function getPluginAuthor()
    {
        return 'Bradley Campbell <oss-projects+kanboard@bradleyscampbell.net>';
    }

    /**
     * Return the plugin version string (semver).
     *
     * @return string
     */
    public function getPluginVersion()
    {
        return '0.1.0';
    }

    /**
     * Return the plugin homepage URL.
     *
     * @return string
     */
    public function getPluginHomepage()
    {
        return 'https://git.bradleyscampbell.net/geekmuse-kanboard/plugin-auto-action-assign-colors-by-day-of-week';
    }

    /**
     * Return the minimum Kanboard version constraint this plugin is compatible with.
     *
     * @return string
     */
    public function getCompatibleVersion()
    {
        return '>=1.2.19';
    }
}
