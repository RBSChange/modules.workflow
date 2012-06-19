<?php
/**
 * workflow_LoadCasesForWorkflowAction
 * @package modules.workflow.actions
 */
class workflow_LoadCasesForWorkflowAction extends change_JSONAction
{
	/**
	 * @param change_Context $context
	 * @param change_Request $request
	 */
	public function _execute($context, $request)
	{
		$result = array();

		$workflowId = $this->getDocumentIdFromRequest($request);
		$targetId = $request->getParameter('targetId');
		$offset = $request->getParameter('startIndex');
		$offset = ($offset < 0) ? 0 : $offset;
		$limit = $request->getParameter('pageSize');
		$limit = ($limit < 0) ? 0 : $limit;
		$result['startIndex'] = $offset;
		$result['pageSize'] = $limit;
		$result['total'] = workflow_CaseService::getInstance()->getCountByWorkflow($workflowId, $targetId);

		$documentsInfo = array();
		if ($result['total'] > 0)
		{
			foreach (workflow_CaseService::getInstance()->getByWorkflow($workflowId, $targetId, $offset, $limit) as $case)
			{	
				$status = $case->getPublicationstatus();
				$documentInfo = array();
				$documentInfo['documentId'] = $case->getId();
				$documentInfo['targetId'] = $case->getDocumentid();
				$documentInfo['status'] = $status;
				$documentInfo['statusLabel'] = $this->getStatusLabel($status);
				$documentInfo['creationdate'] = date_Formatter::toDefaultDateTimeBO($case->getUICreationdate());
				$documentInfo['modificationdate'] = date_Formatter::toDefaultDateTimeBO($case->getUIModificationdate());
				$documentsInfo[] = $documentInfo;
			}
		}
		$result['documents'] = $documentsInfo;
		
		return $this->sendJSON($result);
	}
	
	/**
	 * @var Array<String, String>
	 */
	private $statusLabels = array();
	
	/**
	 * @param string $status
	 * @return string
	 */
	private function getStatusLabel($status)
	{
		if (!isset($this->statusLabels[$status]))
		{
			$this->statusLabels[$status] = LocaleService::getInstance()->trans('m.workflow.bo.doceditor.case-panel.status-' . strtolower($status), array('ucf'));
		}
		return $this->statusLabels[$status];
	}
}