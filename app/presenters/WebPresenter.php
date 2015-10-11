<?php

namespace App\Presenters;

use Nette,
	 App\BaseForm,
	 Nette\Mail\Message,
	 Nette\Mail\SendmailMailer;

class WebPresenter extends Nette\Application\UI\Presenter {

	public function actionKalendar($date = null) {
		$opentime = $this->context->parameters['opentime'];
		$calendar = $this->convertDates("calendar");
		$this->template->opentime = $opentime;
		$this->template->calendar = $this->setCalendarData($opentime, $calendar);
		$this->template->date = $date;
		$menu = $this->convertDates("lunch-menu");
		if (isset($menu[$date])) {
			$this->template->food = $menu[$date];
		}
		if (isset($calendar[$date]['event'])) {
			$this->template->event = $calendar[$date];
		}
		$this->template->openDay = $this->setOpenTime($date, $opentime, $calendar);
	}

	public function actionJidelniListek() {
		$this->template->menu = $this->context->parameters['main-menu'];
	}

	public function actionNapojovyListek() {
		$this->template->drink = $this->context->parameters['drink-menu'];
	}

	public function actionPoledniMenu() {
		$this->template->menu = $this->convertDates("lunch-menu");
	}

	public function actionTuristikaPribram() {
		$this->template->tourism = $this->context->parameters['tourism'];
	}

	private function setCalendarData($opentime, $calendar) {
		$date = strtotime('this week monday');
		$data = array();
		for ($week = 1; $week <= 5; $week++) {
			for ($day = 1; $day <= 7; $day++) {
				$dayOpen = $this->setOpenTime($date, $opentime, $calendar);
				if ($dayOpen['event']) {
					$data[$date]['title'] = $dayOpen['event'];
					$data[$date]['class'] = "e";
				} elseif ($dayOpen['changed']) {
					$data[$date]['title'] = "Změna otevírací doby";
					$data[$date]['class'] = "d";
				} elseif ($dayOpen) {
					$data[$date]['title'] = "Otevřeno";
					$data[$date]['class'] = NULL;
				} else {
					$data[$date]['title'] = "Zavřeno";
					$data[$date]['class'] = "c";
				}
				$date = strtotime("+1 day", $date);
			}
		}
		return $data;
	}

	private function setOpenTime($date, $opentime, $calendar) {
		$dayId = date("N", $date);
		$open = FALSE;
		$close = FALSE;
		$changed = FALSE;
		if (isset($opentime[$dayId]['open'])) {
			$open = $opentime[$dayId]['open'];
		}
		if (isset($calendar[$date]['start']) and ! $open) {
			$open = $calendar[$date]['start'];
			$changed = TRUE;
		}
		if (isset($calendar[$date]['open'])) {
			if ($calendar[$date]['open']) {
				$open = $calendar[$date]['open'];
				$changed = TRUE;
			} else {
				$open = FALSE;
			}
		}
		if (isset($opentime[$dayId]['close'])) {
			$close = $opentime[$dayId]['close'];
		}
		if (isset($calendar[$date]['close'])) {
			$close = $calendar[$date]['close'];
			$changed = TRUE;
		}
		if ($open and $close) {
			$time['open'] = $open;
			$time['close'] = $close;
			$time['changed'] = $changed;
			if (isset($calendar[$date]['event']) and isset($calendar[$date]['start'])) {
				$time['event'] = $calendar[$date]['event'];
			} else {
				$time['event'] = FALSE;
			}
		} else {
			$time = FALSE;
		}
		return $time;
	}

	private function convertDates($section) {
		$today = strtotime('today 00:00:00');
		$data = $this->context->parameters[$section];
		foreach ($data as $date => $value) {
			$stamp = strtotime($date);
			if ($stamp >= $today) {
				$data[$stamp] = $value;
			}
			unset($data[$date]);
		}
		return $data;
	}

	protected function createComponentSendQuestion() {
		$form = new BaseForm();
		$form->addText('name', 'Jméno', NULL, 100)
				  ->setAttribute('placeholder', 'Jméno a přijmení')
				  ->setRequired('Zadejte jméno a přijmení.');
		$form->addText('email', 'Email', NULL, 60)
				  ->setAttribute('placeholder', 'Váš email')
				  ->setRequired('Zadejte email na který Vám odpovíme.')
				  ->addRule(BaseForm::EMAIL, 'Email není správně vyplněn.');
		$form->addText('subject', 'Předmět', NULL, 100)
				  ->setAttribute('placeholder', 'Předmět');
		$form->addTextArea('message', 'Zpráva', NULL, NULL)
				  ->setAttribute('placeholder', 'Vaše Zpráva. Jméno a přijmení bude vloženo jako podpis.')
				  ->setRequired('Zadejte text zprávy.')
				  ->addRule(BaseForm::MAX_LENGTH, 'Zprava je příliš dlouhá. Povoleno je maximálně 1000 znaků.', 1000);
		$form->addHidden('spamtest')
				  ->addRule($form::EQUAL, 'Robot', array(NULL));
		$form->addSubmit('send', 'Odeslat');
		$form->onSuccess[] = array($this, 'submitSendQuestion');
		return $form;
	}

	public function submitSendQuestion($form) {
		$formData = $form->getValues();
		unset($formData['spamtest']);
		$this->sendQuestion($formData);
		$flashMessage = "Děkujeme, Vaše zpráva byla úspěšně odeslána.";
		$this->flashMessage($flashMessage, 'success');
		$this->redirect('this');
	}

	public function sendQuestion($formData) {
		$clientMail = $formData['email'];
		$ownerMail = $this->context->parameters['owner']['mail'];
		$ownerName = $this->context->parameters['owner']['name'];

		$template = $this->createTemplate();
		$template->formData = $formData;
		$template->setFile(__DIR__ . "/../templates/Mail/sendQuestion.latte");

		$mail = new Message;
		$mail->setFrom($clientMail)
				  ->addTo($ownerMail, $ownerName)
				  ->setHtmlBody($template);

		$mailer = new SendmailMailer;
		$mailer->send($mail);
	}

}
