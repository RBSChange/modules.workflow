<?php
/**
 * End the workflow by an error, so the document go back to draft status.
 * @package modules.workflow
 */
class workflow_CancelContentWorkflowaction extends workflow_BaseWorkflowaction
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

		// Send the cancellation alert.
		$notificationCodeName = $this->getCaseParameter('NOTIFICATION_ERROR');
		$replacements = array('documentId' => $document->getId());
		$this->sendNotificationToAuthor($notificationCodeName, $replacements);

		$this->setExecutionStatus(workflow_WorkitemService::EXECUTION_SUCCESS);
		return true;
	}
}