<?php
/**
 * Resend the creation notification of the last transition.
 * @package modules.workflow
 */
class workflow_RecallWorkflowaction extends workflow_BaseWorkflowaction
{
	/**
	 * This method will execute the action.
	 * @return boolean true if the execution end successfully, false in error case.
	 */
	function execute()
	{
		// Do nothing. The tasks will be re-created, so the notifications will be send again.
		$this->setExecutionStatus(workflow_WorkitemService::EXECUTION_SUCCESS);
		return true;
	}
}