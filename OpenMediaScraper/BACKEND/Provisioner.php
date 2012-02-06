<?php

/**
 * Container and implementer of configuration.
 * @author sscarduzio
 *
 */
define("MIN_AREA", 50*50);

define("MIN_WIDTH", 100);
define("MAX_WIDTH", 600);

define("MIN_HEIGHT", MIN_WIDTH);
define("MAX_HEIGHT", MAX_WIDTH);

define("MAX_ACCEPT_SIZE", 1024*768);
define("THUMBNAIL_WIDTH", 50);

class Provisioner {
	/**
	 * Singleton instance
	 */
	private static $instance;
	
	// #TODO a lot of configuration that may come from WP gui for ex.
	public $minArea = MIN_AREA;
	
	public $minWidth = MIN_WIDTH;
	public $maxWidth = MAX_WIDTH;
	
	public $minHeight = MIN_HEIGHT;
	public $maxHeight = MAX_HEIGHT;
	
	public $maxAcceptSize = MAX_ACCEPT_SIZE;
	public $thumbnailWidth = THUMBNAIL_WIDTH; 
	
	public static function  getInstance(){
		if(is_null(self::$instance)){
			$class = get_called_class();
			self::$instance = new $class();
		}
		return self::$instance;
	}
	
	
	public function isAcceptable($theImage){
		$prefix = $theImage->getImgID() ." DISCARDING: ";
		
		if($theImage->getWidth() < $this->minWidth){
			dbg("$prefix" . $theImage->getWidth() ."px REASON: min width.");
			return false;
		}
		if($theImage->getHeight() < $this->minHeight){
			dbg("$prefix" . $theImage->getHeight() ."px REASON: min height.");
			return false;
		}
		if($theImage->getHeight() > $this->maxHeight){
			dbg("$prefix" . $theImage->getHeight() ."px REASON: max height.");
			return false;
		}
		if($theImage->getWidth() > $this->maxWidth){
			dbg("$prefix" . $theImage->getWidth() ."px REASON: max width.");
			return false;
		}
		
		return true;
	}
	
	
}