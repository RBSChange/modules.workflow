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
		if ($this->m_case === null)
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
	
	/**
	 * @return workflow_Workflowaction
	 * @throws Exception if workflowaction class does not exists
	 */
	public function getExecAction()
	{
		$className = $this->getExecActionName();
		if (!f_util_ClassUtils::classExists($className))
		{
			throw new Exception("Could not find class ".$className);
		}
		$action = new $className();
		$action->initialize($this);
		return $action;
	}
	
	/**
	 * @return f_persistentdocument_PersistentDocument
	 */
	public function getDocument()
	{
		return DocumentHelper::getDocumentInstance($this->getDocumentid());
	}
	
	/**
	 * @return users_persistentdocument_user
	 */
	public function getUser()
	{
		$userid = $this->getUserid();
		if ($userid)
		{
			return DocumentHelper::getDocumentInstance($userid);
		}
		return null;
	}
}