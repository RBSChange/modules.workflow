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