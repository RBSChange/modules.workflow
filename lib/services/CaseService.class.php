<?php
/**
 * @package modules.workflow
 */
class workflow_CaseService extends f_persistentdocument_DocumentService
{
	/**
	 * @var workflow_CaseService
	 */
	private static $instance;

	/**
	 * @return workflow_CaseService
	 */
	public static function getInstance()
	{
		if (self::$instance === null)
		{
			self::$instance = self::getServiceClassInstance(get_class());
		}
		return self::$instance;
	}

	/**
	 * @return workflow_persistentdocument_case
	 */
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName('modules_workflow/case');
	}

	/**
	 * Create a query based on 'modules_workflow/case' model
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		return $this->pp->createQuery('modules_workflow/case');
	}

	/**
	 * Start the case.
	 * @param workflow_persistentdocument_case $case
	 * @param integer $documentId
	 */
	public function start($case, $documentId)
	{
		if (Framework::isDebugEnabled())
		{
			Framework::debug(__METHOD__);
		}
		$case->setPublicationstatus('ACTIVE');
		$case->setDocumentid($documentId);
		$case->setLabel($case->getWorkflow()->getLabel() . ' - ' . $documentId);
		$this->addNewToken($case, workflow_WorkflowService::getInstance()->getStartPlace($case->getWorkflow()));
	}

	/**
	 * Close this instance.
	 * @param workflow_persistentdocument_case $case
	 */
	public function close($case)
	{
		if (Framework::isDebugEnabled())
		{
			Framework::debug(__METHOD__ . ' : case = ' . $case->getId());
		}
		$case->setPublicationstatus('FILED');
	}

	/**
	 * Cancel this instance.
	 * @param workflow_persistentdocument_case $case
	 */
	public function cancelCase($case)
	{
		if (Framework::isDebugEnabled())
		{
			Framework::debug(__METHOD__ . ' : case = ' . $case->getId());
		}
		$case->setPublicationstatus('TRASH');

		// Cancel all active tokens.
		$tokenService = workflow_TokenService::getInstance();
		$tokenArray = $case->getTokenArray();
		foreach ($tokenArray as $token)
		{
			if ($tokenService->isActive($token))
			{
				$tokenService->cancelToken($token);
			}
		}

		// Cancel all active workitems.
		$workitemService = workflow_WorkitemService::getInstance();
		$workitemArray = $case->getWorkitemArray();
		foreach ($workitemArray as $workItem)
		{
			if ($workitemService->isActive($workItem))
			{
				$workitemService->cancelWorkitem($workItem);
			}
		}
	}

	/**
	 * Add to the given case a new token from a given place.
	 * @param workflow_persistentdocument_case $case
	 * @param workflow_persistentdocument_place $place
	 * @return workflow_persistentdocument_token $token
	 */
	public function addNewToken($case, $place)
	{
		if (Framework::isDebugEnabled())
		{
			Framework::debug(__METHOD__ . ' : place = ' . $place->getId());
		}
		$ts = workflow_TokenService::getInstance();
		$token = $ts->init($case, $place);
		$ts->checks($token);
		return  $token;
	}

	/**
	 * Add to the given case a new workitem from a given transition.
	 * @param workflow_persistentdocument_case $case
	 * @param workflow_persistentdocument_transition $transition
	 * @return workflow_persistentdocument_workitem $workitem
	 */
	public function addNewWorkitem($case, $transition)
	{
		if (Framework::isDebugEnabled())
		{
			Framework::debug(__METHOD__ . ' : transition = ' . $transition->getId());
		}
		$wis = workflow_WorkitemService::getInstance();
		$workitem = $wis->init($case, $transition);

		// Execute immediatly the workitem in automatic-trigger case.
		if ($workitem->getTransition()->getTrigger() == WorkflowHelper::TRIGGER_AUTO)
		{
			$wis->autoTrigger($workitem);
		}
		return $workitem;
	}

	/**
	 * Get the first active token associated to the given place.
	 * @param workflow_persistentdocument_case $case
	 * @param integer $placeId
	 * @return workflow_persistentdocument_token
	 */
	public function getActiveToken($case, $placeId)
	{
		$tokenService = workflow_TokenService::getInstance();
		$tokensArray = $case->getTokenArray();
		foreach ($tokensArray as $token)
		{
			if (($token->getPlace()->getId() == $placeId) && $tokenService->isActive($token))
			{
				return 	$token;
			}
		}
		return null;
	}

	/**
	 * Get the first active workitem associated to the given transition.
	 * @param workflow_persistentdocument_case $case
	 * @param integer $transitionId
	 * @return workflow_persistentdocument_workitem
	 */
	public function getActiveWorkitem($case, $transitionId)
	{
		$workitemsArray = $case->getWorkitemArray();
		foreach ($workitemsArray as $workitem)
		{
			if (($workitem->getTransition()->getId() == $transitionId) && workflow_WorkitemService::getInstance()->isActive($workitem))
			{
				return 	$workitem;
			}
		}
		return null;
	}

	/**
	 * Get all active workitems associated to the given transition.
	 * @param workflow_persistentdocument_case $case
	 * @param integer $transitionId
	 * @return array<workflow_persistentdocument_workitem>
	 */
	public function getActiveWorkitems($case, $transitionId)
	{
		$workitemsArray = $case->getWorkitemArray();
		$activeWorkitemsArray = array();
		foreach ($workitemsArray as $workitem)
		{
			if (($workitem->getTransition()->getId() == $transitionId) && workflow_WorkitemService::getInstance()->isActive($workitem))
			{
				$activeWorkitemsArray[] = $workitem;
			}
		}
		return $activeWorkitemsArray;
	}

	/**
	 * Get a parameter value.
	 * @param workflow_persistentdocument_case $case
	 * @param string $name
	 * @return mixed
	 */
	public function getParameter($case, $name)
	{
		if (Framework::isDebugEnabled())
		{
			Framework::debug(__METHOD__ . ' : name = ' . $name);
		}
		if ($case)
		{
			$parameters = unserialize($case->getParameters());
		}
		if (is_array($parameters) && array_key_exists($name, $parameters))
		{
			return $parameters[$name];
		}
		return null;
	}

	/**
	 * Get the parameters.
	 * @param workflow_persistentdocument_case $case
	 * @return array<mixed>
	 */
	public function getParametersArray($case)
	{
		if (Framework::isDebugEnabled())
		{
			Framework::debug(__METHOD__);
		}
		if ($case)
		{
			$parameters = unserialize($case->getParameters());
		}
		if (!is_array($parameters))
		{
			$parameters = array();
		}
		return $parameters;
	}

	/**
	 * Set a parameter value.
	 * @param workflow_persistentdocument_case $case
	 * @param string $name
	 * @param mixed $value
	 */
	public function setParameter($case, $name, $value)
	{
		if (Framework::isDebugEnabled())
		{
			Framework::debug(__METHOD__ . ' : name = ' . $name . ', value = ' . $value);
		}
		if (is_null($value))
		{
			$this->clearParameter($case, $name);
		}
		else
		{
			if ($case->getParameters())
			{
				$parameters = unserialize($case->getParameters());
			}
			else
			{
				$parameters = array();
			}
			$parameters[$name] = $value;
			$case->setParameters(serialize($parameters));
		}
	}

	/**
	 * Remove one parameter.
	 * @param workflow_persistentdocument_case $case
	 * @param string $name
	 */
	public function clearParameter($case, $name)
	{
		if (Framework::isDebugEnabled())
		{
			Framework::debug(__METHOD__ . ' : name = ' . $name);
		}
		$serializedParameters = $case->getParameters();
		if ($serializedParameters !== null)
		{
			$parameters = unserialize($serializedParameters);
			if (array_key_exists($name, $parameters))
			{
				unset($parameters[$name]);
				$case->setParameters(serialize($parameters));
			}
		}
	}
	
	/**
	 * @param Integer $workflowId
	 * @param Integer $targetId
	 * @return Integer
	 */
	public function getCountByWorkflow($workflowId, $targetId = null)
	{
		$query = $this->createQuery();
		$query->add(Restrictions::eq('workflow.id', $workflowId));
		if ($targetId > 0)
		{
			$query->add(Restrictions::eq('documentid', $targetId));
		}
		$query->setProjection(Projections::rowCount('count'));
		$row = $query->findUnique();
		return $row['count'];
	}
	
	/**
	 * @param Integer $workflowId
	 * @param Integer $targetId
	 * @param Integer $offset
	 * @param Integer $limit
	 * @return workflow_persistentdocument_case[]
	 */
	public function getByWorkflow($workflowId, $targetId = null, $offset = null, $limit = null)
	{
		$query = $this->createQuery()->add(Restrictions::eq('workflow.id', $workflowId));
		$query->addOrder(Order::desc('document_creationdate'));
		if ($targetId > 0)
		{
			$query->add(Restrictions::eq('documentid', $targetId));
		}
		if ($offset !== null)
		{
			$query->setFirstResult($offset);
		}
		if ($limit !== null)
		{
			$query->setMaxResults($limit);
		}
		return $query->find();
	}
	
	/**
	 * @param workflow_persistentdocument_case $document
	 * @param Integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
	protected function postSave($document, $parentNodeId = null)
	{
		$document->savePendingCurrentUserDocumentEntry();
	}	
}