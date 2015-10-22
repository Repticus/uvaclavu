<?php

namespace App\Presenters;

use Nette,
	 App\Calendar,
	 Nette\Mail\Message,
	 Nette\Mail\SendmailMailer,
	 Nette\Application\UI\Form;

class WebPresenter extends Nette\Application\UI\Presenter {

	public $date;
	public $openTime;

	public function actionKalendar($date) {
		$this->date = $date;
		$this->openTime = $this['calendar']->getOpenTime($date);
		$this->template->date = $date;
		$this->template->event = $this['calendar']->getEvent($date);
		$this->template->changed = $this['calendar']->isTimeChanged($date);
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

	protected function createComponentCalendar() {
		$opentime = $this->presenter->context->parameters['opentime'];
		$event = $this->convertDates("event");
		return new Calendar($opentime, $event, $this->date);
	}

	protected function createComponentQuestion() {
		$form = new Form();
		$form->addText('name', 'Jméno', NULL, 100)
				  ->setAttribute('placeholder', 'Jméno a přijmení')
				  ->setRequired('Zadejte jméno a přijmení.');
		$form->addText('email', 'Email', NULL, 60)
				  ->setAttribute('placeholder', 'Váš email')
				  ->setRequired('Zadejte email na který Vám odpovíme.')
				  ->addRule(Form::EMAIL, 'Email není správně vyplněn.');
		$form->addText('subject', 'Předmět', NULL, 100)
				  ->setAttribute('placeholder', 'Předmět');
		$form->addTextArea('message', 'Zpráva', NULL, NULL)
				  ->setAttribute('placeholder', 'Vaše Zpráva. Jméno a přijmení bude vloženo jako podpis.')
				  ->setRequired('Zadejte text zprávy.')
				  ->addRule(Form::MAX_LENGTH, 'Zprava je příliš dlouhá. Povoleno je maximálně 1000 znaků.', 1000);
		$form->addHidden('spamtest')
				  ->addRule(Form::EQUAL, 'Robot', array(NULL));
		$form->addSubmit('send', 'Odeslat');
		$form->onError[] = array($this, 'showFormError');
		$form->onSuccess[] = array($this, 'submitQuestion');
		return $form;
	}

	protected function createComponentReservation() {
		$form = new Form();
		$form->addText('name', 'Jméno', NULL, 100)
				  ->setAttribute('placeholder', 'Jméno a přijmení')
				  ->setRequired('Zadejte jméno a přijmení.');
		$form->addText('phone', 'Telefon', NULL, 100)
				  ->setAttribute('placeholder', 'Telefon');
		$form->addText('email', 'Email', NULL, 60)
				  ->setAttribute('placeholder', 'Váš email')
				  ->setRequired('Zadejte email na který Vám odpovíme.')
				  ->addRule(Form::EMAIL, 'Email není správně vyplněn.');
		$form->addSelect('time', 'Čas', $this->getHourList());
		$form->addSelect('hours', 'Počet hodin', $this->getHourCount());
		$form->addSelect('quantity', 'Počet osob', $this->getPersonList());
		$form->addCheckbox('voucher', 'Chci využít voucher');
		$form->addTextArea('message', 'Poznámka', NULL, NULL)
				  ->setAttribute('placeholder', 'Poznámka')
				  ->addRule(Form::MAX_LENGTH, 'Poznámka je příliš dlouhá. Povoleno je maximálně 1000 znaků.', 1000);
		$form->addHidden('spamtest')
				  ->addRule(Form::EQUAL, 'Robot', array(NULL));
		$form->addSubmit('send', 'Rezervovat');
		$form->addSubmit('storno', 'Storno')
							 ->setValidationScope(FALSE)
				  ->onClick[] = array($this, "stornoReservation");
		$form->onError[] = array($this, 'showFormError');
		$form->onSuccess[] = array($this, 'submitReservation');
		return $form;
	}

	public function submitQuestion($form) {
		$formData = $form->getValues();
		unset($formData['spamtest']);
		$clientMail = $formData['email'];
		$template = $this->createTemplate();
		$template->formData = $formData;
		$template->setFile(__DIR__ . "/../templates/Mail/question.latte");
		$this->sendMail($clientMail, $template);
		$flashMessage = "Děkujeme, Vaše zpráva byla úspěšně odeslána.";
		$this->flashMessage($flashMessage, 'success');
		$this->redirect('this');
	}

	public function submitReservation($form) {
		$formData = $form->getValues();
		unset($formData['spamtest']);
		$clientMail = $formData['email'];
		$template = $this->createTemplate();
		$template->formData = $formData;
		$template->setFile(__DIR__ . "/../templates/Mail/reservation.latte");
		$this->sendMail($clientMail, $template);
		$flashMessage = "Děkujeme, Vaše rezervace byla úspěšně odeslána.";
		$this->flashMessage($flashMessage, 'success');
		$this->redirect('this');
	}

	public function stornoReservation() {
		$this->redirect('this');
	}

	public function showFormError($form) {
		foreach ($form->errors as $error) {
			$this->flashMessage($error, 'error');
		}
	}

	private function getPersonList() {
		return array(
			 1 => "1 osoba",
			 2 => "2 osoby",
			 3 => "3 osoby",
			 4 => "4 osoby",
			 5 => "5 osob",
			 6 => "6 osob a více");
	}

	private function getHourList() {
		$hour = (int) $this->openTime[0];
		$close = (int) $this->openTime[1];
		$hourList = array();
		while ($hour < $close) {
			$hourList[$hour] = "od " . $hour . ":00 h";
			$hour++;
		}
		return $hourList;
	}

	private function getHourCount() {
		$hour = (int) $this->openTime[0];
		$close = (int) $this->openTime[1];
		$hourList = array();
		$count = 1;
		while ($hour < $close) {
			$hourList[$count] = "po dobu " . $count . " h";
			$count++;
			$hour++;
		}
		return $hourList;
	}

	private function convertDates($section) {
		$firstDay = strtotime(date('o-\\WW'));
		$data = $this->context->parameters[$section];
		foreach ($data as $date => $value) {
			$stamp = strtotime($date);
			if ($stamp >= $firstDay) {
				$data[$stamp] = $value;
			}
			unset($data[$date]);
		}
		return $data;
	}

	private function sendMail($clientMail, $template) {
		$ownerMail = $this->context->parameters['owner']['mail'];
		$ownerName = $this->context->parameters['owner']['name'];
		$mail = new Message;
		$mail->setFrom($clientMail)
				  ->addTo($ownerMail, $ownerName)
				  ->setHtmlBody($template);
		$mailer = new SendmailMailer;
		$mailer->send($mail);
	}

}
