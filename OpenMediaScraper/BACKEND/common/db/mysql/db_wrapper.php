<?php

/**
 * common.db.mysql.DBWrapper
 *
 * DBWrapper (created: Jul 27, 2011)
 * @author Simone Scarduzio
 * tags
 */

namespace common\db\mysql;

class DBWrapper {
	protected static  $instance = null;
	private  $link;

	protected function DBWrapper(){
		dbg("Connecting to: ". DB_HOST ." as: ". DB_USR . ":" . DB_PASSWD);
		$this->link = @mysql_connect(DB_HOST, DB_USR, DB_PASSWD);
		if (!$this->link) {
			err('FATAL: Could not connect: ' . mysql_error());
			throw new Exception(mysql_error());
		}
		if(!$this->query("USE ". DB_NAME .";")){
			err('FATAL: Selected DB does not exist: ' . mysql_error());
			throw new Exception(mysql_error());
		}
	}
	
	public static function  getInstance(){
		if(is_null(self::$instance)){
			$class = get_called_class();
			self::$instance = new $class();
		}
		return self::$instance;
	}

	public function query($str){
		$r =  mysql_query($str, $this->link);
		if(isDebug()){
			$err = mysql_error();
			if(!is_null($err)){
				$err = " ERR: " . $err;
			}
			dbg("Q: $str RES: " . var_export($r, true) . $err);	
		}
	
		if(!$r){
			return false;
		}
		return $r;
	}

	public function affectedRows(){
		return mysql_affected_rows($this->link);
	}

	function closeDBConnection() {
		mysql_close($this->link);
	}
}
