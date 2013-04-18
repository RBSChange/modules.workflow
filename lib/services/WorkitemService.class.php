<?php
/**
 * @package modules.workflow
 */
class workflow_WorkitemService extends f_persistentdocument_DocumentService
{
	const EXECUTION_SUCCESS = "__SUCCESS";
	const EXECUTION_ERROR = "__ERROR";
	const EXECUTION_NOEXECUTION = "__NOEXECUTION";

	/**
	 * @var workflow_WorkitemService
	 */
	private static $instance;

	/**
	 * @return workflow_WorkitemService
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
	 * @return workflow_persistentdocument_workitem
	 */
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName('modules_workflow/workitem');
	}

	/**
	 * Create a query based on 'modules_workflow/workitem' model
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		return $this->pp->createQuery('modules_workflow/workitem');
	}

	/**
	 * @param workflow_persistentdocument_workitem $workitem
	 * @return void
	 */
	protected function preDelete($workitem)
	{
		$usertaskToDeleteArray = $workitem->getUsertaskArrayInverse();
		foreach ($usertaskToDeleteArray as $usertaskToDelete)
		{
			$usertaskToDelete->delete();
		}
	}


	/**
	 * Get the associated workflow.
	 * @param workflow_persistentdocument_workitem $workitem
	 * @return workflow_persistentdocument_workflow
	 */
	public function getWorkflow($workitem)
	{
		return $workitem->getCase()->getWorkflow();
	}

	/**
	 * Check the status to know if this workitem is active.
	 * @param workflow_persistentdocument_workitem $workitem
	 * @return boolean
	 */
	public function isActive($workitem)
	{
		$publicationStatus = $workitem->getPublicationstatus();
		if (Framework::isDebugEnabled())
		{
			Framework::debug(__METHOD__ . ' : is workitem ' . $workitem->getId() . ' active ? Status = ' . $publicationStatus);
		}
		return ($publicationStatus == 'ACTIVE' || $workitem->isPublished());
	}

	/**
	 * Check the status to know if this workitem is closed.
	 * @param workflow_persistentdocument_workitem $workitem
	 * @return boolean
	 */
	public function isClosed($workitem)
	{
		$publicationStatus = $workitem->getPublicationstatus();
		if (Framework::isDebugEnabled())
		{
			Framework::debug(__METHOD__ . ' : is workitem ' . $workitem->getId() . ' closed ? Status = ' . $publicationStatus);
		}
		return ($publicationStatus == 'FILED');
	}

	/**
	 * Initialize a workitem.
	 * @param workflow_persistentdocument_case $case
	 * @param workflow_persistentdocument_transition $transition
	 * @return workflow_persistentdocument_workitem $workitem
	 */
	public function init($case, $transition)
	{
		if (Framework::isDebugEnabled())
		{
			Framework::debug(__METHOD__ . ' : transition = ' . $transition->getId());
		}
		$wes = workflow_WorkflowEngineService::getInstance();
		$workitem = $wes->getNewWorkitemInstance();
		$workitem->setTransition($transition);
		$workitem->setLabel($transition->getLabel());
		$workitem->setDocumentId($case->getDocumentId());
		$workitem->setPublicationStatus('ACTIVE');
		if ($transition->getTrigger() === WorkflowHelper::TRIGGER_TIME)
		{
			$workflowAction = $workitem->getExecAction();
			if (method_exists($workflowAction, 'getTimelimit'))
			{
				$timeLimit = $workflowAction->getTimelimit();
			}
			else
			{
				$timeLimit = max(1, intval($transition->getTimelimit()));
			}
			$date = date_Calendar::getInstance();
			$date->add(date_Calendar::HOUR, $timeLimit);
			$deadline = $date->toString();
			$workitem->setDeadline($deadline);
		}
		
		$case->addWorkitem($workitem);

		// Create the associated tasks.
		$wes->createTasksForWorkitem($workitem);

		return $workitem;
	}

	/**
	 * Execute this workitem.
	 * @param workflow_persistentdocument_workitem $workitem
	 * @return boolean true if the execution ends correctly.
	 */
	public function execute($workitem)
	{
		if (Framework::isDebugEnabled())
		{
			Framework::debug(__METHOD__ . ' : workItem = ' . $workitem->getId());
		}
		$caseService = workflow_CaseService::getInstance();
		$case = $workitem->getCase();
		$case->workitemTrigged($workitem);
		$caseService->setParameter($case, '__LAST_STATUS', self::EXECUTION_NOEXECUTION);

		// Try to execute the action if one is defined.
		$classname = $workitem->getExecActionName();
		if (!empty($classname))
		{
			try
			{
				if (!f_util_ClassUtils::classExists($classname))
				{
					Framework::error(__METHOD__ . ' : This workflow action does not exist : ' . $classname);
					throw new FileNotFoundException($classname);
				}
				else
				{
					$action = new $classname();
					$action->initialize($workitem);
					if ($action->execute())
					{
						$caseService->setParameter($case, '__LAST_STATUS', $action->getExecutionStatus());
					}
					else
					{
						$caseService->setParameter($case, '__LAST_STATUS', self::EXECUTION_ERROR);
					}
				}
			}
			catch (Exception $e)
			{
				if (Framework::isDebugEnabled())
				{
					Framework::exception($e);
				}
				$caseService->setParameter($case, '__LAST_STATUS', self::EXECUTION_ERROR);
			}
		}
		else
		{
			if (Framework::isDebugEnabled())
			{
				Framework::debug(__METHOD__ . ' : No action to execute for workitem = ' . $workitem->getId());
			}
			$caseService->setParameter($case, '__LAST_STATUS', self::EXECUTION_SUCCESS);
		}

		// Error management.
		$lastStatus =  $caseService->getParameter($case, '__LAST_STATUS');
		if ($lastStatus != self::EXECUTION_NOEXECUTION && $lastStatus != self::EXECUTION_ERROR)
		{
			$workitem->setPublicationstatus('FILED');
		}

		if (Framework::isDebugEnabled())
		{
			Framework::debug(__METHOD__ . ' END : last status = ' . $lastStatus . ' for workItem = ' . $workitem->getId() . ', workitem status = ' .  $workitem->getPublicationstatus());
		}
		
		// Log action.
		$transition = $workitem->getTransition();
		$params = array(
			'transition' => $transition->getLabelAsHtml(),
			'status' => $caseService->getParameter($case, '__LAST_STATUS')
		);
		switch ($transition->getTrigger())
		{
			case 'USER':
				$user = DocumentHelper::getDocumentInstance($workitem->getUserid());
				$params['user'] = $user->getLabelAsHtml();
				$this->addCurrentUserDocumentEntry('execute.workitem.user', $case, $params, 'workflow');
				break;
				
			case 'AUTO':
				$this->addCurrentUserDocumentEntry('execute.workitem.auto', $case, $params, 'workflow');
				break;
				
			case 'TIME':
				$this->addCurrentUserDocumentEntry('execute.workitem.time', $case, $params, 'workflow');
				break;

			case 'MSG':
				$this->addCurrentUserDocumentEntry('execute.workitem.msg', $case, $params, 'workflow');
				break;
								
			default:
				Framework::warn(__METHOD__ . ' unknown trigger "' . $transition->getTrigger() . '"');
				break;
		}
		
		return ($this->isClosed($workitem));
	}
	
	/**
	 * @param String $actionName
	 * @param workflow_persistentdocument_case $document
	 * @param array $info
	 * @param String $moduleName
	 */
	private function addCurrentUserDocumentEntry($actionName, $document, $info, $moduleName)
	{
		if ($document->getId() > 0)
		{
			UserActionLoggerService::getInstance()->addCurrentUserDocumentEntry($actionName, $document, $info, $moduleName);
		}
		// Document has no id yet. The entry will be created in the postSave().
		else
		{
			$document->addPendingCurrentUserDocumentEntry($actionName, $info, $moduleName);
		}
	}

	/**
	 * Finish the workitem.
	 * @param workflow_persistentdocument_workitem $workitem
	 * @return boolean false if the workitem is not correctly finished.
	 */
	public function finish($workitem)
	{
		if ($this->isClosed($workitem))
		{
			if (Framework::isDebugEnabled())
			{
				Framework::debug(__METHOD__ . ' : workitem = ' . $workitem->getId());
			}
			$arcService = workflow_ArcService::getInstance();
			$tokenService = workflow_TokenService::getInstance();
			$caseService = workflow_CaseService::getInstance();
			$transitionService = workflow_TransitionService::getInstance();

			$case = $workitem->getCase();

			// Get output places for this workitem.
			$placesArray = array();
			$outArcsArray = $transitionService->getOutputArcsArray($workitem->getTransition());
			foreach ($outArcsArray as $arc)
			{
				if ($arcService->guard($arc, $workitem))
				{
					$placesArray[] = $arc->getPlace();
				}
			}

			if (count($placesArray) != 0)
			{
				// Consume input tokens.
				$inArcsArray = $transitionService->getInputArcsArray($workitem->getTransition());
				foreach ($inArcsArray as $arc)
				{
					$token = $caseService->getActiveToken($case, $arc->getPlace()->getId());
					if($token)
					{
						$tokenService->consume($token);
					}
				}

				// Activate output token.
				foreach ($placesArray as $place)
				{
					$caseService->addNewToken($case, $place);
				}
			}
			else
			{
				// Error ! There is no output place.
				$workitem->setPublicationstatus('ACTIVE');
				if (Framework::isWarnEnabled())
				{
					Framework::warn(__METHOD__ . ' : NO output place');
				}
			}
		}

		return ($workitem->getPublicationstatus() == 'FILED');
	}

	/**
	 * Cancel this workitem.
	 * @param workflow_persistentdocument_workitem $workitem
	 */
	public function cancelWorkitem($workitem)
	{
		if (Framework::isDebugEnabled())
		{
			Framework::debug(__METHOD__ . ' : workitem = ' . $workitem->getId());
		}
		$workitem->setPublicationstatus('TRASH');

		// Cancel the tasks for the workitem.
		$query = $this->pp->createQuery('modules_task/usertask');
		$query->createCriteria('workitem')->add(Restrictions::eq('id', $workitem->getId()));
		$query->add(Restrictions::in('publicationstatus', array('ACTIVE', 'PUBLICATED')));
		$tasksToCancel = $query->find();
		foreach ($tasksToCancel as $task)
		{
			TaskHelper::getUsertaskService()->cancelUsertask($task);
		}
	}

	/**
	 * Activate an automatic trigger.
	 * @param workflow_persistentdocument_workitem $workitem
	 * @return workflow_persistentdocument_workitem
	 */
	public function autoTrigger($workitem)
	{
		if (Framework::isDebugEnabled())
		{
			Framework::debug(__METHOD__ . ' : workItem = ' . $workitem->getId());
		}
		$this->execute($workitem);
		$this->finish($workitem);
		return $workitem;
	}

	/**
	 * Activate a user trigger.
	 * @param workflow_persistentdocument_workitem $workitem
	 * @param users_persistentdocument_user $user
	 * @return workflow_persistentdocument_workitem
	 */
	public function userTrigger($workitem, $user = null)
	{
		if ($workitem->isPublished())
		{
			if (Framework::isDebugEnabled())
			{
				Framework::debug(__METHOD__ . ' : workItem = ' . $workitem->getId());
			}
			try
			{
				if ($user === null)
				{
					$user = users_UserService::getInstance()->getCurrentUser();
				}
				
				if ($user !== null)
				{
					$userId = $user->getId();
				}
				else
				{
					$userId = null;
				}
				
				if (Framework::isDebugEnabled())
				{
					Framework::debug(__METHOD__ . ' : set userId = ' . $userId . ' for workitem ' . $workitem->getId());
				}
				$workitem->setUserid($userId);
			}
			catch(Exception $e)
			{
				Framework::exception($e);
			}
			$this->execute($workitem);
			$this->finish($workitem);
		}
		return $workitem;
	}

	/**
	 * Activate a timer trigger.
	 * @param workflow_persistentdocument_workitem $workitem
	 * @return workflow_persistentdocument_workitem
	 */
	public function timerTrigger($workitem)
	{
		if ($workitem->isPublished())
		{
			if (Framework::isDebugEnabled())
			{
				Framework::debug(__METHOD__ . ' : workItem = ' . $workitem->getId());
			}
			$this->execute($workitem);
			$this->finish($workitem);
		}
		return $workitem;
	}

	/**
	 * Activate a message trigger.
	 * @param workflow_persistentdocument_workitem $workitem
	 * @param string $message
	 * @return workflow_persistentdocument_workitem
	 */
	public function messageTrigger($workitem, $message)
	{
		if ($workitem->isPublished())
		{
			if (Framework::isDebugEnabled())
			{
				Framework::debug(__METHOD__ . ' workItem = ' . $workitem->getId() . ', message = ' . $message);
			}
			workflow_CaseService::getInstance()->setParameter($workitem->getCase(), '__LAST_MESSAGE', $message);
			$this->execute($workitem);
			$this->finish($workitem);
		}
		return $workitem;
	}

	/**
	 * Get all active workitems associated to the given document and task.
	 * @param integer $documentId
	 * @param string $taskId
	 * @return array<workflow_persistentdocument_workitem>
	 */
	public function getActiveWorkitems($documentId, $taskId)
	{
		// Verify that we have a document id.
		if (empty($documentId))
		{
			if (Framework::isErrorEnabled())
			{
				Framework::error(__METHOD__ . ' : no document id');
			}
			return array();
		}

		// Get the start task id for this document.
		if (empty($taskId))
		{
			if (Framework::isErrorEnabled())
			{
				Framework::error(__METHOD__ . ' : no task id');
			}
			return array();
		}

		$query = $this->createQuery();
		$query->add(Restrictions::eq('documentid', $documentId));
		$query->add(Restrictions::in('publicationstatus', array('ACTIVE', 'PUBLICATED')));
		$query->createCriteria('transition')->add(Restrictions::eq('taskid', $taskId));
		return $query->find();
	}

	/**
	 * @param integer $documentId
	 * @return array<workflow_persistentdocument_workitem>
	 */
	public function getActiveMessageWorkitemsByDocumentId($documentId)
	{
		$query = $this->createQuery();
		$query->add(Restrictions::eq('documentid', $documentId));
		$query->add(Restrictions::in('publicationstatus', array('ACTIVE', 'PUBLICATED')));
		$query->add(Restrictions::eq('transition.trigger', WorkflowHelper::TRIGGER_MESSAGE));
		return $query->find();
	}
	
	/**
	 * Get all users that can execute the workitem.
	 * If the case parameter '__NEXT_VALID_ACTORS_IDS' is set and contains an array of user ids, these users are returned.
	 * Else, the wwwadmin is returned in an array.
	 * The '__NEXT_VALID_ACTORS_IDS' has to be set before, for example by an automatic transition.
	 * @param workflow_persistentdocument_workitem $workitem
	 * @param boolean $clearParameter says if the '__NEXT_VALID_ACTORS_IDS' parameter has to be cleared at the end of this method.
	 * @return array<users_persistentdocument_user>
	 */
	public function getValidActors($workitem, $clearParameter = true)
	{
		$users = array();
		
		$workflowActionName = $workitem->getExecActionName();
		if (!empty($workflowActionName) && f_util_ClassUtils::methodExists($workflowActionName, "getActorIds"))
		{
			$workflowAction = $workitem->getExecAction();
			$actorsIds = $workflowAction->getActorIds();
		}
		else
		{
			// If there are next actors defined, return them, else get the user for the defined roles.
			$actorsIds = workflow_CaseService::getInstance()->getParameter($workitem->getCase(), '__NEXT_ACTORS_IDS');
			if (!is_array($actorsIds) && count($actorsIds) == 0)
			{
				$permissionService = f_permission_PermissionService::getInstance();
				$roleName = $workitem->getTransition()->getRoleid();
				$roleName = $permissionService->resolveRole($roleName, $workitem->getDocumentid());
				if (Framework::isDebugEnabled())
				{
					Framework::debug(__METHOD__ . ' workItem = ' . $workitem->getId() . ', roleName = ' . $roleName);
				}
				$actorsIds = $permissionService->getUsersByRoleAndDocumentId($roleName, $workitem->getDocumentid());
			}
			else if ($clearParameter)
			{
				workflow_CaseService::getInstance()->clearParameter($workitem->getCase(), '__NEXT_ACTORS_IDS');
			}
		}

		// If the parameter AFFECT_TASKS_TO_SUPER_ADMIN is set to true, add the super-administrator.
		if (workflow_CaseService::getInstance()->getParameter($workitem->getCase(), 'AFFECT_TASKS_TO_SUPER_ADMIN') == 'true')
		{
			$rootUsers = users_BackenduserService::getInstance()->getRootUsers();
			foreach ($rootUsers as $rootUser) 
			{
				if ($rootUser->isPublished())
				{
					$rootUserId = $rootUser->getId();
					if (!in_array($rootUserId, $actorsIds))
					{
						$actorsIds[] = $rootUserId;
					}
				}				
			}
		}

		// If there are user ids, instanciate them.
		if (count($actorsIds) == 0)
		{
			return $users;
		}
		
		$actorsIds = users_UserService::getInstance()->convertToPublishedUserIds($actorsIds);
		foreach ($actorsIds as $actorId)
		{
			$users[] = DocumentHelper::getDocumentInstance($actorId, 'modules_users/user');
		}

		return $users;
	}
	
	/**
	 * @param workflow_persistentdocument_workitem $workitem
	 * @param task_persistentdocument_usertask $task
	 */
	public function updateTaskInfos($workitem, $task)
	{
		$label = $workitem->getLabel() . ' (' . $workitem->getDocumentid() . ')';
		$task->setLabel($label);
		
		$classname = $workitem->getExecActionName();
		if (!empty($classname) && f_util_ClassUtils::classExists($classname) && f_util_ClassUtils::methodExists($classname, 'updateTaskInfos'))
		{
			
			$action = new $classname();
			$action->initialize($workitem);
			$action->updateTaskInfos($task);
		}
	}
}