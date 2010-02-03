<?php
/**
 * End the workflow by a refuse, so the document go back to draft status.
 * @package modules.workflow
 */
class workflow_BackToDraftWorkflowaction extends workflow_BaseWorkflowaction
{
	/**
	 * This method will execute the action.
	 * @return boolean true if the execution end successfully, false in error case.
	 */
	function execute()
	{
		// Update the document's status.
		$document = $this->getDocument();
		$document->getDocumentService()->cancel($document->getId());

		// Send the activation alert.
		$notificationLabel = workflow_CaseService::getInstance()->getParameter($this->getWorkitem()->getCase(), 'NOTIFICATION_BACK_TO_DRAFT');
		if (!$notificationLabel)
		{
			$notificationLabel = $this->getWorkitem()->getCase()->getWorkflow()->getLabel() . ' - Retour brouillon';
		}

		$replacements = array('documentId' => $document->getId());
		$this->sendNotificationToAuthor($notificationLabel, $replacements);

		$this->setExecutionStatus(workflow_WorkitemService::EXECUTION_SUCCESS);
		return true;
	}
}