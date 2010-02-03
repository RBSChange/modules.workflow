<?php
/**
 * End the workflow activating the document.
 * @package modules.workflow
 */
class workflow_ActivateContentWorkflowaction extends workflow_BaseWorkflowaction
{
	/**
	 * This method will execute the action.
	 * @return boolean true if the execution end successfully, false in error case.
	 */
	function execute()
	{
		// Update the document's status.
		// intportg - 14/09/2007 - use the good document service, not the generic one.
		$document = $this->getDocument();
		$document->getDocumentService()->activate($document->getId());

		// Send the activation alert.
		$notificationLabel = $this->getCaseParameter('NOTIFICATION_ACTIVATION');
		if (!$notificationLabel)
		{
			$notificationLabel = $this->getWorkitem()->getCase()->getWorkflow()->getLabel() . ' - Activation du document';
		}

		$replacements = array('documentId' => $document->getId());
		$this->sendNotificationToAuthor($notificationLabel, $replacements);

		$this->setExecutionStatus(workflow_WorkitemService::EXECUTION_SUCCESS);
		return true;
	}
}