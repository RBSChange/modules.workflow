<?php
/**
 * @package module.workflow
 * @method workflow_ListExistingstarttasksService getInstance()
 */
class workflow_ListExistingstarttasksService extends change_BaseService
{
	/**
	 * @var Array
	 */
	private $parameters = array();
	
	/**
	 * @param Array $parameters
	 */
	public function setParameters($parameters)
	{
		$this->parameters = $parameters;
	}
	
	/**
	 * @return array<list_Item>
	 */
	public final function getItems()
	{
		$query = workflow_WorkflowService::getInstance()->createQuery();
		$workflowsByTask = array();
		foreach ($query->find() as $workflow)
		{
			$taskId = $workflow->getStarttaskid();
			if (!isset($workflowsByTask[$taskId]) || $workflow->isPublished())
			{
				$workflowsByTask[$taskId] = $workflow;
			}
		}
		
		$items = array();		
		foreach ($workflowsByTask as $key => $workflow)
		{
			$items[] = new list_Item($workflow->getLabel(), $key);
		}		
		return $items;
	}
}