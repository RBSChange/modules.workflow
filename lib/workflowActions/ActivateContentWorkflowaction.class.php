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
		$document = $this->getDocument();
		$document->getDocumentService()->activate($document->getId());

		// Send the activation alert.
		$notificationCodeName = $this->getCaseParameter('NOTIFICATION_ACTIVATION');
		$replacements = array('documentId' => $document->getId());
		$this->sendNotificationToAuthorCallback($notificationCodeName, null, $replacements);
		$this->setExecutionStatus(workflow_WorkitemService::EXECUTION_SUCCESS);
		return true;
	}
}