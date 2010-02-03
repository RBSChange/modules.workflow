<?php
/**
 * This class is called by the transition who validated the document.
 * @package modules.workflow
 */
class workflow_ValidateContentWorkflowaction extends workflow_BaseWorkflowaction
{
	/**
	 * This method will execute the action.
	 * @return boolean true if the execution end successfully, false in error case.
	 */
	function execute()
	{
		$workitem = $this->getWorkitem();
		$decision = workflow_CaseService::getInstance()->getParameter($workitem->getCase(), '__LAST_DECISION');
		if ($decision)
		{
			$this->setExecutionStatus($decision);
			return true;
		}
		else
		{
			return false;
		}
	}
}