<?php

/**
 * OpenMediaScraper
 * 
 * @author sscarduzio
 *
 * Todo
 * ====
 * Parsing of opengraph metas
 * Sort images by size without downloading (curl jpeg stream/http headers)
 * configurable target size
 * Allow actual (multiple?) download+resizing
 *
 */
define('BASE_PATH',str_replace('\\','/',dirname(__FILE__)));
array_walk(glob(BASE_PATH.'/lib/*/*.php'),create_function('$v,$i', 'return require_once($v);'));
array_walk(glob(BASE_PATH.'/*.php'),create_function('$v,$i', 'return require_once($v);'));
array_walk(glob(BASE_PATH.'/common/*/*.php'),create_function('$v,$i', 'return require_once($v);'));


class OpenMediaScraper {
	/**
	 * 
	 * Enter description here ...
	 * @var unknown_type
	 */
	private $url;
	private $title;
	private $description;
	private $imagePool;
	private $allSorted = false;
	private $provisioner;


	public function OpenMediaScraper($url) {
		$provisioner = Provisioner::getInstance();
		
		$html = file_get_html($url);
		
		// Set the url
		$this->url = $url;

		//Grab the page title
		$info->title = trim($html->find('title', 0)->plaintext);

		//Grab the page description
		foreach($html->find('meta') as $meta)
		if ($meta->name == "description")
		$this->description = trim($meta->content);
			

		$count = preg_match_all('/[a-z0-9\_\-\/\.]+(png|PNG|jp?g|JP?G|gif|GIF)/', $html, $match);
		echo "found $count results";
		if ($count === FALSE) {
			echo('not found\n');
		}
		else{
			//Grab the image URLs
			$imgArr = $match[0];
			$this->imagePool = $this->parseRawUrls($match[0]);
		}
		unset($match);
	}

	// Stupid getters
	public function getPageUrl(){
		return $this->url;
	}
	public function getPageTitle(){
		return $this->title;
	}
	public function getPageDescription(){
		return $this->description;
	}
	

	// Some parsing to obtain just absolute URLs
	private function parseRawUrls($imgArr){
		for($i=0; $i<sizeof($imgArr); $i++){
			$rawUrl = $imgArr[$i];
			
			//Turn any relative Urls into absolutes
			if (substr($rawUrl,0,2)=="//") {
				$theUrl =  'http:'.$rawUrl;
			}
			elseif (substr($rawUrl,0,4)!="http"){
				$theUrl =  $this->url.$rawUrl;
			}
			else {
				$theUrl =  $rawUrl;
			}
			
			// Drop image if it doesnt satisfy minimum requirements
			$theImage = new Image($theUrl);
			if(!$this->isAcceptable($theImage)){
				continue;	
			}
			
			if(!is_array($this->imagePool)){
				$this->imagePool = array();
				$this->imagePool[]=$theImage;
				continue;
			}
			
			$this->addImage($theImage);
		}
		return $imgArr;
	}
	private function isAcceptable($theImage){
		#TODO Call provisioner and mingle with him about this
		return true;
	}
	private function addImage($theImage){ ################################ this array shit does not work, plz sort like OOP commends.
		#TODO clever way to calculate rank
		$r = 2;
		$this->allSorted = false;
		$this->imagePool[]=array('img' => $theImage, 'rank' => $r);
	}
	
	/**
	 * getPageImagePool
	 * @return rank-sorted array of Image objects
	 */
	public function getPageImagePool(){
		if(!$this->allSorted){
			$this->aasort($this->imagePool, 'rank');
		}
		foreach ($this->imagePool as $row) $ret[] = $row['img'];
		return $ret;
	}
	
	// Sort multidimensional associative array by custom key.
	private function aasort(&$array, $key) {
		$sorter=array();
		$ret=array();
		reset($array);
		foreach ($array as $ii => $va) {
			$sorter[$ii]=$va[$key];
		}
		asort($sorter);
		foreach ($sorter as $ii => $va) {
			$ret[$ii]=$array[$ii];
		}
		$array=$ret;
		$this->allSorted = true;
	}
	
}

$su = new OpenMediaScraper("http://www.codesigner.eu");
$pool = $su->getPageImagePool();

var_dump($pool);
