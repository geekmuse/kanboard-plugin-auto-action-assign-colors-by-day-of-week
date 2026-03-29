<?php

namespace Kanboard\Plugin\AssignColorsByDayOfWeek;

use Kanboard\Core\Plugin\Base;
use Kanboard\Plugin\AssignColorsByDayOfWeek\Action\AssignColorsByDayOfWeek;

class Plugin extends Base
{
    public function initialize()
    {
        $this->actionManager->register(new AssignColorsByDayOfWeek($this->container));
    }

    public function getPluginName()
    {
        return 'AssignColorsByDayOfWeek';
    }

    public function getPluginDescription()
    {
        return t('Assign task card colors by day of week');
    }

    public function getPluginAuthor()
    {
        return 'Bradley Campbell <oss-projects+kanboard@bradleyscampbell.net>';
    }

    public function getPluginVersion()
    {
        return '0.1.0';
    }

    public function getPluginHomepage()
    {
        return 'https://bradleyscampbell.net';
    }

    public function getCompatibleVersion()
    {
        return '>=1.2.19';
    }
}
