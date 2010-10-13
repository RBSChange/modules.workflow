<?php
/**
 * This action is a simple implementation of the WorkflowAction interface wich do nothing but just provide some usefull methods.
 * @package modules.workflow
 */
class workflow_BaseWorkflowaction implements workflow_Workflowaction
{
	protected $executionStatus = workflow_WorkitemService::EXECUTION_NOEXECUTION;

	/**
	 * @var workflow_persistentdocument_workitem
	 */
	protected $workitem = null;

	/**
	 * This method initializes the action. It must be called before the execute one.
	 * @param workflow_persistentdocument_workitem $workitem
	 */
	public function initialize($workitem)
	{
		$this->setWorkitem($workitem);
	}

	/**
	 * This method needs to be redefined to execute the action.
	 * @return boolean true if the execution end successfully, false in error case.
	 */
	public function execute()
	{
		// Do nothing.
		$this->setExecutionStatus(workflow_WorkitemService::EXECUTION_SUCCESS);
		return true;
	}

	/**
	 * Return a value which will be compared with the precondition in explicit or split case.
	 * @return string
	 */
	public function getExecutionStatus()
	{
		return $this->executionStatus;
	}

	/**
	 * Return a value which will be compared with the precondition in explicit or split case.
	 * @param string $status
	 */
	protected function setExecutionStatus($status)
	{
		$this->executionStatus = $status;
	}

	/**
	 * Get the workitem.
	 * @return workflow_persistentdocument_workitem
	 */
	protected function getWorkitem()
	{
		return $this->workitem;
	}

	/**
	 * Set the workitem.
	 * @param workflow_persistentdocument_workitem $workitem
	 */
	protected function setWorkitem($workitem)
	{
		$this->workitem = $workitem;
	}

	/**
	 * @return Integer
	 */
	protected function getDocumentId()
	{
		if ($this->workitem)
		{
			return $this->workitem->getDocumentid();
		}
		return 0;
	}

	/**
	 * Get the associated document.
	 * @return f_persistentdocument_PersistentDocument
	 */
	protected function getDocument()
	{
		if ($this->workitem)
		{
			return DocumentHelper::getDocumentInstance($this->workitem->getDocumentid());
		}
		return null;
	}

	/**
	 * Change the document's publication status.
	 * @param string $newStatus
	 */
	protected function changeDocumentStatus($newStatus)
	{
		$document = $this->getDocument();
		if (Framework::isDebugEnabled())
		{
			Framework::debuf(__METHOD__ . ' : change document ' . $document->getId() . ' status from ' . $document->getPublicationStatus() . ' to ' . $newStatus);
		}
		$document->setPublicationstatus($newStatus);
		$document->save();
	}

	/**
	 * Set a notification to the document author with the default sender.
	 * @param string $notificationCodeName
	 * @param array $replacements an associative array with the word to replace as the key and the replacement as the value.
	 */
	protected function sendNotificationToAuthor($notificationCodeName, $replacements)
	{
		// Look for the document author.
		$userId = workflow_CaseService::getInstance()->getParameter($this->getWorkitem()->getCase(), '__DOCUMENT_AUTHOR_ID');
		if (!$userId)
		{
			if (Framework::isInfoEnabled())
			{
				Framework::info(__METHOD__ . ' : there is no user to send notification');
			}
			return;
		}
		$user = DocumentHelper::getDocumentInstance($userId);

		// Send the notification.
		$receiver = sprintf('%s <%s>', f_util_StringUtils::strip_accents($user->getFullname()), $user->getEmail());
		$this->sendNotification($notificationCodeName, array($receiver), $replacements);
	}

	/**
	 * Set a notification to the document author with the default sender.
	 * @param string $notificationCodeName
	 * @param array<string> $receivers an array of email addresses.
	 * @param array $replacements an associative array with the word to replace as the key and the replacement as the value.
	 */
	protected function sendNotification($notificationCodeName, $receivers, $replacements)
	{
		// Get the notification by codename.
		$notificationService = notification_NotificationService::getInstance();
		$notification = $notificationService->getNotificationByCodeName($notificationCodeName);
		if (!$notification)
		{
			if (Framework::isWarnEnabled())
			{
				Framework::warn(__METHOD__ . ' : there is no notification for the codename "' . $notificationCodeName . '"');
			}
			return;
		}

		$defaultParameters = workflow_WorkflowEngineService::getInstance()
		                    ->getDefaultNotificationParameters($this->getDocument(), $this->getWorkitem());

		// Add the case parameters.
		$caseParameters = workflow_CaseService::getInstance()->getParametersArray($this->getWorkitem()->getCase());
		$replacements = array_merge($replacements, $defaultParameters, $caseParameters);

		// Send the notification.
		$notificationService->sendMail($notification, $receivers, $replacements);
		if (Framework::isDebugEnabled())
		{
			Framework::debug(__METHOD__ . ' : mail sent to ' . implode(', ', $receivers));
		}
	}

	/**
	 * @param String $name
	 * @return String
	 */
	protected function getCaseParameter($name)
	{
		$case = $this->getWorkitem()->getCase();
		return workflow_CaseService::getInstance()->getParameter($case, $name);
	}

	/**
	 * @param String $name
	 * @param String $value
	 */
	protected function setCaseParameter($name, $value)
	{
		$case = $this->getWorkitem()->getCase();
		WorkflowHelper::getCaseService()->setParameter($case, $name, $value);
	}	
	
	/**
	 * @return String
	 */
	protected function getDecision()
	{
		return $this->getCaseParameter('__LAST_DECISION');
	}

	/**
	 * @return String
	 */
	protected function getCommentary()
	{
		return $this->getCaseParameter('__LAST_COMMENTARY');
	}
	
	/**
	 * @param task_persistentdocument_usertask $task
	 */
	public function updateTaskInfos($task)
	{
		//TODO customize $task information here.
	}
}
