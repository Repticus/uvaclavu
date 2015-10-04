<?php

namespace App\Presenters;

use Nette,
	 App\BaseForm,
	 Nette\Mail\Message,
	 Nette\Mail\SendmailMailer;

class WebPresenter extends Nette\Application\UI\Presenter {

	public function actionKalendar($date = null) {
		$opentime = $this->context->parameters['opentime'];
		$this->template->opentime = $opentime;
		$this->template->calendar = $this->setCalendarData($opentime);
		$this->template->date = $date;
		$menu = $this->convertDates("lunch-menu");
		if (isset($menu[$date])) {
			$this->template->food = $menu[$date];
		}
		$event = $this->convertDates("calendar");
		if (isset($event[$date]['name'])) {
			$this->template->event = $event[$date];
		}
		$dayId = date("N", $date);
		if (isset($opentime[$dayId]['from'])) {
			$openDay['from'] = $opentime[$dayId]['from'];
			$openDay['end'] = $opentime[$dayId]['end'];
			$this->template->openDay = $openDay;
		}
	}

	public function actionTuristikaPribram() {
		$this->template->tourism = $this->context->parameters['tourism'];
	}

	public function actionJidelniListek() {
		$this->template->menu = $this->context->parameters['main-menu'];
		$this->template->drink = $this->context->parameters['drink-menu'];
	}

	public function actionPoledniMenu() {
		$this->template->menu = $this->convertDates("lunch-menu");
	}

	private function setCalendarData($opentime) {
		$date = strtotime('this week monday');
		$data = $this->convertDates("calendar");
		$calendar = array();
		for ($week = 1; $week <= 5; $week++) {
			$weekData = array();
			for ($day = 1; $day <= 7; $day++) {
				if (isset($data[$date])) {
					$weekData[$date] = $data[$date];
				} elseif (!isset($opentime[$day]['from'])) {
					$weekData[$date] = 0;
				} else {
					$weekData[$date] = null;
				}
				$date = strtotime("+1 day", $date);
			}
			$calendar[$week] = $weekData;
		}
		return $calendar;
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
