<?php
class Image {
	
	protected $url;
	protected $width;
	protected $height;
	private $isJpg = false;
	private $imgData;
	
	public function Image($url){
		$this->url=$url;
		$this->isJpg = stristr(strtolower($this->url), ".jpg") || stristr(strtolower($this->url), ".jpeg");
	}
	
	public function getArea(){
		return $this->getHeight() * $this->getWidth();
	}
	public function getWidth(){
		if(!is_int($this->width)){
			$this->calculateDimensions();
		}
		return $this->width;
	}
	
	public function getHeight(){
		if(!is_int($this->height)){
			$this->calculateDimensions();
		}
		return $this->height;
	}
	
	protected function calculateDimensions(){
		if((!is_int($this->width) || !is_int($this->height)) ){
			if($this->isJpg){
				$this->getjpegsize($this->url);
			}
			else {
				$this->imgData = file_get_contents($this->url);
				$img = imagecreatefromstring($this->fetchRawData());
				$this->width = imagesx($img);
				$this->height = imagesy($img);
			}
					
		}
	}
	
	protected function fetchRawData(){
		if (is_null($this->imgData)){
			$this->imgData = file_get_contents($this->url);
		}
		return $this->imgData;
	}
	
	// Retrieve JPEG width and height without downloading/reading entire image.
	private function getjpegsize($img_loc) {
		$handle = fopen($img_loc, "rb") or die("Invalid file stream.");
		$new_block = NULL;
		if(!feof($handle)) {
			$new_block = fread($handle, 32);
			$i = 0;
			if($new_block[$i]=="\xFF" && $new_block[$i+1]=="\xD8" && $new_block[$i+2]=="\xFF" && $new_block[$i+3]=="\xE0") {
				$i += 4;
				if($new_block[$i+2]=="\x4A" && $new_block[$i+3]=="\x46" && $new_block[$i+4]=="\x49" && $new_block[$i+5]=="\x46" && $new_block[$i+6]=="\x00") {
					// Read block size and skip ahead to begin cycling through blocks in search of SOF marker
					$block_size = unpack("H*", $new_block[$i] . $new_block[$i+1]);
					$block_size = hexdec($block_size[1]);
					while(!feof($handle)) {
						$i += $block_size;
						$new_block .= fread($handle, $block_size);
						if($new_block[$i]=="\xFF") {
							// New block detected, check for SOF marker
							$sof_marker = array("\xC0", "\xC1", "\xC2", "\xC3", "\xC5", "\xC6", "\xC7", "\xC8", "\xC9", "\xCA", "\xCB", "\xCD", "\xCE", "\xCF");
							if(in_array($new_block[$i+1], $sof_marker)) {
								// SOF marker detected. Width and height information is contained in bytes 4-7 after this byte.
								$size_data = $new_block[$i+2] . $new_block[$i+3] . $new_block[$i+4] . $new_block[$i+5] . $new_block[$i+6] . $new_block[$i+7] . $new_block[$i+8];
								$unpacked = unpack("H*", $size_data);
								$unpacked = $unpacked[1];
								$height = hexdec($unpacked[6] . $unpacked[7] . $unpacked[8] . $unpacked[9]);
								$width = hexdec($unpacked[10] . $unpacked[11] . $unpacked[12] . $unpacked[13]);
								$this->height = $height;
								$this->width = $width;
								return TRUE;
							} else {
								// Skip block marker and read block size
								$i += 2;
								$block_size = unpack("H*", $new_block[$i] . $new_block[$i+1]);
								$block_size = hexdec($block_size[1]);
							}
						} else {
							return FALSE;
						}
					}
				}
			}
		}
		return FALSE;
	}
	
}

// $x = new Image("http://meteli.net/img/archive/AR9492_7_142x142.jpg");
// echo $x->getArea();
// $x = new Image("http://www.codesigner.eu/wp-content/themes/hemingway-reloaded-10/images/past.gif");
// echo $x->getArea();