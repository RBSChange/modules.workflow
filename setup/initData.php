<?php
class workflow_Setup extends object_InitDataSetup
{
	public function install()
	{
		$this->executeModuleScript('init.xml');
		
		workflow_ModuleService::getInstance()->addExecuteScheduledTasks();
	}
}