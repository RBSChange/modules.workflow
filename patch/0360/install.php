<?php
/**
 * workflow_patch_0360
 * @package modules.workflow
 */
class workflow_patch_0360 extends patch_BasePatch
{

	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		$this->execChangeCommand('update-autoload', array('modules/workflow'));
		$this->execChangeCommand('compile-listeners');
		workflow_ModuleService::getInstance()->addExecuteScheduledTasks();
	}
}