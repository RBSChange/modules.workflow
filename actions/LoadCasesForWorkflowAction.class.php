<?php
/**
 * workflow_LoadCasesForWorkflowAction
 * @package modules.workflow.actions
 */
class workflow_LoadCasesForWorkflowAction extends f_action_BaseJSONAction
{
	/**
	 * @param Context $context
	 * @param Request $request
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
			$dateTimeFormat = f_Locale::translateUI('&modules.uixul.bo.datePicker.calendar.dataWriterTimeFormat;');
			foreach (workflow_CaseService::getInstance()->getByWorkflow($workflowId, $targetId, $offset, $limit) as $case)
			{	
				$status = $case->getPublicationstatus();
				$documentInfo = array();
				$documentInfo['documentId'] = $case->getId();
				$documentInfo['targetId'] = $case->getDocumentid();
				$documentInfo['status'] = $status;
				$documentInfo['statusLabel'] = $this->getStatusLabel($status);
				$documentInfo['creationdate'] = date_DateFormat::format($case->getUICreationdate(), $dateTimeFormat);
				$documentInfo['modificationdate'] = date_DateFormat::format($case->getUIModificationdate(), $dateTimeFormat);
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
	 * @param String $status
	 * @return String
	 */
	private function getStatusLabel($status)
	{
		if (!isset($this->statusLabels[$status]))
		{
			$this->statusLabels[$status] = f_Locale::translate('&modules.workflow.bo.doceditor.case-panel.Status-' . ucfirst(strtolower($status)) . ';');
		}
		return $this->statusLabels[$status];
	}
}