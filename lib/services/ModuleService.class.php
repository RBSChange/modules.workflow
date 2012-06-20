<?php
/**
 * @package modules.workflow
 * @method workflow_ModuleService getInstance()
 */
class workflow_ModuleService extends ModuleBaseService
{	
	/**
	 * @return void
	 */
	public function addExecuteScheduledTasks()
	{
		$tasks = task_PlannedtaskService::getInstance()->getBySystemtaskclassname('workflow_ExecuteScheduledTasks');
		if (count($tasks) == 0)
		{
			$task = task_PlannedtaskService::getInstance()->getNewDocumentInstance();
			$task->setSystemtaskclassname('workflow_ExecuteScheduledTasks');
			$task->setLabel('workflow_ExecuteScheduledTasks');
			$task->setMaxduration(2);
			$task->setMinute(-1);
			$task->save(ModuleService::getInstance()->getSystemFolderId('task', 'workflow'));
		}
	}
}