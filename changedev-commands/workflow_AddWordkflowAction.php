<?php
/**
 * commands_workflow_AddWordkflowaction
 * @package modules.workflow.command
 */
class commands_workflow_AddWordkflowAction extends c_ChangescriptCommand
{
	/**
	 * @return String
	 * @example "<moduleName> <name>"
	 */
	function getUsage()
	{
		return "<moduleName> <name>";
	}

	/**
	 * @return String
	 * @example "initialize a document"
	 */
	function getDescription()
	{
		return "adds a new workflow action";
	}
	
	/**
	 * This method is used to handle auto-completion for this command.
	 * @param Integer $completeParamCount the parameters that are already complete in the command line
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @return String[] or null
	 */
	function getParameters($completeParamCount, $params, $options, $current)
	{
		$components = array();
		
		if ($completeParamCount == 0)
		{
			foreach (glob("modules/*", GLOB_ONLYDIR) as $module)
			{
				$components[] = basename($module);
			}
			return $components;
		}
		
		return array_diff($components, $params);
	}
	
	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @return boolean
	 */
	protected function validateArgs($params, $options)
	{
		return (count($params) == 2);
	}

	/**
	 * @return String[]
	 */
//	function getOptions()
//	{
//	}

	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		$this->message("== AddWordkflowaction ==");

		$this->loadFramework();
		
		$moduleName = strtolower($params[0]);
		$actionName = ucfirst($params[1]);
		if (!ModuleService::getInstance()->moduleExists($moduleName))
		{
			return $this->quitError("Component $moduleName does not exits");
		}
		
		$this->generateAction($moduleName, $actionName);
					
		return $this->quitOk("Command successfully executed");
	}
	
	/**
	 * @param string $moduleName
	 * @param string $actionName
	 */
	private function generateAction($moduleName, $actionName)
	{
		$folder = f_util_FileUtils::buildWebeditPath('modules', $moduleName, 'lib', 'workflowactions');
		$file = $folder . DIRECTORY_SEPARATOR . $actionName . 'WorkflowAction.php';
		$class = $moduleName . '_' . $actionName . 'WorkflowAction';
		
		if (file_exists($file))
		{
			$this->warnMessage('Workflow action "' . $actionName . '" already exists in ' . $moduleName . '".');
		}
		else
		{
			$generator = new builder_Generator();
			$generator->setTemplateDir(f_util_FileUtils::buildWebeditPath('modules', 'workflow', 'templates', 'builder'));
			$generator->assign_by_ref('author', $this->getAuthor());
			$generator->assign_by_ref('name', $actionName);
			$generator->assign_by_ref('module', $moduleName);
			$generator->assign_by_ref('date', date('r'));
			$generator->assign_by_ref('class', $class);
			$generator->assign_by_ref('modelName', $modelName);
			$generator->assign_by_ref('parameters', $paramNames);
			$result = $generator->fetch('workflowAction.tpl');
			
			f_util_FileUtils::mkdir($folder);
			f_util_FileUtils::write($file, $result);
			ClassResolver::getInstance()->appendToAutoloadFile($class, realpath($file));
		}
		$this->message('Workflow action class path: ' . $file);
	}
}