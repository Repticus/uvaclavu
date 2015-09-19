<?php

namespace App\Presenters;

use Nette;

class WebPresenter extends Nette\Application\UI\Presenter {

	public function actionTuristikaPribram() {
		$this->template->tourism = $this->context->parameters['tourism'];
	}

	public function actionJidelniListek() {
		$this->template->menu = $this->context->parameters['main-menu'];
	}

	public function actionPoledniMenu() {
		$menu = $this->context->parameters['lunch-menu'];
		$today = strtotime('today 00:00:00');
		foreach ($menu as $date => $food) {
			$day = strtotime($date);
			if ($day < $today) {
				unset($menu[$date]);
			}
		}
		$this->template->menu = $menu;
	}

}
