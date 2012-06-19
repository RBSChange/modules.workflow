<?php
/**
 * @package module.workflow
 * @method workflow_ListContextualtransitionsService getInstance()
 */
class workflow_ListContextualtransitionsService extends change_BaseService
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
		if (isset($this->parameters['workflowId']))
		{
			$workflow = DocumentHelper::getDocumentInstance($this->parameters['workflowId']);
		}
		else if (isset($this->parameters['arcId'])) 
		{
			$arc = DocumentHelper::getDocumentInstance($this->parameters['arcId']);
			$workflow = $arc->getWorkflow();
		}
		else
		{
			if (Framework::isDebugEnabled())
			{
				Framework::debug(__METHOD__ . ' No parameter to find the current workflow: '.var_export($this->parameters, true));
			}
			return array();
		}
		
		$items = array();		
		foreach ($workflow->getTransitionsArray() as $transition)
		{
			$items[] = new list_Item($transition->getLabel(), $transition->getId());
		}		
		return $items;
	}
}