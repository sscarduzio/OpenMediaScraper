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

/*
* Timezone settings, refer to: http://php.net/manual/en/timezones.php
*/
date_default_timezone_set("Europe/Helsinki");

define('RID', strtoupper(uniqid(gethostname().':', null)));
define('BASE_PATH',str_replace('\\','/',dirname(__FILE__)));
array_walk(glob(BASE_PATH.'/lib/*/*.php'),create_function('$v,$i', 'return require_once($v);'));
array_walk(glob(BASE_PATH.'/*.php'),create_function('$v,$i', 'return require_once($v);'));
array_walk(glob(BASE_PATH.'/common/*/*.php'),create_function('$v,$i', 'return require_once($v);'));


define("REGEXP_MATCH_IMAGES", '/(http|https)(:\/\/)+[A-Za-z0-9\@\$\%\?\=\;\&\_\-\/\.]+(\.png|\.PNG|\.jp?g|\.JP?G|\.gif|\.GIF)/');

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
	private $provisioner;
	private $updated_time;
	
	public function OpenMediaScraper($url) {
		$this->provisioner = Provisioner::getInstance();
		
		// Check if page url is actually already an image url
		if(preg_match_all(REGEXP_MATCH_IMAGES, $url, $match) > 0){
			$this->addImage(new Image($url));
			return;
		}
		
		$html = file_get_html($url);

		// Normalize trailing slash
		if(!substr($url,sizeof($url)-1,1) == '/'){
			$url.='/';
		}
		$this->url = $url;
		
		//Grab the page title
		$this->title = trim($html->find('title', 0)->plaintext);
		
		// Explore the meta and open graph headers first
		foreach($html->find('meta') as $meta){
			if ($meta->name == "description"){
				$this->description = trim($meta->content);
				inf("META description found: " . $this->description);
			}
			if ($meta->property == "og:title"){
				$this->title = trim($meta->content);
				inf("META og:title found: " . $this->title);
			}

			if ($meta->property == "og:url"){
				$x = trim($meta->content);
				if(strlen($x)>1){
					inf("META og:url found: " . $x);
					$this->url = $x;
				}
			}
			if ($meta->property == "og:updated_time"){
				$x = trim($meta->content);
				if(strlen($x) == 10){
					inf("META og:updated_time found: " . $x);
					$this->updated_time = $x;
				}
			}

			if ($meta->property == "og:image"){
				$x = trim($meta->content);
				if(strlen($x)>1){
					inf("META og:image found: " . $x);
					$this->addImage(new Image($x));
				}
			}
		}		
		
		if(!isset($this->updated_time)){
			$this->updated_time = time();
		}
		// Send the info about the page
		$o = (object) array(
			'title' => $this->title,
			'description' => $this->description,
			'url' => $this->url
		);
		echo json_encode($o);
		
		// Take care of the images now
		$count = preg_match_all(REGEXP_MATCH_IMAGES, $html, $match);
		inf("found $count images in $url");
		if ($count === FALSE) {
			inf('OMS: no images found for page URL: ' . $url);
		}
		else{
			//Grab the image URLs
			$this->parseRawUrls($match[0]);
			inf("OMS: scrapped " . ($count - sizeof($this->imagePool)) . " of $count images");
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
			dbg('match: ' . $rawUrl);
			//Turn any relative Urls into absolutes
			
			if (substr($rawUrl,0,2)=="//") {
				$theUrl =  'http:'.$rawUrl;
			}
			// already absolute 
			else if(substr($rawUrl,0,4) == "http"){
				$theUrl = $rawUrl;
			}
			// href="/img.jpg" needs  <document root>/img.jpg
			else if (substr($rawUrl,0,1)=="/"){
				// regexp to pick the document root without trailing '/'
				preg_match('/(https|http)+:(\/\/)+[A-Za-z0-9\_\-\.]+(?=\/)/', $this->url, $match);
				//$rawUrl = substr($rawUrl, 1);
				$theUrl=$match[0] . $rawUrl;
			}
			else {
				// href="img.jpg" is <document_root><webpage_path>/img.jpg
				$theUrl = $this->url.$rawUrl;
			}
			
			if($theUrl != $rawUrl){
				dbg('parsed: ' . $rawUrl . " => " . $theUrl);
			}
			
			// Drop image if it doesnt satisfy minimum requirements
			$theImage = new Image($theUrl);
			if(!$this->provisioner->isAcceptable($theImage)){
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
	
	private function addImage($theImage){ ################################ this array shit does not work, plz sort like OOP commends.
// 		#TODO clever way to calculate rank
// 		$r = $theImage->getArea();
// 		if(!is_int($r)) {
// 			$r=0;
// 		}
		if(!is_null($theImage)){
			$this->imagePool[]=$theImage;
			echo $theImage->serialize();
		}
	}
	
	/**
	 * getPageImagePool
	 * @return rank-sorted array of Image objects
	 */
	public function getPageImagePool(){
		return is_int($this->imagePool) ? 0 : $this->imagePool;
	}
}

$su = new OpenMediaScraper("http://net.tutsplus.com/articles/news/learn-jquery-in-30-days/");
$su = new OpenMediaScraper("http://mtv.it/");