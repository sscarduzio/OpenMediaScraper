<?php

/**
 * common.log
 * 
 * AELogger.php (created: 23/lug/2011)
 * @author Simone Scarduzio
 * Cp1252
 * 
 * A wrapper around Log4php
 */
 

require_once 'log4php/Logger.php';

define("AE_LOG_LEVEL", LoggerLevel::ALL);
define("LOGFILE", "log/oms.log");

$logger = Logger::getRootLogger();
$logger->setLevel(LoggerLevel::toLevel(AE_LOG_LEVEL));

$appender = new LoggerAppenderRollingFile("MyAppender");
$appender->setFile(LOGFILE, true);
$appender->setMaxBackupIndex(10); 
$appender->setMaxFileSize("100MB");
$appenderlayout = new LoggerLayoutPattern();
//$pattern = '%d{Y.m.d H:i:s} [%p] %c: %m (at %F line %L)%n';
$pattern = '%d{Y.m.d H:i:s.u} [%p] %m %n';
$appenderlayout->setConversionPattern($pattern);
$appender->setLayout($appenderlayout);
$appender->activateOptions();

$logger->removeAllAppenders();
$logger->addAppender($appender);

$logger->info(" *** Engine initializing ***");

function err($str){ 
	$logger = Logger::getRootLogger();
	$logger->error("RID:" . RID . " " . $str);
}

function wrn($str){ 
	$logger = Logger::getRootLogger();
	$logger->warn("RID:" . RID . " " . $str);
}

function dbg($str){
	$logger = Logger::getRootLogger();
	$logger->debug("RID:" . RID . " " . $str);
}

function inf($str){
	$logger = Logger::getRootLogger();
	$logger->info("RID:" . RID . " " . $str);
}

function trc($str){
	$logger = Logger::getRootLogger();
	$logger->trace("RID:" . RID . " " . $str);
}

function isDebug(){
	$logger = Logger::getRootLogger();
	return $logger->isDebugEnabled();
}

// /**
//  * CDR Logger
//  */
// $cdrLogger = Logger::getLogger("CDR");
// $appender = new LoggerAppenderRollingFile("CDRAppender");
// $appender->setFile(LOGFILE_CDR, true);
// $appender->setMaxBackupIndex(10);
// $appender->setMaxFileSize("100MB");
// $appenderlayout = new LoggerLayoutPattern();
// $pattern = '%d{Y.m.d H:i:s.u};%m %n';
// $appenderlayout->setConversionPattern($pattern);
// $appender->setLayout($appenderlayout);
// $appender->activateOptions();

// $cdrLogger->removeAllAppenders();
// $cdrLogger->addAppender($appender);

// function writeCDR($str){
// 	global $cdrLogger;
// 	$cdrLogger->info($str);
// }