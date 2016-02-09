<?php

namespace App;

use Nette,
	 Nette\Application\Routers\RouteList,
	 Nette\Application\Routers\Route;

class RouterFactory {

	/**
	 * @return Nette\Application\IRouter
	 */
	public static function createRouter() {
		$router = new RouteList();
		$router[] = new Route('poledni-menu[/<week>]', 'Web:poledniMenu');
		$router[] = new Route('rezervace[/<date>]', 'Web:rezervace');
		$router[] = new Route('<action>', 'Web:restaurace');
		return $router;
	}

}
