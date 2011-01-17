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
		$workflow->getDocumentService()->publishIfPossible($workflow->getId());
		$this->logAction($workflow);
		if ($workflow->isPublished())
		{
			$message = LocaleService::getInstance()->transBO('m.workflow.bo.actions.validate-workflow-success', array('ucf'));
		}
		else
		{
			$message = $workflow->getDocumentService()->getUIActivePublicationStatusInfo($workflow, RequestContext::getInstance()->getLang());
			if (!$message)
			{
				$message = LocaleService::getInstance()->transBO('m.workflow.bo.actions.validate-workflow-error', array('ucf'));
			}
		}
		return $this->sendJSON(array('message' => $message));
	}
}