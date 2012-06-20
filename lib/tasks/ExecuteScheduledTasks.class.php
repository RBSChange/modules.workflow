<?php
/**
 * @package modules.workflow
 */
class workflow_ExecuteScheduledTasks  extends task_SimpleSystemTask
{
	
	/**
	 * @see task_SimpleSystemTask::execute()
	 */
	protected function execute()
	{
		workflow_WorkflowEngineService::getInstance()->executeScheduledTasks();
	}
}