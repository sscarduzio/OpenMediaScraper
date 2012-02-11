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

for ($i = 0; $i < ob_get_level(); $i++) {
	ob_end_flush();
}
ob_implicit_flush(1);
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
	private $html;
	
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
	
	/**
	 * Constructor takes care of:
	 * - Get the HTML
	 * - Find page metadata
	 * - Find pool of images
	 * 
	 * Should return in a pageQueryTime+epsilon.
	 * 
	 * @param String $url
	 */
	public function OpenMediaScraper($url) {
		$this->provisioner = Provisioner::getInstance();
		
		// Check if page url is actually already an image url
		if(preg_match_all(REGEXP_MATCH_IMAGES, $url, $match) > 0){
			$this->url = $url;
			$this->title = "Untitled image";
			return;
		}
		
		$this->html = file_get_html($url);

		// Normalize trailing slash
		if(!substr($url,sizeof($url)-1,1) == '/'){
			$url.='/';
		}
		$this->url = $url;
		
		$this->parseHTMLHead();
		
		// Handle the updated time if not found in opengraph metadata
		if(!isset($this->updated_time)){
			$this->updated_time = time();
		}
		
		// Go through the images and rank them
		$this->findImageUrls();
		
	}
	
   /**
	* Find URLs of images inside the HTML, parse urls and build a list of
	* image URLs.
	*/
	private function findImageUrls(){
		// Take care of the images now
		$count = preg_match_all(REGEXP_MATCH_IMAGES, $this->html, $match);
		inf("found $count images in $this->url");
		if ($count === FALSE) {
			inf('OMS: no images found for page URL: ' . $url);
		}
		else{
			//Grab the image URLs
			$unique = array_values(array_unique($match[0]));
			$this->imagePool = $this->parseRawUrls($unique);
			inf("OMS: dups deleted: ".($count-sizeof($unique))."; scrapped " . ($count - sizeof($this->imagePool)) . " of $count images");
		}
		unset($match);
	}


	// Some parsing to obtain just absolute URLs
	private function parseRawUrls($imgArr){
		$parsedURLs = array();
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
				
			$parsedURLs[]=$theUrl;
		}
		return $parsedURLs;
	}
		
	/**
	 * Parse and serialize image at index i 
	 * @param int $i
	 */
	public function getImageURL($i){
		if(!is_int($i) && $i<0 || !isset($this->imagePool[$i]) ){
			return null;
		}
		
		// Drop image if it doesnt satisfy minimum requirements
		$x=$i;
		while(!$this->provisioner->isAcceptable(new Image($this->imagePool[$x]))){
			unset($this->imagePool[$x]);
			$i++;		
		}
		// Reindex the array
		$this->imagePool = array_values($this->imagePool);
		if(isset($this->imagePool[$i])){
			return $this->imagePool[$i]->serialize();
		}
	}
	
	/**
	 *  Send the info about the page
	 */
	public function JSON_getPageInfo(){
		$a = array(
		'title' => $this->title,
		'description' => $this->description,
		'url' => $this->url,
		'imgPool' => $this->imagePool
		);
		return json_encode((object)$a);
	}
	
	/**
	 * Collect PAGE metadata from OpenGraph meta tags and other tags in <head>.
	 */
	private function parseHTMLHead(){
		//Grab the page title
		$this->title = trim($this->html->find('title', 0)->plaintext);
		
		// Explore the meta and open graph headers first
		foreach($this->html->find('meta') as $meta){
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
	}
	
	private function addImage($theImage){ ################################ this array shit does not work, plz sort like OOP commends.
// 		#TODO clever way to calculate rank
// 		$r = $theImage->getArea();
// 		if(!is_int($r)) {
// 			$r=0;
// 		}

		// Initialize the array
		if(!is_array($this->imagePool)){
			$this->imagePool = array();
		}
		
		// Add img
		if(!is_null($theImage)){
			$this->imagePool[]=$theImage;
		}
	}
	
}
// echo('{"title":"Web development tutorials, from beginner to advanced | Nettuts+","description":"Nettuts+ is a blog and community for Web Development tutorials. Learn php, JavaScript, WordPress, HTML5, CSS, Ruby and much more.","url":"http:\/\/www.nettuts.com\/"}');
// sleep(20);
// die();
if(!isset($_REQUEST['page']) || preg_match('/(https|http)+:(\/\/)+[A-Za-z0-9\@\$\%\?\=\;\&\_\-\/\.]/', $_REQUEST['page'], $match) != 1){
	die('{"status":"bad input"}');
	//$_REQUEST['page'] ="http://www.nettuts.com/";
}
$page = $_REQUEST['page'];
$su = new OpenMediaScraper($page);
echo $su->JSON_getPageInfo();