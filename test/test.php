<?php
ini_set('error_reporting', E_ALL & ~E_NOTICE);
include('lib/XTinyTpl.class.php');
$tpl = new XTinyTpl(array(
	'rootPath' => dirname(__FILE__),
	'webPath' => '/test/xtpl',
	'tplPath' => 'tpl',
	'cachePath' => 'cache',
	'extName' => '.html',
	'resVer' => '',
	'cssToHead' => true,
	'regGlobal' => true,
	'mergeInclude' => false,
	'debug' => true
));



$list = array(
	array(
		'name' => '张三',
		'list' => array(
			array(
				'sub_name' => 'aaa1'
			),
			array(
				'sub_name' => 'aaa2'
			)
		)
	),
	array(
		'name' => '李四',
		'list' => array(
			array(
				'sub_name' => 'bbb1'
			),
			array(
				'sub_name' => 'bbb2'
			)
		)
	)
);
$tpl->setVar('a', 1);
$tpl->setVar($list);
$tpl->setVar(   
	array(  
		'var1'=>'123456',   
		'var2'=>'abcdefg',
	)
);


$tpl->out('test/test');

