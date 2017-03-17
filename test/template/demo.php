<?php

require(__dir__.'/../template.php');

template::$fileDir = __dir__;
template::$cacheDir = __dir__.'/../cache';

$template = new template('test');

$template->assign('var', 'ejemplo de variable');
$template->assign('title', 'El titulo dentro de un import');
$template->assign('test', array('item1', 'item2', 'item3'));

$template->show();
