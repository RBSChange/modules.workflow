<?php
/**
 * @package modules.workflow.lib.services
 */
class workflow_ModuleService extends ModuleBaseService
{
	/**
	 * Singleton
	 * @var workflow_ModuleService
	 */
	private static $instance = null;

	/**
	 * @return workflow_ModuleService
	 */
	public static function getInstance()
	{
		if (is_null(self::$instance))
		{
			self::$instance = self::getServiceClassInstance(get_class());
		}
		return self::$instance;
	}
	
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