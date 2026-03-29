<?php

namespace Kanboard\Plugin\AssignColorsByDayOfWeek\Action;

use DateTime;
use DateTimeZone;
use Kanboard\Model\TaskModel;
use Kanboard\Action\Base;

/**
 * Automatically assigns a card color based on the day of the week of the
 * task's due date, triggered on task creation.
 *
 * @property \Kanboard\Model\ColorModel $colorModel
 */
class AssignColorsByDayOfWeek extends Base
{
    /**
     * Resolve the English day-of-week name from a Unix timestamp, using the
     * configured Timezone parameter (falls back to the server default).
     *
     * Extracted as a shared helper so both hasRequiredCondition() and
     * getColorForDay() use the same timezone without duplication.
     */
    private function resolveDayOfWeek($ts)
    {
        $tz = $this->getParam('Timezone');
        if (empty($tz)) {
            $tz = date_default_timezone_get();
        }
        $dt = new DateTime('now', new DateTimeZone($tz));
        $dt->setTimestamp((int) $ts);
        // DateTime::format('l') always returns English day names regardless of
        // PHP locale — parameter keys are fixed English strings (see
        // getActionRequiredParameters()), so the lookup is always consistent.
        return $dt->format('l');
    }

    private function getColorForDay($ts)
    {
        return $this->getParam($this->resolveDayOfWeek($ts));
    }

    private function getTimezoneOptions()
    {
        $identifiers = DateTimeZone::listIdentifiers(DateTimeZone::ALL);
        // Prepend an empty-string sentinel meaning "use the PHP/server default".
        // resolveDayOfWeek() checks empty($tz) and falls back to
        // date_default_timezone_get() when the sentinel is selected.
        return array('' => t('Server default')) + array_combine($identifiers, $identifiers);
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
        // Prepend a sentinel "No change" option (empty-string key) so users can
        // explicitly leave a day's task color unchanged.  hasRequiredCondition()
        // already treats '' as "skip" — the same path taken when a parameter is
        // absent — so no additional guard logic is required.
        $colors = array('' => t('No change')) + $this->colorModel->getList();
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
            'Saturday'  => $colors,
            'Sunday'    => $colors,
            // IANA timezone identifier for day-of-week resolution.
            // Empty string ("Server default") means: use date_default_timezone_get().
            'Timezone'  => $this->getTimezoneOptions(),
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
            $data['task']['color_id'] === $this->colorModel->getDefaultColor()
            && isset($data['task']['date_due'])
            && $data['task']['date_due'] != 0
            && $data['task']['date_due'] != ''
        ) {
            // Resolve the day of week from the due date and verify that a color
            // is configured for that day. Days with no configured parameter
            // (e.g. Saturday, Sunday) cause getParam() to return null, so this
            // returns false and prevents doAction() from writing a null color_id
            // to the database (closes GAP-02, GAP-05).
            $day = $this->resolveDayOfWeek($data['task']['date_due']);
            $color = $this->getParam($day);
            return $color !== null && $color !== '';
        }
        return false;
    }
}
