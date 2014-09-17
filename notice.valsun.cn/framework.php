<?php

class Core {
    private static $_instance = array();
    private static $classFile;

	private function __construct() {
		//-----------需要页面显示调试信息,	注释掉下面两行即可---
		set_error_handler(array("Core", 'appError'));
		set_exception_handler(array("Core", 'appException'));
        date_default_timezone_set("Asia/Shanghai");
		if(version_compare(PHP_VERSION, '5.4.0', '<')) {
			@set_magic_quotes_runtime(0);
			define('MAGIC_QUOTES_GPC', get_magic_quotes_gpc()?True:False);
		}

		//设置根目录
        if(!defined('WEB_PATH')) {
			define("WEB_PATH", "/data/web/notice.valsun.cn/");
		}

		//加载通用检测方法
		include_once	WEB_PATH."lib/common.php";

		//加载全局配置信息
		C(include_once  WEB_PATH.'conf/common.php');
		include_once	WEB_PATH."lib/auth.php";	//鉴权
		//Auth::setAccess(include WEB_PATH.'conf/access.php');
		include_once 	WEB_PATH."lib/log.php";
		include_once 	WEB_PATH."lib/page.php";
		include_once 	WEB_PATH."lib/authuser.class.php";
		include_once 	WEB_PATH."lib/opensys_functions.php";
		//加载数据接口层及所需支撑
		include_once	WEB_PATH."lib/service/http.php";	//网络接口
		include_once	WEB_PATH."lib/functions.php";
		include_once	WEB_PATH."lib/cache/cache.php";		//memcache

		if(C("DATAGATE") == "db") {
			$db	= C("DB_TYPE");
			include_once	WEB_PATH."lib/db/".$db.".php";	//db直连
			if($db	==	"mysql") {
				global	$dbConn;
				$db_config	=	C("DB_CONFIG");
				$dbConn		=	new mysql();
				$dbConn->connect($db_config["master1"][0],$db_config["master1"][1],$db_config["master1"][2]);
				$dbConn->select_db($db_config["master1"][4]);
				$dbConn->query('set names utf8');
			}
			if($db	==	"mongodb") {
				//.......
			}
		}
		//自动加载类
		spl_autoload_register(array('Core', 'autoload'));
	}

	//自动加载实现
	public function autoload($class) {
		//加载act
		if(strpos($class, "Act")) {
			$name		=	preg_replace("/Act/", "", $class);
			$fileName	=	lcfirst($name).".action.php";
			Core::getFile($fileName, WEB_PATH."action/");
			if(empty(Core::$classFile)) {
				throw new Exception("action class no exits");
			}
			include_once Core::$classFile;
		}

		//加载model
		if(strpos($class, "Model")) {
			$name		=	preg_replace("/Model/", "", $class);
			$fileName	=	lcfirst($name).".model.php";
			Core::getFile($fileName, WEB_PATH."model/");
			if(empty(Core::$classFile)) {
				throw new Exception("model class no exits");
			}
			include_once Core::$classFile;
		}

		//加载view
		if(strpos($class, "View")) {
			$name		=	preg_replace("/View/", "", $class);
			$fileName	=	lcfirst($name).".view.php";
			Core::getFile($fileName, WEB_PATH."view/");
			if(empty(Core::$classFile)) {
				throw new Exception("view class no exits");
			}
			include_once Core::$classFile;
		}
	}

	//获取相应文件
	public static function getFile($fileName, $path) {
		if ($handle = @opendir($path)) {
		    while(false !== ($file = @readdir($handle))) {
		        if(is_dir($path.$file) && ($file != "." && $file != "..")) {
		        	Core::getFile($fileName, $path.$file."/");
		        } else {
		       	 	if($file == $fileName) {
		        		Core::$classFile = $path.$file;
		        	}
		        }
		    }
		}
		@closedir($handle);
	}

	private function __clone() {}

	//单实例
    public static function getInstance() {
        if(!(self::$_instance instanceof self)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     +----------------------------------------------------------
     * 自定义异常处理
     * @access public
     * @param mixed $e 异常对象
     */
    static public function appException($e) {
		echo $e;
        //halt($e->__toString());
    }

    /**
     +----------------------------------------------------------
     * 自定义错误处理
     * @access public
     * @param int $errno 错误类型
     * @param string $errstr 错误信息
     * @param string $errfile 错误文件
     * @param int $errline 错误行数
     * @return void
     +----------------------------------------------------------
     */
    static public function appError($errno, $errstr, $errfile, $errline) {
		//echo $errstr;
		//exit;
    	switch ($errno) {
			case E_WARNING:
				$errorStr = "[$errno] $errstr ".basename($errfile)." 第 $errline 行.";
				if(C('LOG_RECORD')) Log::write($errorStr,Log::ERR);
				echo ($errorStr)."<br>"."<br>";
				break;
			case E_ERROR:
			case E_USER_ERROR:
				$errorStr = "[$errno] $errstr ".basename($errfile)." 第 $errline 行.";
				if(C('LOG_RECORD')) Log::write($errorStr,Log::ERR);
				echo($errorStr)."<br>"."<br>";
				break;
			case E_STRICT:
			case E_USER_WARNING:
			case E_USER_NOTICE:
			default:
				$errorStr = "[$errno] $errstr ".basename($errfile)." 第 $errline 行.";
				Log::record($errorStr,Log::NOTICE);
				break;
		}
    }
}