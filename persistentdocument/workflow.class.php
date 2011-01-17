<?php
/**
 * workflow_persistentdocument_workflow
 * @package modules.workflow
 */
class workflow_persistentdocument_workflow extends workflow_persistentdocument_workflowbase
{
	/**
	 * Add the wokflow to the arc.
	 * @param workflow_persistentdocument_arc $newValue
	 */
	public function addArcs($newValue)
	{
		parent::addArcs($newValue);
		$newValue->setWorkflow($this);
	}
	
	/**
	 * Add the wokflow to the place.
	 * @param workflow_persistentdocument_place $newValue
	 */
	public function addPlaces($newValue)
	{
		parent::addPlaces($newValue);
		$newValue->setWorkflow($this);
	}
	
	/**
	 * Add the wokflow to the transition.
	 * @param workflow_persistentdocument_transition $newValue
	 */
	public function addTransitions($newValue)
	{
		parent::addTransitions($newValue);
		$newValue->setWorkflow($this);
	}
}