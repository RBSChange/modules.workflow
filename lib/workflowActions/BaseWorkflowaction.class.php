<?php
/**
 * This action is a simple implementation of the WorkflowAction interface wich do nothing but just provide some usefull methods.
 * @package modules.workflow
 */
class workflow_BaseWorkflowaction implements workflow_Workflowaction
{
	/**
	 * @var string
	 */
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
		// Do nothing by default.
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
	 * @return integer
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
			Framework::debug(__METHOD__ . ' : change document ' . $document->getId() . ' status from ' . $document->getPublicationStatus() . ' to ' . $newStatus);
		}
		$document->setPublicationstatus($newStatus);
		$document->save();
	}

	/**
	 * Send a notification to the document author with the default sender. The notification replacements are returned by the callback function.
	 * @param string $codeName
	 * @param function $callback
	 * @param mixed $callbackParameter
	 * @return boolean
	 */
	protected function sendNotificationToAuthorCallback($codeName, $callback = null, $callbackParameter = array())
	{
		// Look for the document author.
		$userId = workflow_CaseService::getInstance()->getParameter($this->getWorkitem()->getCase(), '__DOCUMENT_AUTHOR_ID');
		if ($userId)
		{
			$user = users_persistentdocument_user::getInstanceById($userId);
			return $this->sendNotificationToUserCallback($codeName, $user, $callback, $callbackParameter);
		}
		else if (Framework::isInfoEnabled())
		{
			Framework::info(__METHOD__ . '(codename = ' . $codeName . '): there is no user to send notification');
		}
		return false;
	}

	/**
	 * Send a notification to the document author with the default sender. The notification replacements are returned by the callback function.
	 * @param string $codeName
	 * @param function $callback
	 * @param mixed $callbackParameter
	 * @return boolean
	 */
	protected function sendSuffixedNotificationToAuthorCallback($codeName, $suffix, $callback = null, $callbackParameter = array())
	{
		// Look for the document author.
		$userId = workflow_CaseService::getInstance()->getParameter($this->getWorkitem()->getCase(), '__DOCUMENT_AUTHOR_ID');
		if ($userId)
		{
			$user = users_persistentdocument_user::getInstanceById($userId);
			return $this->sendSuffixedNotificationToUserCallback($codeName, $suffix, $user, $callback, $callbackParameter);
		}
		else if (Framework::isInfoEnabled())
		{
			Framework::info(__METHOD__ . '(codename = ' . $codeName . '): there is no user to send notification');
		}
		return false;
	}

	/**
	 * Send a notification to the document author with the default sender. The notification replacements are returned by the callback function.
	 * @param string $codeName
	 * @param users_persistentdocument_user $user
	 * @param string $callback function name
	 * @param mixed $callbackParameter
	 * @return boolean
	 */
	protected function sendNotificationToUserCallback($codeName, $user, $callback = null, $callbackParameter = array())
	{
		list($websiteId, $lang) = $this->getNotificationWebsiteIdAndLang($codeName);
		$notification = notification_NotificationService::getInstance()->getConfiguredByCodeName($codeName, $websiteId, $lang);
		if ($notification !== null)
		{
			$notification->setSendingModuleName('workflow');
		}
		return $user->getDocumentService()->sendNotificationToUserCallback($notification, $user, $callback, $callbackParameter);
	}

	/**
	 * Send a notification to the document author with the default sender. The notification replacements are returned by the callback function.
	 * @param string $codeName
	 * @param string $suffix
	 * @param users_persistentdocument_user $user
	 * @param string $callback function name
	 * @param mixed $callbackParameter
	 * @return boolean
	 */
	protected function sendSuffixedNotificationToUserCallback($codeName, $suffix, $user, $callback = null, $callbackParameter = array())
	{
		list($websiteId, $lang) = $this->getNotificationWebsiteIdAndLang($codeName);
		$notification = notification_NotificationService::getInstance()->getConfiguredByCodeNameAndSuffix($codeName, $suffix, $websiteId, $lang);
		if ($notification !== null)
		{
			$notification->setSendingModuleName('workflow');
		}
		return $user->getDocumentService()->sendNotificationToUserCallback($notification, $user, $callback, $callbackParameter);
	}
	
	/**
	 * @param string $notificationCodeName
	 * @return array array(websiteId, lang) by default, workflow's document websiteId and original lang
	 */
	public function getNotificationWebsiteIdAndLang($notificationCodeName)
	{
		$document = $this->getDocument();
		return array($document->getDocumentService()->getWebsiteId($document), $document->getLang());
	}

	/**
	 * @param string $name
	 * @return string
	 */
	protected function getCaseParameter($name)
	{
		$case = $this->getWorkitem()->getCase();
		return workflow_CaseService::getInstance()->getParameter($case, $name);
	}

	/**
	 * @param string $name
	 * @param string $value
	 */
	protected function setCaseParameter($name, $value)
	{
		$case = $this->getWorkitem()->getCase();
		workflow_CaseService::getInstance()->setParameter($case, $name, $value);
	}	
	
	/**
	 * @return string
	 */
	protected function getDecision()
	{
		return $this->getCaseParameter('__LAST_DECISION');
	}

	/**
	 * @return string
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
	
	/**
	 * @param task_persistentdocument_usertask $task
	 * @return array
	 */
	public function getCreationNotifParameters($usertask)
	{
		return $this->getCommonNotifParameters($usertask);
	}
	
	/**
	 * @param task_persistentdocument_usertask $task
	 * @return array
	 */
	public function getCancellationNotifParameters($usertask)
	{
		// Add the decision.
		$decision = f_Locale::translate('&modules.workflow.bo.general.decision-' . strtolower($this->getDecision()) . ';');
		return array_merge($this->getCommonNotifParameters($usertask), array('decision' => $decision));
	}
	
	/**
	 * @param task_persistentdocument_usertask $task
	 * @return array
	 */
	public function getTerminationNotifParameters($usertask)
	{
		// Add the decision.
		$decision = f_Locale::translate('&modules.workflow.bo.general.decision-' . strtolower($this->getDecision()) . ';');
		return array_merge($this->getCommonNotifParameters($usertask), array('decision' => $decision));
	}
	
	/**
	 * @param task_persistentdocument_usertask $task
	 * @return array
	 */
	protected function getCommonNotifParameters($usertask)
	{
		return array();
	}
}