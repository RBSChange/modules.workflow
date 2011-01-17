<?php
/**
 * @package modules.workflow
 */
class workflow_TransitionService extends f_persistentdocument_DocumentService
{
	/**
	 * @var workflow_TransitionService
	 */
	private static $instance;

	/**
	 * @return workflow_TransitionService
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
	 * @return workflow_persistentdocument_transition
	 */
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName('modules_workflow/transition');
	}

	/**
	 * Create a query based on 'modules_workflow/transition' model
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		return $this->pp->createQuery('modules_workflow/transition');
	}

	/**
	 * Updates associated arcs' labels.
	 * @param workflow_persistentdocument_transition $transition
	 * @param Integer $parentNodeId Parent node ID where to save the document (optionnal).
	 * @return void
	 */
	public function postSave($transition, $parentNodeId = null)
	{
		$as = workflow_ArcService::getInstance();
		$arcsArray = $as->getArcsByTransition($transition);
		foreach ($arcsArray as $arc)
		{
			$as->regenerateLabel($arc);
			$arc->save();
		}
	}

	/**
	 * Validate this transition for the workflow definition (test if it is correctly connected).
	 * @param workflow_persistentdocument_transition $transition
	 * @return boolean
	 */
	public function validatePath($transition)
	{
		$workflow = $transition->getWorkflow();
		$workflowService = $workflow->getDocumentService();
				
		// Check if the action is valid.
		$classname = $transition->getActionname();
		if (!empty($classname))
		{
			if (strpos($classname, '{') !== false )
			{
				if (Framework::isInfoEnabled())
				{
					Framework::info(__METHOD__ . ' : unable to validate abstract method ' . $classname);
				}
			}
			else if (!f_util_ClassUtils::classExists($classname))
			{
				$workflowService->setActivePublicationStatusInfo($workflow, '&modules.workflow.bo.general.Error-TransitionActionDoesNotExist;', array('id' => $transition->getId(), 'actionName' => $classname));
				return false;
			}
		}

		// Check if this transition has input and output arcs.
		$hasinput = (count($this->getInputArcsArray($transition)) > 0);
		$hasoutput = (count($this->getOutputArcsArray($transition)) > 0);
		if ($hasoutput && $hasinput)
		{
			return true;
		}

		$workflowService->setActivePublicationStatusInfo($workflow, '&modules.workflow.bo.general.Error-TransitionBadlyConnected;', array('id' => $transition->getId()));
		return false;
	}

	/**
	 * Get all arcs leading to this transition.
	 * @param workflow_persistentdocument_transition $transition
	 * @param string $precondition allow to filter arcs by precondition.
	 * @return array<workflow_persistentdocument_arc>
	 */
	public function getInputArcsArray($transition, $precondition = null)
	{
		return workflow_ArcService::getInstance()->getArcsByTransition($transition, WorkflowHelper::DIRECTION_PLACE_TO_TRANSITION, $precondition);
	}

	/**
	 * Get all arcs leaving this transition.
	 * @param workflow_persistentdocument_transition $transition
	 * @param string $precondition allow to filter arcs by precondition.
	 * @return array<workflow_persistentdocument_arc>
	 */
	public function getOutputArcsArray($transition, $precondition = null)
	{
		return workflow_ArcService::getInstance()->getArcsByTransition($transition, WorkflowHelper::DIRECTION_TRANSITION_TO_PLACE, $precondition);
	}
}