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
 * @package  Kanboard\Plugin\AssignColorsByDayOfWeek\Action
 * @author   Bradley Campbell <oss-projects+kanboard@bradleyscampbell.net>
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
     *
     * @param  int $ts Unix timestamp (e.g. task date_due value)
     * @return string  English day name as returned by DateTime::format('l'),
     *                 e.g. 'Monday', 'Saturday'
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

    /**
     * Return the configured color ID for the day of the week of the given timestamp.
     *
     * @param  int         $ts Unix timestamp (task date_due)
     * @return string|null Color identifier string, or null/'' when no color is
     *                     configured for that day (including the 'No change' sentinel)
     */
    private function getColorForDay($ts)
    {
        return $this->getParam($this->resolveDayOfWeek($ts));
    }

    /**
     * Build an associative array of all IANA timezone identifiers for use as
     * a select-list in the Timezone action parameter.
     *
     * Prepends an empty-string sentinel mapped to 'Server default'; when the
     * user selects this option, resolveDayOfWeek() falls back to
     * date_default_timezone_get().
     *
     * @return array<string, string> Map of IANA identifier => IANA identifier,
     *                              with '' => t('Server default') prepended
     */
    private function getTimezoneOptions()
    {
        $identifiers = DateTimeZone::listIdentifiers(DateTimeZone::ALL);
        // Prepend an empty-string sentinel meaning "use the PHP/server default".
        // resolveDayOfWeek() checks empty($tz) and falls back to
        // date_default_timezone_get() when the sentinel is selected.
        return array('' => t('Server default')) + array_combine($identifiers, $identifiers);
    }

    /**
     * Return the human-readable description shown in the action configuration UI.
     *
     * @return string
     */
    public function getDescription()
    {
        // Explicitly names 'due date' to distinguish from creation-date colouring.
        // Users often expect 'day of week' to mean today; this clarifies that the
        // colour is driven by the day of the week of the task's due date (GAP-12).
        return t('Assign a color based on the day of the week of the task\'s due date');
    }

    /**
     * Return the list of Kanboard events this action listens to.
     *
     * @return string[] Array of event name constants
     */
    public function getCompatibleEvents()
    {
        return array(TaskModel::EVENT_CREATE);
    }

    /**
     * Return the action parameters that must be configured by the user.
     *
     * Each key is a fixed English day name (Monday–Sunday) or 'Timezone'.
     * Fixed English keys are used intentionally (no t() wrapper) because
     * DateTime::format('l') always returns English names; translating the keys
     * would cause a permanent lookup mismatch in non-English installations.
     *
     * @return array<string, array<string, string>> Map of parameter name =>
     *         associative options array (value => label)
     */
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

    /**
     * Return the event payload fields that must be present for this action to run.
     *
     * @return array<int|string, mixed> Flat and/or nested list of required field names
     */
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

    /**
     * Update the task's color_id to the value configured for its due-date day.
     *
     * Called only when hasRequiredCondition() returns true, so the color lookup
     * is guaranteed to return a non-empty string.
     *
     * @param  array<string, mixed> $data Event payload containing task_id and task fields
     * @return bool                       True on success, false on failure
     */
    public function doAction(array $data)
    {
        return $this->taskModificationModel->update(array(
            'id' => $data['task_id'],
            'color_id' => $this->getColorForDay($data['task']['date_due']),
        ));
    }

    /**
     * Determine whether all conditions are met before doAction() may run.
     *
     * Returns true only when:
     * - The task currently has the default color (not already customised)
     * - The task has a non-zero due date
     * - A non-empty color is configured for the day of the week of that due date
     *
     * Saturday and Sunday with no configured color (or with the 'No change'
     * sentinel) return false, preventing doAction() from writing a null color_id.
     *
     * @param  array<string, mixed> $data Event payload containing task and task_id fields
     * @return bool                       True when doAction() may proceed safely
     */
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
