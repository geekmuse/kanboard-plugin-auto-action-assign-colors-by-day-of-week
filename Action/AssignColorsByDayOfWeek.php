<?php

namespace Kanboard\Plugin\AssignColorsByDayOfWeek\Action;

use DateTime;
use DateTimeZone;
use Kanboard\Model\TaskModel;
use Kanboard\Model\ColorModel;
use Kanboard\Action\Base;

class AssignColorsByDayOfWeek extends Base
{
    private function getColorForDay($ts)
    {
        $dt = new DateTime('now', new DateTimeZone('America/New_York'));
        $dt->setTimestamp($ts);
        $dayOfWeek = $dt->format('l');
        return $this->getParam(t($dayOfWeek));
    }

    public function getDescription()
    {
        return t('Assign automatically a color based on the day of the week');
    }

    public function getCompatibleEvents()
    {
        return array(TaskModel::EVENT_CREATE);
    }

    public function getActionRequiredParameters()
    {
        $colors = $this->colorModel->getList();
        return array(
            t('Monday') => $colors,
            t('Tuesday') => $colors,
            t('Wednesday') => $colors,
            t('Thursday') => $colors,
            t('Friday') => $colors,
        );
    }

    public function getEventRequiredParameters()
    {
        return array(
            'task_id',
            'task' => array(
                'project_id',
                'color_id',
                'date_due',
            )
        );
    }

    public function doAction(array $data)
    {
        return $this->taskModificationModel->update(array(
            'id' => $data['task_id'],
            'color_id' => $this->getColorForDay($data['task']['date_due']),
        ));
    }

    public function hasRequiredCondition(array $data)
    {
        return (
            $data['task']['color_id'] == $this->colorModel->getDefaultColor()
            and isset($data['task']['date_due'])
            and $data['task']['date_due'] != 0
            and $data['task']['date_due'] != ''
        );
    }
}
