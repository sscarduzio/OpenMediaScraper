<?php

/**
 * Container and implementer of configuration.
 * @author sscarduzio
 *
 */
define("MIN_AREA", 50*50);
define("MIN_WIDTH", 50);
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
	public $maxAcceptSize = MAX_ACCEPT_SIZE;
	public $thumbnailWidth = THUMBNAIL_WIDTH; 
	
	public static function  getInstance(){
		if(is_null(self::$instance)){
			$class = get_called_class();
			self::$instance = new $class();
		}
		return self::$instance;
	}
	
	
	
}