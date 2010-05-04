<?php
/**
 * @package module.workflow
 */
class workflow_ListExistingstarttasksService extends BaseService
{
	/**
	 * @var workflow_ListContextualplacesService
	 */
	private static $instance;
	
	/**
	 * @return workflow_ListContextualplacesService
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