<?php
/**
 * @package modules.workflow
 */
class workflow_WorkflowListener
{
	/**
	 * @param f_persistentdocument_DocumentService $sender
	 * @param array $params
	 */
	public function onPersistentDocumentDeleted($sender, $params)
	{
		if ($params['document'] instanceof f_persistentdocument_PersistentDocument)
		{
			$document = $params['document'];
			
			if ($document->getPersistentModel()->hasWorkflow())
			{
				$caseService = workflow_CaseService::getInstance();
				
				$query = $caseService->createQuery();
				$cases = $query->add(Restrictions::eq('documentid', $document->getId()))
					->add(Restrictions::eq('lang', RequestContext::getInstance()->getLang()))
					->find();
				foreach ($cases as $case)
				{
					$case->delete();
				}
			}
		}
	}
	
	/**
	 * @param f_persistentdocument_DocumentService $sender
	 * @param array $params
	 */
	public function onHourChange($sender, $params)
	{
		try
		{
			Controller::getInstance();
		}
		catch (Exception $e)
		{
			Framework::info(__METHOD__ . ' ' . $e->getMessage());
			Controller::newInstance("controller_ChangeController");
		}
		workflow_WorkflowEngineService::getInstance()->executeScheduledTasks();
	}
}