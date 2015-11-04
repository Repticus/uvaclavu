<?php

namespace App;

use Nette\Application\UI\Control;

class Calendar extends Control {

	private $firstDate;
	private $requestDate;
	private $event;
	private $opentime;
	private $calendar;

	public function __construct($opentime, $event, $date = NULL) {
		parent::__construct();
		$this->firstDate = strtotime(date('o-\\WW'));
		$this->requestDate = $this->setDate($date);
		$this->event = $event;
		$this->opentime = $opentime;
		$this->calendar = $this->setCalendar();
	}

	public function render() {
		$template = $this->template;
		$this->template->days = array('Po', 'Út', 'St', 'Čt', 'Pá', 'So', 'Ne');
		$this->template->calendar = $this->calendar;
		$template->render(__DIR__ . '/Calendar.latte');
	}

	public function renderOpenTime() {
		$seasonId = $this->getSeasonId($this->requestDate);
		$template = $this->template;
		$this->template->days = array('Pondělí', 'Úterý', 'Středa', 'Čtvrtek', 'Pátek', 'Sobota', 'Neděle');
		$this->template->opentime = $this->opentime[$seasonId]['days'];
		$this->template->season = $this->getSeason($seasonId);
		$template->render(__DIR__ . '/OpenTime.latte');
	}

	private function setCalendar() {
		$date = $this->firstDate;
		for ($week = 1; $week <= 5; $week++) {
			for ($day = 1; $day <= 7; $day++) {
				$days[] = $date;
				$date = strtotime("+1 day", $date);
			}
		}
		return $days;
	}

	private function setDate($date) {
		if ((int) $date < $this->firstDate) {
			return strtotime("today");
		} else {
			return (int) $date;
		}
	}

	private function getSeasonId($date) {
		$month = (int) date("m", $date);
		foreach ($this->opentime as $id => $season) {
			if (in_array($month, $season['months'])) {
				return $id;
			}
		}
		return FALSE;
	}

	private function getSeason($seasonId) {
		$months = $this->opentime[$seasonId]['months'];
		$start = strftime('%B', mktime(0, 0, 0, $months[0], 1, 2000));
		$end = strftime('%B', mktime(0, 0, 0, end($months), 1, 2000));
		return array($start, $end);
	}

	private function getDefaultOpenTime($date) {
		$day = date("N", $date) - 1;
		$seasonId = $this->getSeasonId($date);
		$days = $this->opentime[$seasonId]['days'];
		if ($days[$day]) {
			return $days[$day];
		}
		return FALSE;
	}

	public function getOpenTime($date) {
		if (isset($this->event[$date]['open'])) {
			if ($this->event[$date]['open']) {
				return $this->event[$date]['open'];
			}
		} else {
			return $this->getDefaultOpenTime($date);
		}
		return FALSE;
	}

	public function getEvent($date) {
		if (isset($this->event[$date]['event'])) {
			return $this->event[$date]['event'];
		}
		return FALSE;
	}

	public function getAttr($date) {
		$opentime = $this->getOpenTime($date);
		if (!$opentime) {
			return array('class' => 'closed', 'title' => 'Zavřeno');
		}
		$event = $this->getEvent($date);
		if ($event) {
			return array('class' => 'event', 'title' => $event[1]);
		}
		if ($this->isTimeChanged($date)) {
			return array('class' => 'changed', 'title' => 'Změna otevírací doby');
		}
		return array('class' => NULL, 'title' => 'Otevřeno');
	}

	public function isTimeChanged($date) {
		$openTime = $this->getOpenTime($date);
		$defaultOpenTime = $this->getDefaultOpenTime($date);
		if ($openTime <> $defaultOpenTime) {
			return TRUE;
		}
		return FALSE;
	}

}
