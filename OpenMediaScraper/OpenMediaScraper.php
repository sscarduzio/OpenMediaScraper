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
	private $imageUrls;
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
			$this->imageUrls = $this->parseRawUrls($match[0]);
		}
		unset($match);
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
			
			if(!isArray($this->imageUrls)){
				$this->imageUrls = array();
				$this->imageUrls[]=$theImage;
				continue;
			}
			
			// Sort by rank, Append to the beginning of the array if it's the best ranked
			if($this->doesRankBest($theImage)){
				array_push($this->imageUrls, $theImage);
			}
			// ..or to the end of the array if it's worst ranked
			else{
				$this->imageUrls[] = $theImage;
			}
		}
		return $imgArr;
	}
	
	public function getUrl(){
		return $this->url;
	}
	public function getTitle(){
		return $this->title;
	}
	public function getDescription(){
		return $this->description;
	}
	public function getImageUrls(){
		return $this->imageUrls;
	}
}
$su = new OpenMediaScraper("http://www.codesigner.eu");

var_dump($su);
