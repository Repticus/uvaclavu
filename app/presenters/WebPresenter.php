<?php

namespace App\Presenters;

use Nette,
	 App\Calendar,
	 App\QuestionForm,
	 App\ReservationForm,
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
		$form = new QuestionForm();
		$form->onError[] = array($this, 'showFormError');
		$form->onSuccess[] = array($this, 'submitQuestion');
		return $form;
	}

	protected function createComponentReservation() {
		$form = new ReservationForm($this->openTime, $this->date);
		$form->addSubmit('storno', 'Storno')->setValidationScope(FALSE)
				  ->onClick[] = array($this, "stornoReservation");
		$form->onError[] = array($this, 'showFormError');
		$form->onSuccess[] = array($this, 'submitReservation');
		return $form;
	}

	public function submitQuestion($form) {
		$formData = $form->getValues();
		$clientMail = $formData['email'];
		$template = $this->template;
		$template->formData = $formData;
		$template->setFile(__DIR__ . "/../templates/Mail/question.latte");
		$this->sendMail($clientMail, $template);
		$flashMessage = "Děkujeme, Vaše zpráva byla úspěšně odeslána.";
		$this->flashMessage($flashMessage, 'success');
		$this->redirect('this');
	}

	public function submitReservation($form) {
		$formData = $form->getValues();
		$clientMail = $formData['email'];
		$template = $this->template;
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
