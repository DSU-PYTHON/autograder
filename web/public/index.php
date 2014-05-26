<?php
/**
 * index.php
 *
 * The dispatcher engine. Requires PCRE > 7.9.
 *
 * @author	Xiangyu Bu (xybu92@live.com)
 */

$base = require(realpath('../app/lib/base.php'));

$base->config(realpath('../app/config/globals.ini'));
$base->config(realpath('../app/config/routes.ini'));

/*$base->set('ONERROR', function($base){
	echo \Template::instance()->render('error.html');
});
*/

$base->run();
