<?php
/**
 * This service contains some methods used to process workflows.
 * @package modules.workflow
 */
class workflow_WorkflowEngineService extends BaseService
{
	/**
	 * @var workflow_WorkflowEngineService
	 */
	private static $instance;

	/**
	 * @return workflow_WorkflowEngineService
	 */
	public static function getInstance()
	{
		if (self::$instance === null)
		{
			self::$instance = self::getServiceClassInstance(get_class());
		}
		return self::$instance;
	}

	/**
	 * Get the start task id corresponding to a given document.
	 * @param integer $documentId
	 * @return string
	 */
	public function getStartTaskIdForDocumentId($documentId)
	{
		$provider = f_persistentdocument_PersistentProvider::getInstance();
		$document = $provider->getDocumentInstance($documentId);
		return $this->getStartTaskIdForDocument($document);
	}

	/**
	 * Get the start task id corresponding to a given document.
	 * @param f_persistentdocument_PersistentDocument $document
	 * @return string
	 */
	public function getStartTaskIdForDocument($document)
	{
		if ($document->getPersistentModel()->hasWorkflow())
		{
			return $document->getPersistentModel()->getWorkflowStartTask();
		}
		else
		{
			return null;
		}
	}

	/**
	 * Initialize a workflow instance (case) for the given document.
	 * @param integer $documentId
	 * @param string $taskId if no task specified, the default start task for this document type is used.
	 * @param array $startParameters
	 * @return workflow_persistentdocument_case the case instance.
	 */
	public function initWorkflowInstance($documentId, $taskId = null, $startParameters = array())
	{
		// Verify that we have a document id.
		if (empty($documentId))
		{
			Framework::error(__METHOD__ . ' : no document id');
			return null;
		}

		// If no task is passed, get the start task id for this document.
		if (empty($taskId))
		{
			$taskId = $this->getStartTaskIdForDocumentId($documentId);
		}
		if (empty($taskId))
		{
			Framework::error(__METHOD__ . ' : no taskId found for ' . $documentId);
			return null;
		}
		
		return $this->execInitWorkflowInstance($documentId, $taskId, $startParameters);
	}

	/**
	 * Execute the initialization of a workflow instance (case) for the given document (no test of arguments validty).
	 * @see initWorkflowInstance()
	 * @param integer $documentId
	 * @param string $taskId
	 * @param array $startParameters
	 * @return workflow_persistentdocument_case the case instance.
	 */
	private function execInitWorkflowInstance($documentId, $taskId, $startParameters = array())
	{
		if (Framework::isDebugEnabled())
		{
			Framework::debug(__METHOD__ . ' : documentId = ' . $documentId . ', taskId = ' . $taskId);
		}

		$workflow = $this->getActiveWorkflowDefinitionByStarttaskid($taskId);
		if ($workflow)
		{
			$case = $this->getNewCaseInstance();
			$case->setWorkflow($workflow);
			
			$cs = workflow_CaseService::getInstance();
			foreach ($startParameters as $parameterName => $parameterValue) 
			{
				$cs->setParameter($case, $parameterName, $parameterValue);
			}
			
			// Set the author id.
			$user = users_UserService::getInstance()->getCurrentUser();
			if ($user)
			{
			    $cs->setParameter($case, '__DOCUMENT_AUTHOR_ID', $user->getId());
			    $cs->setParameter($case, 'workflowAuthor', $user->getFullname());
			}
			else
			{
				$cs->setParameter($case, '__DOCUMENT_AUTHOR_ID', null);
				$cs->setParameter($case, 'workflowAuthor', 'Anonymous');
			}
			
			workflow_CaseService::getInstance()->start($case, $documentId);
			
			$case->save();
			return $case;
		}
		else
		{
			Framework::error(__METHOD__ . ' : no workflow found for documentId = ' . $documentId . ', taskId = ' . $taskId);
			return null;
		}
	}

	/**
	 * Cancel the workflow instance associated to the given document.
	 * @param integer $documentId
	 * @param string $taskId
	 */
	public function cancelWorkflowInstance($documentId, $taskId)
	{
		// Verify that we have a document id.
		if (empty($documentId))
		{
			Framework::error(__METHOD__ . ' : no document id');
			return;
		}

		// Get the start task id for this document.
		if (empty($taskId))
		{
			Framework::error(__METHOD__ . ' : no task id');
			return;
		}

		$workitems = workflow_WorkitemService::getInstance()->getActiveWorkitems($documentId, $taskId);
		if (count($workitems) > 0)
		{
			$case = $workitems[0]->getCase();
			$this->execCancelWorkflowInstance($documentId, $case);
		}
		else
		{
			Framework::warn(__METHOD__ . ' : no case found for document ' . $documentId . ' and task ' . $taskId);
		}
	}

	/**
	 * Execute the cancellation the workflow instance for the given task id (no test of arguments validty).
	 * @param integer $documentId
	 * @param string workflow_persistentdocument_case $case
	 */
	private function execCancelWorkflowInstance($documentId, $case)
	{
		workflow_CaseService::getInstance()->cancelCase($case);
		if (!$case->isNew())
		{
			$case->save();
		}

		// Remove the WORKFLOW status from the document and set it back to DRAFT or CORRECTION.
		$document = DocumentHelper::getDocumentInstance($documentId);
		$document->getDocumentService()->cancel($documentId);
	}

	/**
	 * Execute the given task.
	 * @param integer $taskId
	 * @param string $decision
	 * @param string $commentary
	 */
	public function executeTaskById($taskId, $decision, $commentary)
	{
		$task = DocumentHelper::getDocumentInstance($taskId);
		return $this->executeTask($task, $decision, $commentary);
	}

	/**
	 * Execute the given task.
	 * @param task_persistentdocument_usertask $task
	 * @param string $decision
	 * @param string $commentary
	 * @param users_persistentdocument_user $user
	 * @return boolean true if the exection is successfuly performed, false else.
	 */
	public function executeTask($task, $decision, $commentary)
	{
		// Execute the workitem.
		$workitem = $task->getWorkitem();
		$case = $workitem->getCase();
		$rc = RequestContext::getInstance();
		try
		{
			$rc->beginI18nWork($case->getLang());
			$caseService = workflow_CaseService::getInstance();

			$caseService->setParameter($case, '__LAST_DECISION', $decision);
			$caseService->setParameter($case, '__LAST_COMMENTARY', $commentary);
			workflow_WorkitemService::getInstance()->userTrigger($workitem, $task->getUser());
			$case->save();
			if ($workitem->getPublicationstatus() == 'FILED')
			{
				// Add the log entry.
				$userName = $task->getUser()->getLabel();
				$documentId = $workitem->getDocumentid();
				$actionLabel = $workitem->getLabel();
				WorkflowHelper::addLogEntry($documentId, $userName, $actionLabel, $decision, $commentary);
				$rc->endI18nWork();
				return true;
			}
			else
			{
				$rc->endI18nWork();
				return false;
			}
		}
		catch (Exception $e)
		{
			$rc->endI18nWork($e);
		}
		return false;
	}
	
	/**
	 * @param integer $documentId
	 * @param string $taskId
	 * @param string $message
	 */
	public function executeMessageTask($documentId, $taskId, $message = null)
	{
		$wis = workflow_WorkitemService::getInstance();
		$query = $wis->createQuery();
		$query->add(Restrictions::published());
		$query->add(Restrictions::eq('documentid', $documentId));
		$query->createCriteria('transition')
			->add(Restrictions::eq('taskid', $taskId))
			->add(Restrictions::eq('trigger', WorkflowHelper::TRIGGER_MESSAGE));
		$workitem = $query->findUnique();
		
		if ($workitem !== null)
		{
			$wis->messageTrigger($workitem, $message);
			workflow_CaseService::getInstance()->save($workitem->getCase());
		}
		else
		{
			Framework::warn(__METHOD__ . "($documentId, '$taskId', '$message') no workitem");
		}
	}

	/**
	 * Execute all the scheduled tasks that have to be executed.
	 * @return Integer the number of executed scheduled tasks.
	 */
	public function executeScheduledTasks()
	{
		// Date courrante.
		$date = date('Y-m-d H:i:s');
		if (Framework::isDebugEnabled())
		{
			Framework::debug(__METHOD__ . ' : date to test : ' . $date);
		}

		// Get all published scheduled workitems with a deadline in the past.
		$query = workflow_WorkitemService::getInstance()->createQuery();
		$query->add(Restrictions::le('deadline', $date));
		$query->add(Restrictions::published());
		$query->createCriteria('transition')->add(Restrictions::eq('trigger', WorkflowHelper::TRIGGER_TIME));
		$workitems = $query->find();

		// Execute the workitems.
		$executedTasks = 0;
		if (Framework::isDebugEnabled())
		{
			Framework::debug(__METHOD__ . ' : number of workitem to execute : ' . count($workitems));
		}
		$wis = workflow_WorkitemService::getInstance();
		$rc = RequestContext::getInstance();
		foreach ($workitems as $workitem)
		{
			try
			{
				$rc->beginI18nWork($workitem->getLang());
				if (Framework::isDebugEnabled())
				{
					Framework::debug(__METHOD__ . ' : execute scheduled workitem ' . $workitem->getId());
				}
				$wis->timerTrigger($workitem);
				workflow_CaseService::getInstance()->save($workitem->getCase());
				$executedTasks++;
				$rc->endI18nWork();
			}
			catch (Exception $e)
			{
				$rc->endI18nWork($e);
			}
		}
		if (Framework::isDebugEnabled())
		{
			Framework::debug(__METHOD__ . ' : number of executed tasks : ' . $executedTasks);
		}
		return $executedTasks;
	}

	/**
	 * Creates the task for each user allowed to execute the workitem.
	 * @param workflow_persistentdocument_workitem $workitem
	 */
	public function createTasksForWorkitem($workitem)
	{
		if ($workitem->getTransition()->getTrigger() == WorkflowHelper::TRIGGER_USER)
		{
			// Get tasks information.
			$transition = $workitem->getTransition();
			$creationnotification = $transition->getCreationnotification();
			$terminationnotification = $transition->getTerminationnotification();
			$cancellationnotification = $transition->getCancellationnotification();
			$description = $transition->getDescription();

			// Generate one task for each allowed user.
			$users = workflow_WorkitemService::getInstance()->getValidActors($workitem);
			if (count($users) > 0)
			{
				foreach ($users as $user)
				{
					$task = TaskHelper::getUsertaskService()->getNewDocumentInstance();
					$task->setUser($user);
					$task->setWorkitem($workitem);
					$task->setCreationnotification($creationnotification);
					$task->setTerminationnotification($terminationnotification);
					$task->setCancellationnotification($cancellationnotification);
					$task->setDescription($description);
					workflow_WorkitemService::getInstance()->updateTaskInfos($workitem, $task);
					$task->save();
				}
			}
			else
			{
				if (Framework::isDebugEnabled())
				{
					Framework::debug(__METHOD__ . ' : No valid user found, so cancel the case and throw NoUserForWorkitemException.');
				}
				workflow_WorkflowEngineService::getInstance()->execCancelWorkflowInstance($workitem->getDocumentid(), $workitem->getCase());
				throw new NoUserForWorkitemException('No-valid-user-found-for-this-workitem');
			}
		}
	}

	/**
	 * Get all active workflows at the given date.
	 *  - if no date given : test for the current date.
	 *  - if start date given and no end date : test for the start date.
	 *  - if start date and end date given : test for the interval.
	 * @param bool $getInvalide
	 * @param string $startDate
	 * @param string $endDate
	 * @return array<workflow_persistentdocument_workflow>
	 */
	public function getActiveWorkflowDefinitions($getInvalide = false, $startDate = null, $endDate = null)
	{
		if ($startDate === null)
		{
			$startDate = date('Y-m-d H:i:s');
		}
		if ($endDate === null)
		{
			$endDate = $startDate;
		}

		return $this->execGetActiveWorkflowDefinitions(null, $startDate, $endDate, $getInvalide, false);
	}

	/**
	 * Get the first (supposed unique) active workflow with the given start task id.
	 * If the date is null, the current date is used.
	 * @param string $startTaskId
	 * @param string $date
	 * @return workflow_persistentdocument_workflow
	 */
	public function getActiveWorkflowDefinitionByStarttaskid($startTaskId, $date = null)
	{
		// Calculate dates.
		if ($date === null)
		{
			$date = date_Calendar::getInstance()->toString();
		}
		return array_shift($this->execGetActiveWorkflowDefinitions($startTaskId, $date, $date, false, true));
	}

	/**
	 * Execute the query to get all active workflows in the given interval (if one date is null, no test is done for this date).
	 * @param string $startTaskId
	 * @param string $startDate
	 * @param string $endDate
	 * @param bool $getInvalide
	 * @param boolean $unique set to true to get only the first
	 * @return array<workflow_persistentdocument_workflow>
	 */
	public function execGetActiveWorkflowDefinitions($startTaskId = null, $startDate = null, $endDate = null, $getInvalide = false, $unique = false)
	{
		$provider = f_persistentdocument_PersistentProvider::getInstance();
		$query = $provider->createQuery('modules_workflow/workflow');
		if (!$getInvalide)
		{
			$query->add(Restrictions::in('publicationstatus', array('ACTIVE', 'PUBLICATED')));
		}
		if ($startTaskId)
		{
			$query->add(Restrictions::eq('starttaskid', $startTaskId));
		}
		if ($endDate)
		{
			$query->add(Restrictions::orExp(Restrictions::isEmpty('startpublicationdate'), Restrictions::lt('startpublicationdate', $endDate)));
		}
		if ($startDate)
		{
			$query->add(Restrictions::orExp(Restrictions::isEmpty('endpublicationdate'), Restrictions::gt('endpublicationdate', $startDate)));
		}

		if ($unique)
		{
			return array($provider->findUnique($query));
		}
		else
		{
			return $provider->find($query);
		}
	}

	/**
	 * Get all active workflow instances (cases) for the given workflow definition.
	 * @param integer $workflowId
	 * @return array<workflow_persistentdocument_case>
	 */
	public function getActiveWorkflowInstances($workflowId)
	{
		$provider = f_persistentdocument_PersistentProvider::getInstance();
		$query = $provider->createQuery('modules_workflow/case');
		$query->add(Restrictions::in('publicationstatus', array('ACTIVE', 'PUBLICATED')));
		$query->createCriteria('workflow')->add(Restrictions::eq('id', $workflowId));
		return $provider->find($query);
	}

	/**
	 * Get the number of active workflow instances (cases) for the given workflow definition.
	 * @param integer $workflowId
	 * @return integer
	 */
	public function getActiveWorkflowInstancesCount($workflowId)
	{
		return count($this->getActiveWorkflowInstances($workflowId));
	}

	/**
	 * Get a new case instance.
	 * @return workflow_persistentdocument_case
	 */
	public function getNewCaseInstance()
	{
		return workflow_CaseService::getInstance()->getNewDocumentInstance();
	}

	/**
	 * Get a new token instance.
	 * @return workflow_persistentdocument_token
	 */
	public function getNewTokenInstance()
	{
		return workflow_TokenService::getInstance()->getNewDocumentInstance();
	}

	/**
	 * Get a new workitem instance.
	 * @return workflow_persistentdocument_workitem
	 */
	public function getNewWorkitemInstance()
	{
		return workflow_WorkitemService::getInstance()->getNewDocumentInstance();
	}

	/**
	 * Get next transitions.
	 * Not used for now...
	 * @param workflow_persistentdocument_transition $transition
	 * @param string $decision if null, no filter on decision.
	 * @param string $trigger if null, no filter on trigger.
	 * @return workflow_persistentdocument_transition $transition
	 */
	public function getNextTransitions($transition, $decision = null, $trigger = null)
	{
		// Get all output arcs from the current transition filter by precondition == $decision (if $decision is not null).
		$arcs = workflow_TransitionService::getInstance()->getOutputArcsArray($transition, $decision);
		if (Framework::isDebugEnabled())
		{
			Framework::debug(__METHOD__ . ' : number of arcs leaving from the current transition = ' . count($arcs));
		}

		// Get the places.
		$places = array();
		foreach ($arcs as $arc)
		{
			$places[] = $arc->getPlace();
		}
		if (Framework::isDebugEnabled())
		{
			Framework::debug(__METHOD__ . ' : number of places accessible from the current transition = ' . count($places));
		}

		// Get the output arcs from the places.
		$arcs = array();
		$placeService = workflow_PlaceService::getInstance();

		foreach ($places as $place)
		{
			$arcs = array_merge($arcs, $placeService->getOutputArcsArray($place));
		}
		if (Framework::isDebugEnabled())
		{
			Framework::debug(__METHOD__ . ' : number of arcs leaving from the places = ' . count($arcs));
		}

		// Get the transitions filtered by trigger id triger is not null.
		$transitions = array();
		foreach ($arcs as $arc)
		{
			$transition = $arc->getTransition();
			if (!$trigger || ($transition->getTrigger() == $trigger))
			{
				$transitions[] = $transition;
			}
		}
		if (Framework::isDebugEnabled())
		{
			Framework::debug(__METHOD__ . ' : number of transitions returned = ' . count($transitions));
		}

		return $transitions;
	}
	
	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param workflow_persistentdocument_workflow $workflow
	 * @param workflow_persistentdocument_workitem $workitem
	 * @param task_persistentdocument_usertask $usertask
	 * @return array
	 */
	public function getDefaultNotificationParameters($document, $workitem = null, $usertask = null)
	{
	    $replacements = array();	    
		$replacements['documentId'] = $document->getId();
		$replacements['documentLabel'] = $document->getLabel();
		$replacements['documentLang'] = $document->getLang();
		
		$ds = $document->getDocumentService();
		if (f_util_ClassUtils::methodExists($ds, 'getPathOf'))
		{
		    $replacements['documentPath'] = $ds->getPathOf($document);
		}
		else
		{
		    $replacements['documentPath'] = '';
		}
		
		if ($workitem !== null)
		{
    		$transition = $workitem->getTransition();
    		$replacements['transitionId'] = $transition->getId();
    		$replacements['transitionLabel'] = $transition->getLabel();
    
    		$workflow = $transition->getWorkflow();
    		$replacements['workflowId'] = $workflow->getId();
    		$replacements['workflowLabel'] = $workflow->getLabel();
		}
		if ($usertask !== null)
		{
		    $replacements['taskLabel'] = $usertask->getLabel();
		    $replacements['taskDescription'] = $usertask->getDescription();
		}
			
		$currentUser = users_UserService::getInstance()->getCurrentUser();
		if ($currentUser !== null)
		{
			$replacements['currentUserId'] = $currentUser->getId();
			$replacements['currentUserFullname'] = $currentUser->getFullname();
		}	

		return $replacements;
	}
}