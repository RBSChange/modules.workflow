<?php
/**
 * workflow_persistentdocument_place
 * @package modules.workflow
 */
class workflow_persistentdocument_place extends workflow_persistentdocument_placebase
{
	/**
	 * @var workflow_persistentdocument_workflow
	 */
	private $m_workflow;

	/**
	 * @return workflow_persistentdocument_workflow
	 */
	public function getWorkflow()
	{
		if (is_null($this->m_workflow))
		{
			$this->checkLoaded();
			$relations = $this->getProvider()->getChildRelationBySlaveDocumentId($this->getId(), 'places', 'modules_workflow/workflow');
			$this->m_workflow = $this->getProvider()->getDocumentInstance($relations[0]->getDocumentId1());
		}
		return $this->m_workflow;
	}

	/**
	 * @param workflow_persistentdocument_workflow $workflow
	 */
	public function setWorkflow($workflow)
	{
		$this->checkLoaded();
		$this->m_workflow = $workflow;
	}
}