# x_tiny_tpl
php模板
php版本：php5.3以上
使用说明请查看：doc.html



----------
PHP调用示例
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


