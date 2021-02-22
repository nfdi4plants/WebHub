<?php

// No direct access
defined('_HZEXEC_') or die();

require_once('S3.php');

//Set with all Folder Names
class FolderSet
{
	//There are no sets in PHP5
	private $hmap;
	public function __construct()
	{
		$this->hmap = array();
	}
	public function add($str)
	{
		//Hack to emulate Set like behavior
		$this->hmap[$str] = true; 
	}
	public function getFolders()
	{
		$arr = array();
		foreach ($this->hmap as  $k => $v) 
		{
			array_push($arr, $k);
		}
		return $arr;
	}
}

//Filter class to get content of a specific folder.
class FolderFilter
{
	public $folders;
	public $files;
	public $path;
	public function __construct($response, $prefix)
	{
		$this->path = $prefix . "/";
		//$this->folders = new \Ds\Set(); Not possible since PHP5 has no Containers
		$this->folders = new FolderSet();
		$this->files = array();
		foreach ($response->body->Contents as $element) 
		{
			if ($element->Size == 0)
				continue;
			$name = $element->Key;

			$pattern = '/^' . str_replace('/', '\/', $prefix) . '\//';
			if (preg_match($pattern, $name)) 
			{
				$name = preg_replace($pattern, "", $name);
				if (strpos($name, '/')) 
				{ //Folders contain /-es
					$this->folders->add(explode("/", $name, 2)[0]); //Part bevore the first /
				}
				else
				{
					$newel = $element;
					$newel->name = $name; //JS linke add Attribute
					array_push($this->files, $newel);
				}
			}
		}
	}
}
