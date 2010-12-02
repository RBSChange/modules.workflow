<?php
class workflow_ValidateWorkflowAction extends f_action_BaseJSONAction
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		$workflow = $this->getDocumentInstanceFromRequest($request);
		$workflowDesignerService = workflow_WorkflowDesignerService::getInstance();
		$ok = $workflowDesignerService->validateWorkflowDefinition($workflow) ? 'success' : 'error';
		$this->logAction($workflow);
		return $this->sendJSON(array('message' => LocaleService::getInstance()->transBO('m.workflow.bo.actions.validate-workflow-' . $ok)));
	}
}