<?php

namespace App\Presenters;

use Nette,
	 App\Calendar,
	 App\QuestionForm,
	 App\ReservationForm,
	 Nette\Mail\Message,
	 Nette\Mail\SendmailMailer;

class WebPresenter extends Nette\Application\UI\Presenter {

	public $date;
	public $openTime;

	public function actionRestaurace() {
		$calendar = $this['calendar'];
		$this->template->event = $calendar->nextEvent;
	}

	public function actionRezervace($date) {
		$calendar = $this['calendar'];
		$calendar->setDate($date);
		$this->date = $date;
		$this->openTime = $calendar->getOpenTime($date);
		$this->template->date = $date;
		$this->template->opentime = $this->openTime;
		$this->template->changed = $calendar->isTimeChanged($date);
		$this->template->event = $calendar->getEvent($date);
	}

	public function actionNapojovyListek() {
		$this->template->drink = $this->context->parameters['drink-menu'];
	}

	public function actionPoledniMenu($week = NULL) {
		$data = $this->convertDates("lunch-menu");
		$week = (int) $week;
		if (!$week) {
			$week = (int) date('W');
		}
		$setWeek = array();
		foreach ($data as $key => $date) {
			$weekId = (int) date('W', $key);
			if ($weekId == $week) {
				$setWeek[$weekId] = TRUE;
			} else {
				$setWeek[$weekId] = FALSE;
				unset($data[$key]);
			}
		}
		$this->template->menu = $data;
		$this->template->week = $setWeek;
	}

	public function actionTuristikaPribram() {
		$this->template->tourism = $this->context->parameters['tourism'];
	}

	protected function createComponentCalendar() {
		$timeData = $this->presenter->context->parameters['opentime'];
		$eventData = $this->convertDates("event");
		return new Calendar($timeData, $eventData);
	}

	protected function createComponentQuestion() {
		$form = new QuestionForm();
		$form->onError[] = array($this, 'showFormError');
		$form->onSuccess[] = array($this, 'submitQuestion');
		return $form;
	}

	public function submitQuestion($form) {
		$formData = $form->getValues();
		$clientMail = $formData['email'];
		$template = $this->createTemplate();
		$template->setFile(__DIR__ . "/../templates/Mail/question.latte");
		$template->formData = $formData;
		$this->sendMail($clientMail, $template);
		$flashMessage = "Děkujeme, Vaše zpráva byla úspěšně odeslána.";
		$this->flashMessage($flashMessage, 'success');
		$this->redirect('this');
	}

	protected function createComponentReservation() {
		$form = new ReservationForm($this->openTime, $this->date);
		$form->addSubmit('reset', 'Reset')->setValidationScope(FALSE)
				  ->onClick[] = array($this, "resetReservation");
		$form->onError[] = array($this, 'showFormError');
		$form->onSuccess[] = array($this, 'submitReservation');
		return $form;
	}

	public function submitReservation($form) {
		$formData = $form->getValues();
		$clientMail = $formData['email'];
		$template = $this->createTemplate();
		$template->setFile(__DIR__ . "/../templates/Mail/reservation.latte");
		$template->formData = $formData;
		$this->sendMail($clientMail, $template);
		$flashMessage = "Děkujeme, Vaše rezervace byla úspěšně odeslána.";
		$this->flashMessage($flashMessage, 'success');
		$this->redirect('this');
	}

	public function resetReservation() {
		$this->redirect('this');
	}

	public function showFormError($form) {
		foreach ($form->errors as $error) {
			$this->flashMessage($error, 'error');
		}
	}

	private function convertDates($section) {
		$today = strtotime(date("d.m.Y"));
		$data = (array) $this->context->parameters[$section];
		foreach ($data as $date => $value) {
			$stamp = strtotime($date);
			if ($stamp >= $today) {
				$data[$stamp] = $value;
			}
			unset($data[$date]);
		}
		ksort($data);
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
