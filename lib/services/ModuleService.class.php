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
	
	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @return boolean
	 */
	public function hasPublishedWorkflowByDocument($document)
	{
		if ($document instanceof f_persistentdocument_PersistentDocument)
		{
			return $this->hasPublishedWorkflowByModel($document->getPersistentModel());
		}
		return false;
	}
	
	/**
	 * @var array
	 */
	private $hasWorkflowCache = array();
	
	/**
	 * @param string $modelName
	 * @return boolean
	 */
	public function hasPublishedWorkflowByModel($persistentModel)
	{
		if ($persistentModel instanceof f_persistentdocument_PersistentDocumentModel && $persistentModel->hasWorkflow())
		{
			$key = $persistentModel->getName();
			if (!isset($this->hasWorkflowCache[$key]))
			{
				$this->hasWorkflowCache[$key] = (workflow_WorkflowEngineService::getInstance()->getActiveWorkflowDefinitionByStarttaskid($persistentModel->getWorkflowStartTask()) !== null);
			}
			return $this->hasWorkflowCache[$key];
		}
		return false;
	}
}