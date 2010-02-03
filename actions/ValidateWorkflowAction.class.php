<?php
/**
 * This action just validates a workflow.
 * @package modules.workflow
 */
class workflow_ValidateWorkflowAction extends f_action_BaseAction
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		$workflowIds = $this->getDocumentIdArrayFromRequest($request);
		if (Framework::isDebugEnabled())
		{
			Framework::debug(__METHOD__ . " : ids = \n" . f_util_StringUtils::parray($workflowIds));
		}

		$workflowDesignerService = workflow_WorkflowDesignerService::getInstance();
		foreach ($workflowIds as $workflowId)
		{
			if (Framework::isDebugEnabled())
			{
				Framework::debug(__METHOD__ . " : validate $workflowId");
			}
			$workflowDesignerService->validateWorkflowDefinitionById($workflowId);
		}

		if (Framework::isDebugEnabled())
		{
			Framework::debug(__METHOD__ . " : return success view");
		}
		return self::getSuccessView();
	}
}