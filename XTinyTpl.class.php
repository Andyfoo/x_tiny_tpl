<?php

/* 
 * 
 * Version:	XTinyTpl_1.0
 * File:	XTinyTpl.class.php
 * PHP:		>=php 5.3
 * Author:	Andyfoo
 * Site:	http://www.andyfoo.com   http://www.pslib.com
 * Email:	andyfoo@163.com
 * QQ:		93638025
 */
define("XTTPL",TRUE);
class XTinyTpl{
	
	private $regGlobal;//是否注册全局变量$GLOBALS
	private $mergeInclude;//是否合并引用文件

	private $rootPath = '';//文件根路径
	private $webPath = '';//web根路径
	private $extName = '';//模板扩展名
	
	private $tplPath = '';//模板相对路径
	private $cachePath = '';//缓存相对路径
	
	private $pathReplace = array();//动态替换的路径列表

	private $resVer = '';//给页面资源(css/js/img)增加版本号:如果值为rand则生成动态时间戳
	private $cssToHead = false;//是否将页面所有css提取到head标签中

	private $mainTplName = '';//主模板文件名
	private $vars = array();//变量数组
	//保存引用文件列表
	private $incFileArr = array();
	private $incFileObj = array();
	private $cssList = array();


	
	private $debug;

	private $phpHead = "<?php if(!defined('XTTPL')){die('Access Denied');}; ?>";

	/**
	 * 实例化参数
	 * 
	 * @param $opt = array()
	 * @return
	 */
	public function __construct($opt = array()) {
		$this->cfg($opt);
	}
	/**
	 * 参数配置
	 * @return
	 */
	public function cfg($opt = array()) {
		$this->rootPath = isset($opt['rootPath']) && $opt['rootPath'] ? $this->cleanPath($opt['rootPath']) : '';
		$this->webPath = isset($opt['webPath']) && $opt['webPath'] ? $this->cleanPath($opt['webPath']) : '';
		$this->extName = isset($opt['extName']) && $opt['extName'] ? $opt['extName'] : '.html';
		$this->tplPath = isset($opt['tplPath']) && $opt['tplPath'] ? $this->cleanPath($opt['tplPath']) : '';
		$this->cachePath = isset($opt['cachePath']) && $opt['cachePath'] ? $this->cleanPath($opt['cachePath']) : '';
		if(isset($opt['pathReplace']) && is_array($opt['pathReplace'])){
			$this->pathReplace = $opt['pathReplace'];
		}else if(isset($opt['pathReplace']) && $opt['pathReplace'] && !is_array($opt['pathReplace'])){
			$this->pathReplace = array($opt['pathReplace']);
		}else{
			$this->pathReplace = array('images');
		}

		$this->resVer = isset($opt['resVer']) && $opt['resVer'] ? $this->cleanPath($opt['resVer']) : '';
		
		$this->regGlobal = isset($opt['regGlobal']) ? $opt['regGlobal'] : true;
		$this->mergeInclude = isset($opt['mergeInclude']) ? $opt['mergeInclude'] : false;
		$this->cssToHead = isset($opt['cssToHead']) ? $opt['cssToHead'] : false;
		if($this->cssToHead){
			$this->mergeInclude = true;
		}

		$this->debug = isset($opt['debug']) ? $opt['debug'] : false;
	}

	/**
	 * 添加变量
	 * 
	 * @param $name = string或array()
	 * @param $value = object
	 * @return
	 */
	public function setVar($name, $value = '') {
		if (is_array($name)){
			$this->vars = array_merge($this->vars, $name);
		}else{
			$this->vars[$name] = $value;
		}
	}
	/**
	 * 添加变量，同setVar
	 */
	public function assign($name, $value = ''){
		$this->setVar($name, $value);
	}

	/**
	 * 格式化路径
	 */
	private function cleanPath($path){
		return preg_replace(array('/[\\\\\/]+/is', '/[\.]+\//is'), array('/', '') , $path);
	}
	/**
	 * 返回相对路径，如：/abc/123/../aaa.png 返回 /abc/aaa.png
	 */
	private function normalizePath($path){
		$parts = array();
		$path = str_replace('\\', '/', $path);
		$path = preg_replace('/\/+/', '/', $path);
		$segments = explode('/', $path);
		$test = '';
		foreach($segments as $segment){
			if($segment != '.'){
				$test = array_pop($parts);
				if(is_null($test))
					$parts[] = $segment;
				else if($segment == '..'){
					if($test == '..')
					$parts[] = $test;

					if($test == '..' || $test == '')
					$parts[] = $segment;
				}
				else{
					$parts[] = $test;
					$parts[] = $segment;
				}
			}
		}
		return implode('/', $parts);
	}
	/**
	 * 解析模板
	 */
	private function parse($tplname, $tplFilename, $str){
		$self = $this;
		//替换有web-path标签的路径，增加上web根目录
		$str = preg_replace_callback(array(
			'/web-path.+?(href|src)\s*(=)\s*([\'"]+)(.+?)([\'"]+)/i'
		), function ($matches) use ($self){
			return $matches[1].$matches[2].$matches[3].$self->cleanPath($self->webPath.'/'.$matches[4]).$matches[5];
		}, $str);

		foreach($this->pathReplace AS $rpath){
			$str = $this->replacePath(dirname($tplFilename), $str, $rpath);
		}
		if($this->cssToHead){
			$matches = array();
			preg_match_all('/(<[^>]*href\s*=\s*[\'"]+[^\'"]+\.css[^\'"]*[\'"]+[^>]*>)/is',$str, $matches, PREG_SET_ORDER);
			foreach($matches AS $v){
				if(!in_array($v[1], $this->cssList)){
					$this->cssList[] = $v[1];
				}
			}
			$str = preg_replace('/<[^>]*href\s*=\s*[\'"]+[^\'"]+\.css[^\'"]*[\'"]+[^>]*>/is', '' , $str);
		}
		//$str = preg_replace_callback('/(<!--\s*#include\s+file\s*=\s*"|\{inc\:|<!--\s*inc:)([^"\'}\s]+)("\s*-->|}|\s*-->)/i', 'self::parseInclude', $str);
		/*
		$str = preg_replace_callback(array(
			'/<!--\s*#include\s+file\s*=\s*[\'"]+(.+?)[\'"]+\s*-->/i',
			'/\{inc\:(.+?)}/i',
			'/<!--\s*inc:(.+?)\s*-->/i'
		), array($this, 'parseInclude'), $str);
		*/
		if (version_compare(PHP_VERSION, '5.3.0', '>=')) {
			$str = preg_replace_callback(array(
				'/<!--\s*#include\s+file\s*=\s*[\'"]+(.+?)[\'"]+\s*-->/i',
				'/\{inc\:(.+?)}/i',
				'/\{include\s+file\s*=\s*"(.+?)"}/i',
				'/<!--\s*inc:(.+?)\s*-->/i'
			), function ($matches) use ($tplname, $self){
				return $self->parseInclude($matches, $tplname);
			}, $str);
		}else{
			die('Supports only PHP 5.3 or above');
		}


		$arr = array(
			'/<!--\s*\{(.+?)}\s*-->/is' => '<?php $1 ?>',
			'/\{run\:}(.+?)\{\/run}/is' => '<?php $1 ?>',
			'/<!--\s*run\:\s*-->(.+?)<!--\s*\/run\s*-->/is' => '<?php $1 ?>',


			'/\$\{([\w\[\]\'"\._]+)}/is' => '<?php echo $$1; ?>',
			'/\{\$(.+?)}/is' => '<?php echo \$$1; ?>',
			'/\$\{(.+?)}/is' => '<?php echo $1; ?>',

			'/<!--\s*end\s*-->/is' => '<?php } ?>',
			
			'/<!--\s*(\$[^\s]+)\s+AS\s+(\$[^\s]+)\s+=>\s+(\$[^\s]+)\s*-->/is' => '<?php if(isset($1)&&is_array($1))foreach($1 AS $2 => $3){ ?>',
			'/<!--\s*(\$[^\s]+)\s+AS\s+(\$[^\s]+)\s*-->/is' => '<?php if(isset($1)&&is_array($1))foreach($1 AS $2){ ?>',
			'/<!--\s*while\s*:\s*(.+?)\s*-->/is' => '<?php while($1){ ?>',
			
			'/<!--\s*else\s*-->/is' => '<?php }else{ ?>',
			'/<!--\s*else\s*if\s*\[(.+?)]\s*-->/is' => '<?php }else if($1){ ?>',
			'/<!--\s*if\s*\[(.+?)]\s*-->/is' => '<?php if($1){ ?>'
		);
		
		$str = preg_replace(array_keys($arr), array_values($arr) , $str);


		//echo $str;
		return $str;
	} 

	/**
	 * 处理模板引用
	 */
	public function parseInclude($matches, $parent_tplname){
		$tplname = trim($matches[1]);
		if(substr($tplname, 0, 1) != '/'){
			$tplname = dirname($parent_tplname) . '/' . $tplname;
		}
		$tplname = $this->normalizePath($tplname);
		
		$tplname = $this->cleanPath($tplname);
		$tplname = $this->fixFilename($tplname);
		
		

		$this->parseFile($tplname);
		$cacheFilename = $this->getCacheFilename($tplname);

		if(!in_array($tplname, $this->incFileArr)){
			$this->incFileArr[] = $tplname;
			$this->incFileObj[] = array(
				'tplname' => $tplname,
				'tplFilename' => $this->getTplFilename($tplname),
				'cacheFilename' => $cacheFilename,
			);
		}
		return $this->mergeInclude ? file_get_contents($this->rootPath .'/'. $cacheFilename) : "<?php if(is_file('{$cacheFilename}'))include('{$cacheFilename}'); ?>";
	}
	/**
	 * 替换路径，通过pathReplace配置，一般用于替换图片路径 
	 */
	private function replacePath($dirname, $str, $rpath){
		$objpath = '/'.$this->cleanPath($dirname.'/'.$rpath.'/');
		$webPath = $this->cleanPath($this->webPath);
		if(strpos($dirname, '/') === false){
			return str_replace($rpath.'/', $this->cleanPath($webPath.$objpath), $str);
		}
		if(is_dir($this->rootPath . $objpath)){
			return str_replace($rpath.'/', $this->cleanPath($webPath.$objpath), $str);
		}

		return $this->replacePath(substr($dirname, 0, strrpos($dirname, '/')), $str, $rpath);
	}
	
	/**
	 * 给页面资源(css/js/img)增加版本号:如果值为rand则生成动态时间戳
	 */
	private function addResVer($str){
		return preg_replace('/(src|href)\s*=\s*([\'"]+)(.+?\.)(css|js|png|jpg|jpeg|gif)([\'"]+)/is', '$1=$2$3$4?_t='.($this->resVer == 'rand' ? microtime(true) : $this->resVer).'$5' , $str);
	}
	
	/**
	 * 添加文件扩展名
	 */
	private function fixFilename($tplname){
		return substr($tplname, 0-strlen($this->extName)) == $this->extName ? $tplname : $tplname . $this->extName;
	}
	/**
	 * 解析模板并生成缓存文件
	 */
	private function parseFile($tplname, $is_main=false){
		$tplname = $this->cleanPath($tplname);
		$tplname = $this->fixFilename($tplname);
		$tplFilename = $this->getTplFilename($tplname);
		$tplFilename_full = $this->rootPath .'/'. $tplFilename;
		$cacheFilename = $this->getCacheFilename($tplname);
		$cacheFilenameFull = $this->rootPath .'/'. $cacheFilename;

		if(!is_file($tplFilename_full)){
			if($this->debug){
				echo "tpl file no exists:" . ($is_main ? "{$tplFilename_full}" : "{$this->mainTplName}->{$tplFilename_full}\n<pre>\n");
				die;
			}else{
				die("tpl file no exists:{$tplname}");
			}
			
		}
		//检查引用文件时间
		if($is_main){
			$this->mainTplName = $tplname;
		}
		$cacheCfgFilename = $this->rootPath .'/'. $this->get_cacheCfgFilename($this->mainTplName);
		if($is_main && !$this->debug){
			$incFileUpdate = false;
			if(is_file($cacheCfgFilename)){//主文件下引用的文件其中有一个更新则全部更新
				include($cacheCfgFilename);
				if(is_array($inc_files))foreach($inc_files AS $v){
					$inc_tplFilename_full = $this->rootPath .'/'. $v['tplFilename'];
					$inccacheFilenameFull = $this->rootPath .'/'. $v['cacheFilename'];

					if (!is_file($inccacheFilenameFull) || (is_file($inccacheFilenameFull) && filemtime($inc_tplFilename_full)>filemtime($inccacheFilenameFull))){
						$incFileUpdate = true;
					}
				}
			}
			//判断文件是否需要更新
			if (!$incFileUpdate && is_file($cacheFilenameFull) && filemtime($this->rootPath .'/'.$cacheFilename)>filemtime($tplFilename_full)){
				return;
			}
		}

		$str = file_get_contents($tplFilename_full);
		$str = $this->parse($tplname, $tplFilename, $str);
		$dirname = dirname($cacheFilename);
		if(!is_dir($this->rootPath .'/'. $dirname))mkdir($this->rootPath .'/'. $dirname, 0777, true);
		if($is_main){//生成主文件配置
			file_put_contents($cacheCfgFilename, $this->phpHead . "<?php \n\$inc_files=".var_export($this->incFileObj, true).";\n?>");
			if($this->cssToHead && count($this->cssList)>0){
				$str = preg_replace('/<\/head>/is', implode('', $this->cssList)."\n</head>" , $str);
			}
		}
		file_put_contents($cacheFilenameFull, $this->phpHead . $str);

	}
	/**
	 * 获取模板文件名全路径 
	 */
	private function getTplFilename($tplname){
		return $this->cleanPath($this->tplPath . '/\/'. $tplname);
	}
	/**
	 * 获取缓存文件名全路径 
	 */
	private function getCacheFilename($tplname){
		return $this->cleanPath($this->cachePath . '/\/'. $tplname.'.php');
	}
	/**
	 * 获取缓存文件引用文件配置名全路径 
	 */
	private function get_cacheCfgFilename($tplname){
		return $this->cleanPath($this->cachePath . '/\/'. $tplname.'.cfg.php');
	}
	/**
	 * 加载缓存文件
	 */
	private function incCacheFile($tplname){
		if($this->regGlobal)extract($GLOBALS);
		extract($this->vars);
		$tplname = $this->fixFilename($tplname);
		$cacheFilename = $this->rootPath .'/'. $this->getCacheFilename($tplname);
		if(is_file($cacheFilename)){
			include($cacheFilename);
		}else{
			die("tpl cache file no exists:{$cacheFilename}");
		}
	}
	/**
	 * 输出模板，设置$return=true则不输入返回结果
	 */
	public function out($tplname, $return = false){
		if($return || $this->resVer){
			ob_start();
			ob_implicit_flush(0);
			$this->render($tplname);
			$_content = ob_get_contents();
			ob_end_clean();
			$content = $_content;
			unset($_content);//释放内存
			if($this->resVer){
				$content = $this->addResVer($content);
				if(!$return){
					echo $content;
					return;
				}
			}
			return $content;
		}else{
			$this->render($tplname);
		}
	}
	/**
	 * 输出模板
	 */
	public function render($tplname){
		$this->parseFile($tplname, true);
		$this->incCacheFile($tplname);
	}
}
