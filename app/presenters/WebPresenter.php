<?php

namespace App\Presenters;

use Nette;

class WebPresenter extends Nette\Application\UI\Presenter {

	public function actionTuristikaPribram() {
		$this->template->tourism = $this->context->parameters['tourism'];
	}
	public function actionJidelniListek() {
		$this->template->menu = $this->context->parameters['menu'];
	}

}
