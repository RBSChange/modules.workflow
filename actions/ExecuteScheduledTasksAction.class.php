<?php
/**
 * This action executes scheduled tasks.
 * @package modules.workflow
 */
class workflow_ExecuteScheduledTasksAction extends f_action_BaseAction
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		workflow_WorkflowEngineService::getInstance()->executeScheduledTasks();
		return self::getSuccessView();
	}
}