<?php
/**
 * This class aims to select a validator for the one level validation workflow.
 * @package modules.workflow
 */
class workflow_SelectValidatorWorkflowaction extends workflow_BaseWorkflowaction
{
	/**
	 * This method will execute the action.
	 * @return boolean true if the execution end successfully, false in error case.
	 */
	function execute()
	{
		$executionStatus = 'OK';
		$actorIds = array();

		// TODO
		// Set the actors for the next user transition...
		// Set execution status to 'KO' if there are two transitions or no user transitions...
		/*
		$actorIds = array('10287');
		$case = $this->getWorkitem()->getCase();
		workflow_CaseService::getInstance()->setParameter($case, '__NEXT_ACTORS_IDS', $actorIds);
		*/

		$this->setExecutionStatus($executionStatus);
		return true;
	}
}