<?php
/**
 * This service contains some methods used to define workflows.
 * @package modules.workflow
 */
class workflow_WorkflowDesignerService extends BaseService
{
	/**
	 * @var workflow_WorkflowDesignerService
	 */
	private static $instance;

	/**
	 * @return workflow_WorkflowDesignerService
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
	 * Create a workflow instance.
	 * @return workflow_persistentdocument_workflow
	 */
	public function getNewWorkflowInstance()
	{
		return workflow_WorkflowService::getInstance()->getNewDocumentInstance();
	}

	/**
	 * Get a new place instance.
	 * @return workflow_persistentdocument_place
	 */
	public function getNewPlaceInstance()
	{
		return workflow_PlaceService::getInstance()->getNewDocumentInstance();
	}

	/**
	 * Get a new transition instance.
	 * @return workflow_persistentdocument_transition
	 */
	public function getNewTransitionInstance()
	{
		return workflow_TransitionService::getInstance()->getNewDocumentInstance();
	}

	/**
	 * Get a new arc instance.
	 * @return workflow_persistentdocument_arc
	 */
	public function getNewArcInstance()
	{
		return workflow_ArcService::getInstance()->getNewDocumentInstance();
	}
}