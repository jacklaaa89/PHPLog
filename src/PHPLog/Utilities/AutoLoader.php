<?php

namespace PHPLog\Utilities;

/**
 * This is the default autoloader for the application.
 * We can register namespaces and directories that will be searched
 * when php fires our autoloading function in the __autoload queue.
 * @version 1
 * @author Jack Timblin
 */
class AutoLoader {
	
	private $namespaces;
	private $directories;
	private $extensions;
	private $registered = false;

	/**
	 * initialises the autoloader.
	 * registers the default extensions to look for.
	 * @version 1
	 * @author Jack Timblin
	 *
	 */
	public function __construct() 
	{
		$this->extensions = array('php');
	}

	/**
	 * gets the registered extensions.
	 * @return array the registered extensions.
	 * @version 1
	 * @author Jack Timblin
	 *
	 */
	public function getExtensions() 
	{
		return $this->extensions;
	}

	/**
	 * sets the registered extensions.
	 * @param array $extensions the extensions to register.
	 * @return RMA\Utilities\AutoLoader returns itself.
	 * @version 1
	 * @author Jack Timblin
	 */
	public function setExtensions($extensions) 
	{
		if(!is_array($extensions) && count($extensions) > 0) {
			$this->extensions = $extensions;
		}
		return $this;
	}

	/**
	 * returns the registered namespaces.
	 * @return array|null the registered namespaces or null if none are set.
	 * @version 1
	 * @author Jack Timblin
	 *
	 */
	public function getNamespaces() 
	{
		return $this->namespaces;
	}

	/**
	 * returns the registered directories.
	 * @return array|null the registered directories or null if none are set.
	 * @version 1
	 * @author Jack Timblin
	 *
	 */
	public function getDirectories() 
	{
		return $this->directories;
	}

	/**
	 * registers namespaces to be autoloaded.
	 * @param array $namespaces the array of namespaces eg. 'Example/Namespace' => 'vendor/namespaces'
	 * @param boolean $merge [optional] whether to merge with current instead of replacing them. default to false.
	 * @return RMA\Utilities\AutoLoader returns itself.
	 * @version 1
	 * @author Jack Timblin
	 */
	public function registerNamespaces($namespaces, $merge = false) 
	{
		$merged = $namespaces;
		if ($merge) {
			$current = $this->namespaces;
			if (is_array($current)) {
				$merged = array_merge($current, $namespaces);
			}
		}
		$this->namespaces = $merged;
		return $this;
	}

	/**
	 * registers directories to be autoloaded.
	 * @param array $dirs the array of dirs
	 * @param boolean $merge [optional] whether to merge with current instead of replacing them. default to false.
	 * @return RMA\Utilities\AutoLoader returns itself.
	 * @version 1
	 * @author Jack Timblin
	 */
	public function registerDirs($dirs, $merge = false) 
	{
		$merged = $dirs;
		if($merge) {
			$current = $this->directories;
			if(is_array($current)) {
				$merged = array_merge($current, $dirs);
			}
		}
		$this->directories = $merged;
		return $this;
	}

	/**
	 * Function to determine if the $haystack starts with the $needle string.
	 * @param string $haystack    the string to test the $needle on.
	 * @param string $needle      the string to test if the $haystack starts with.
	 * @param bool   $ignoreCase  [optional] whether to perform the comparion regardless of case.
	 * @return bool TRUE if the $needle starts with the $haystack, FALSE otherwise.
	 */
	private function startsWith($haystack, $needle, $ignoreCase = true)
	{
		if ($ignoreCase)
		{
			$haystack = strtolower($haystack); $needle = strtolower($needle);
		}
	    return strncmp($haystack, $needle, strlen($needle)) === 0;
	}

	/**
	 * registers the autoloader.
	 * @return RMA\Utilities\AutoLoader returns itself.
	 * @version 1
	 * @author Jack Timblin
	 */
	public function register() 
	{
		if (!$this->registered) {
			spl_autoload_register(array($this, 'autoLoad'));
			$this->registered = true;
		}
		return $this;
	}

	/**
	 * unregisters the autoloader.
	 * @return RMA\Utilities\AutoLoader returns itself.
	 * @version 1
	 * @author Jack Timblin
	 */
	public function unregister() 
	{
		if ($this->registered) {
			spl_autoload_unregister(array($this, 'autoLoad'));
			$this->registered = false;
		}
		return $this;
	}

	/**
	 * the function to be called when phps __autoload queue gets to us.
	 * it attempts to stimulate a 'require' if we find a matching file based on the classname.
	 * @param string $className the name of the class.
	 * @return boolean TRUE if the class was loaded and found, FALSE otherwise.
	 * @version 1
	 * @author Jack Timblin
	 */
	public function autoLoad($className) 
	{
		$ds  = DIRECTORY_SEPARATOR;
		$nss = '\\';
		$ex  = $this->extensions;
		$ns  = $this->namespaces;
		$drs = $this->directories;

		if (is_array($ns)) {
			foreach ($ns as $prefix => $directory) {
				if ($this->startsWith($className, $prefix)) {
					$fn = str_replace($prefix . $nss, "", $className);
					$fn = str_replace($nss, $ds, $fn);
					if ($fn) {
						$fd = rtrim($directory, $ds) . $ds;
						foreach ($ex as $extension) {
							$fp = $fd . $fn . '.' . $extension;
							if (file_exists($fp)) {
								require $fp;
								return true;
							}
						}
					}
				}
			}
		}

		$dsClassName = str_replace("_", $ds, $className);
		$nsClassName = str_replace("\\", $ds, $dsClassName);

		if (is_array($drs)) {
			foreach ($drs as $directory) {
				$fd = rtrim($directory, $ds) . $ds;
				foreach ($ex as $extension) {
					$fp = $fd . $nsClassName . '.' . $extension;
					if (file_exists($fp)) {
						require $fp;
						return true;
					}
				}
			}
		}

		return false;

	}

}