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
        // DateTime::format('l') always returns English day names regardless of PHP
        // locale. Parameter keys are fixed English strings (see
        // getActionRequiredParameters()), so no t() wrapper is used here.
        $dayOfWeek = $dt->format('l');
        return $this->getParam($dayOfWeek);
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
        // Keys are fixed English strings, intentionally without t().
        // Using t() would store translated day names (e.g. 'Lundi' in French) in
        // action_has_params, but DateTime::format('l') always produces English names,
        // causing a permanent lookup mismatch in non-English installations (GAP-03).
        // BREAKING CHANGE for existing non-English configurations: re-save actions
        // after upgrading from 0.1.0.
        return array(
            'Monday'    => $colors,
            'Tuesday'   => $colors,
            'Wednesday' => $colors,
            'Thursday'  => $colors,
            'Friday'    => $colors,
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
        if (
            $data['task']['color_id'] == $this->colorModel->getDefaultColor()
            && isset($data['task']['date_due'])
            && $data['task']['date_due'] != 0
            && $data['task']['date_due'] != ''
        ) {
            // Resolve the day of week from the due date and verify that a color
            // is configured for that day. Days with no configured parameter
            // (e.g. Saturday, Sunday) cause getParam() to return null, so this
            // returns false and prevents doAction() from writing a null color_id
            // to the database (closes GAP-02, GAP-05).
            $dt = new DateTime('now', new DateTimeZone('America/New_York'));
            $dt->setTimestamp((int) $data['task']['date_due']);
            $day = $dt->format('l');
            $color = $this->getParam($day);
            return $color !== null && $color !== '';
        }
        return false;
    }
}
