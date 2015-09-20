<?php

namespace App\Presenters;

use Nette,
	 App\BaseForm,
	 Nette\Mail\Message,
	 Nette\Mail\SendmailMailer;

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
