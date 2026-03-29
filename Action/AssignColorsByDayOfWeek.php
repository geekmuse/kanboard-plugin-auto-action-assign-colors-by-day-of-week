<?php

namespace Kanboard\Plugin\AssignColorsByDayOfWeek\Action;

use PDO;
use DateTime;
use DateTimeZone;

use Kanboard\Model\TaskModel;
use Kanboard\Model\ColorModel;
use Kanboard\Action\Base;

class AssignColorsByDayOfWeek extends Base {

    private function projectHasCustomColors($projectId) {
	    $countColors = $this->db->getConnection()->query("SELECT COUNT(*) FROM actions a WHERE a.action_name = '\Kanboard\Plugin\AssignColorsByDayOfWeek\Action\AssignColorsByDayOfWeek' AND a.project_id = ".$projectId.";")->fetchColumn();
        return $countColors > 0;
    }
 
    private function getColorSettings($projectId) {
	$colors = $this->db->getConnection()->query("SELECT p.name, p.value FROM actions a INNER JOIN action_has_params p ON a.id = p.action_id WHERE a.action_name = '\Kanboard\Plugin\AssignColorsByDayOfWeek\Action\AssignColorsByDayOfWeek' AND a.project_id = ".$projectId.";")->fetchAll(PDO::FETCH_ASSOC);

	return $colors;
    }

    private function getColorForDay($projectId, $ts) {
	$colors = $this->getColorSettings($projectId);
	$dt = new DateTime('now', new DateTimeZone('America/New_York'));
	$dt->setTimestamp($ts);
	$dayOfWeek = $dt->format('l');
	return $colors[(array_keys(array_column($colors, 'name'), $dayOfWeek, false))[0]]['value'];
    }

    public function getDescription() {
        return t('Assign automatically a color based on the day of the week');
    }

    public function getCompatibleEvents() {
        return array(TaskModel::EVENT_CREATE);
    }

    public function getActionRequiredParameters() {
	$colors = $this->colorModel->getList();
        return array(
		t('Monday') => $colors,
		t('Tuesday') => $colors,
		t('Wednesday') => $colors,
		t('Thursday') => $colors,
		t('Friday') => $colors,
	);
    }

    public function getEventRequiredParameters() {
	    return array(
		'task_id',
		'task' => array(
		    'project_id',
		    'color_id',
		    'date_due',
		)
	    );
    }

    public function doAction(array $data) {
        return $this->taskModificationModel->update(array('id' => $data['task_id'], 'color_id' => $this->getColorForDay($data['task']['project_id'], $data['task']['date_due'])));
    }

    public function hasRequiredCondition(array $data) {
	    return (
		$data['task']['color_id'] == $this->colorModel->getDefaultColor()
		and $this->projectHasCustomColors($data['task']['project_id'])
		and isset($data['task']['date_due'])
		and $data['task']['date_due'] != 0
		and $data['task']['date_due'] != ''
	    );	
    }
}
