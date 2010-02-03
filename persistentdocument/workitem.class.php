<?php
/**
 * workflow_persistentdocument_workitem
 * @package modules.workflow
 */
class workflow_persistentdocument_workitem extends workflow_persistentdocument_workitembase
{
	/**
	 * @var workflow_persistentdocument_case
	 */
	private $m_case;

	/**
	 * @return workflow_persistentdocument_case
	 */
	public function getCase()
	{
		if (is_null($this->m_case))
		{
			$this->checkLoaded();
			$relations = $this->getProvider()->getChildRelationBySlaveDocumentId($this->getId(), 'workitem', 'modules_workflow/case');
			$this->m_case = $this->getProvider()->getDocumentInstance($relations[0]->getDocumentId1());
		}
		return $this->m_case;
	}

	/**
	 * @param workflow_persistentdocument_case $case
	 */
	public function setCase($case)
	{
		$this->checkLoaded();
		$this->m_case = $case;
	}

	/**
	 * @return String
	 */
	public function getExecActionName()
	{
		$classname = $this->getTransition()->getActionname();

		if (strpos($classname, '{') !== false)
		{
			$document = DocumentHelper::getDocumentInstance($this->getDocumentid());
			$moduleName = $document->getPersistentModel()->getModuleName();
			$documentName = ucfirst($document->getPersistentModel()->getDocumentName());
			$classname = str_replace(array('{MODULENAME}', '{DOCUMENTNAME}', '{modulename}', '{documentname}'),
			array($moduleName, $documentName, $moduleName, $documentName), $classname);
		}

		return $classname;
	}
}