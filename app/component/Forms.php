<?php

namespace App;

use Nette\Application\UI\Form;

class QuestionForm extends Form {

	public function __construct() {
		parent::__construct();
		$this->addText('name', 'Jméno', NULL, 100)
				  ->setAttribute('placeholder', 'Jméno a přijmení')
				  ->setRequired('Zadejte jméno a přijmení.');
		$this->addText('email', 'Email', NULL, 60)
				  ->setAttribute('placeholder', 'Váš email')
				  ->setRequired('Zadejte email na který Vám odpovíme.')
				  ->addRule(Form::EMAIL, 'Email není správně vyplněn.');
		$this->addText('subject', 'Předmět', NULL, 100)
				  ->setAttribute('placeholder', 'Předmět');
		$this->addTextArea('message', 'Zpráva', NULL, NULL)
				  ->setAttribute('placeholder', 'Vaše Zpráva. Jméno a přijmení bude vloženo jako podpis.')
				  ->setRequired('Zadejte text zprávy.')
				  ->addRule(Form::MAX_LENGTH, 'Zprava je příliš dlouhá. Povoleno je maximálně 1000 znaků.', 1000);
		$this->addHidden('spamtest')->setOmitted(TRUE)
				  ->addRule(Form::EQUAL, 'Robot', array(NULL));
		$this->addSubmit('send', 'Odeslat');
	}

}

class ReservationForm extends Form {

	public function __construct($openTime, $date) {
		parent::__construct();
		$this->addText('name', 'Jméno', NULL, 100)
				  ->setAttribute('placeholder', 'Jméno a přijmení')
				  ->setRequired('Zadejte jméno a přijmení.');
		$this->addText('phone', 'Telefon', NULL, 100)
				  ->setAttribute('placeholder', 'Telefon');
		$this->addText('email', 'Email', NULL, 60)
				  ->setAttribute('placeholder', 'Váš email')
				  ->setRequired('Zadejte email na který Vám odpovíme.')
				  ->addRule(Form::EMAIL, 'Email není správně vyplněn.');
		$this->addSelect('time', 'Čas', $this->getTimeList($openTime));
		$this->addSelect('hour', 'Počet hodin', $this->getHourList($openTime));
		$this->addSelect('quantity', 'Počet osob', $this->getPersonList());
		$this->addCheckbox('voucher', 'Chci využít voucher');
		$this->addTextArea('message', 'Poznámka', NULL, NULL)
				  ->setAttribute('placeholder', 'Poznámka')
				  ->addRule(Form::MAX_LENGTH, 'Poznámka je příliš dlouhá. Povoleno je maximálně 1000 znaků.', 1000);
		$this->addHidden('date')->setValue($date);
		$this->addHidden('spamtest')->setOmitted(TRUE)
				  ->addRule(Form::EQUAL, 'Robot', array(NULL));
		$this->addSubmit('send', 'Rezervovat');
	}

	private function getTimeList($openTime) {
		$hour = (int) $openTime[0];
		$close = (int) $openTime[1];
		$hourList = array();
		while ($hour < $close) {
			$hourList[$hour] = "od " . $hour . ":00 h";
			$hour++;
		}
		return $hourList;
	}

	private function getHourList($openTime) {
		$hour = (int) $openTime[0];
		$close = (int) $openTime[1];
		$hourList = array();
		$count = 1;
		while ($hour < $close) {
			$hourList[$count] = "po dobu " . $count . " h";
			$count++;
			$hour++;
		}
		return $hourList;
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

}
