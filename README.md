# x_tiny_tpl
 [![BSD License](http://img.shields.io/badge/license-BSD-blue.svg)](https://github.com/Andyfoo/x_tiny_tpl/blob/master/LICENSE)

这是一个php模板class，之前我一直使用(Ease Template：[http://www.systn.com/data/et/1.html](http://www.systn.com/data/et/1.html "http://www.systn.com/data/et/1.html"))，后因不支持php7，然后就重写了。

XTinyTpl和Ease Template语法类似，功能相对少了一些，主要是自用，因现在不经常用php，所以维护不及时，请谅解。


感谢Ease Template作者，希望他能继续更新下去。


php版本：php5.3以上

**XTinyTpl文件小，运行速度快。支持模板变量、循环、判断、php函数、php代码段。**

使用说明请查看：doc.html


 
----------
**PHP调用示例**
    `

		<?php
		include('lib/XTinyTpl.class.php');
		$tpl = new XTinyTpl(array(
			'rootPath' => dirname(__FILE__),
			'webPath' => '/test/xtpl',
			'tplPath' => 'tpl',
			'cachePath' => 'cache',
			'extName' => '.html',
			'regGlobal' => true,
			'mergeInclude' => false
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
				

	`


