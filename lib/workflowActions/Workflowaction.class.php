<?php
/**
 * This interface describe a minimal workflow action. These actions will be associated to workitems.
 * @package modules.workflow
 */
interface workflow_Workflowaction
{
	/**
	 * This method initializes the action. It must be called before the execute one.
	 * @param workflow_persistentdocument_workitem $workitem
	 */
	public function initialize($workitem);

	/**
	 * This method will execute the action.
	 * @return boolean true if the execution end successfully, false in error case.
	 */
	public function execute();

	/**
	 * Return a value which will be compared with the precondition in explicit or split case.
	 * @return string
	 */
	public function getExecutionStatus();
}