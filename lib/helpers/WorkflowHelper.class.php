<?php
/**
 * @package modules.workflow
 */
class WorkflowHelper
{
	// Place types.
	const PLACE_TYPE_START = 1;
	const PLACE_TYPE_INTERMEDIATE = 5;
	const PLACE_TYPE_END = 9;

	// Transition triggers.
	const TRIGGER_USER = 'USER';
	const TRIGGER_AUTO = 'AUTO';
	const TRIGGER_MESSAGE = 'MSG';
	const TRIGGER_TIME = 'TIME';

	// Arc directions.
	const DIRECTION_PLACE_TO_TRANSITION = 'IN';
	const DIRECTION_TRANSITION_TO_PLACE = 'OUT';

	// Arc types.
	const ARC_TYPE_SEQUENTIAL = 'SEQ';
	const ARC_TYPE_EXPLICIT_OR_SPLIT = 'EX_OR_SP';
	const ARC_TYPE_IMPLICIT_OR_SPLIT = 'IM_OR_SP';
	const ARC_TYPE_OR_JOIN = 'OR_JO';
	const ARC_TYPE_AND_SPLIT = 'AND_SP';
	const ARC_TYPE_AND_JOIN = 'AND_JO';

	// Default replacements for the notifications.
	const DEFAULT_REPLACEMENTS = '{documentId}, {documentLabel}, {documentLang}, {documentPath}, {workflowId}, {workflowLabel}, {transitionId}, {transitionLabel}, {currentUserId}, {currentUserFullname}, {__LAST_COMMENTARY}, {__LAST_DECISION}';

	/**
	 * @return workflow_WorkflowService
	 * @deprecated use workflow_WorkflowService::getInstance()
	 */
	public static function getWorkflowService()
	{
		return workflow_WorkflowService::getInstance();
	}

	/**
	 * @return workflow_PlaceService
	 * @deprecated use workflow_PlaceService::getInstance()
	 */
	public static function getPlaceService()
	{
		return workflow_PlaceService::getInstance();
	}

	/**
	 * @return workflow_TransitionService
	 * @deprecated use workflow_TransitionService::getInstance()
	 */
	public static function getTransitionService()
	{
		return workflow_TransitionService::getInstance();
	}

	/**
	 * @return workflow_ArcService
	 * @deprecated use workflow_ArcService::getInstance()
	 */
	public static function getArcService()
	{
		return workflow_ArcService::getInstance();
	}

	/**
	 * @return workflow_CaseService
	 * @deprecated use workflow_CaseService::getInstance()
	 */
	public static function getCaseService()
	{
		return workflow_CaseService::getInstance();
	}

	/**
	 * @return workflow_TokenService
	 * @deprecated use workflow_TokenService::getInstance()
	 */
	public static function getTokenService()
	{
		return workflow_TokenService::getInstance();
	}

	/**
	 * @return workflow_WorkitemService
	 * @deprecated use workflow_WorkitemService::getInstance()
	 */
	public static function getWorkitemService()
	{
		return workflow_WorkitemService::getInstance();
	}

	/**
	 * @return notification_NotificationService
	 * @deprecated use notification_NotificationService::getInstance()
	 */
	public static function getNotificationService()
	{
		return notification_NotificationService::getInstance();
	}

	/**
	 * @return users_UserService
	 * @deprecated use users_UserService::getInstance()
	 */
	public static function getUserService()
	{
		return users_UserService::getInstance();
	}

	/**
	 * @return workflow_WorkflowTesterService
	 * @deprecated use workflow_WorkflowTesterService::getInstance()
	 */
	public static function getWorkflowTesterService()
	{
		return workflow_WorkflowTesterService::getInstance();
	}

	/**
	 * @return workflow_WorkflowDesignerService
	 * @deprecated use workflow_WorkflowDesignerService::getInstance()
	 */
	public static function getWorkflowDesignerService()
	{
		return workflow_WorkflowDesignerService::getInstance();
	}

	/**
	 * @return workflow_WorkflowEngineService
	 * @deprecated use workflow_WorkflowEngineService::getInstance()
	 */
	public static function getWorkflowEngineService()
	{
		return workflow_WorkflowEngineService::getInstance();
	}

	/**
	 * @return f_persistentdocument_DocumentService
	 * @deprecated use f_persistentdocument_DocumentService::getInstance()
	 */
	public static function getDocumentService()
	{
		return f_persistentdocument_DocumentService::getInstance();
	}

	/**
	 * Add new log entry.
	 * @param integer $documentId
	 * @param string $actorName
	 * @param string $actionLabel
	 * @param string $decision
	 * @param string $commentary
	 * @return boolean true if the log entry is correctly added, false else.
	 */
	public static function addLogEntry($documentId, $actorName, $actionLabel, $decision = '', $commentary = '')
	{
		generic_DocumentlogentryService::getInstance()->addLogEntry($documentId, $actorName, $actionLabel, $decision, $commentary);
	}
}