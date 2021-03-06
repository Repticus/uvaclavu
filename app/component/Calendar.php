<?php

namespace App;

use Nette\Application\UI\Control;

class Calendar extends Control {

	public $thisDate;
	public $activeDate;
	public $firstDate;
	public $lastDate;
	public $firstDay;
	public $nextEvent;
	public $lastDay;
	public $nextMonth;
	public $prevMonth;
	private $calendar;
	private $timeData;
	private $eventData;

	public function __construct($timeData, $eventData) {
		parent::__construct();
		$this->timeData = $timeData;
		$this->eventData = $eventData;
		$this->thisDate = strtotime("today");
		$this->activeDate = $this->thisDate;
		$this->nextEvent = $this->setNextEvent();
	}

	private function registerFilter() {
		$this->template->addFilter('czMonth', function($date) {
			$month = date('n', $date);
			static $months = array(1 => 'leden', 'únor', 'březen', 'duben', 'květen', 'červen', 'červenec', 'srpen', 'září', 'říjen', 'listopad', 'prosinec');
			return $months[$month];
		});
	}

	public function render() {
		$this->calendar = $this->setCalendar();
		$this->registerFilter();
		$this->template->thisDate = $this->thisDate;
		$this->template->firstDay = $this->firstDay;
		$this->template->lastDay = $this->lastDay;
		$this->template->nextMonth = $this->nextMonth;
		$this->template->prevMonth = $this->prevMonth;
		$this->template->days = array('Po', 'Út', 'St', 'Čt', 'Pá', 'So', 'Ne');
		$this->template->calendar = $this->calendar;
		$this->template->render(__DIR__ . '/Calendar.latte');
	}

	public function renderOpenTime() {
		$seasonId = $this->getSeasonId($this->firstDay);
		$this->template->days = array('Pondělí', 'Úterý', 'Středa', 'Čtvrtek', 'Pátek', 'Sobota', 'Neděle');
		$this->template->opentime = $this->timeData[$seasonId]['days'];
		$this->template->season = $this->getSeason($seasonId);
		$this->template->render(__DIR__ . '/OpenTime.latte');
	}

	public function setDate($date) {
		$this->activeDate = $date;
		$this->firstDay = strtotime('first day of this month', $date);
		$this->lastDay = strtotime('last day of this month', $date);
		$this->firstDate = strtotime(date('o-\\WW', $this->firstDay));
		$this->lastDate = strtotime('sunday', $this->lastDay);
		$this->nextMonth = strtotime('+1 day', $this->lastDay);
		$this->prevMonth = strtotime('-1 day', $this->firstDay);
	}

	private function setCalendar() {
		if (!$this->firstDate) {
			$this->setDate($this->thisDate);
		}
		$date = $this->firstDate;
		do {
			$days[] = $date;
			$date = strtotime("+1 day", $date);
		} while ($date <= $this->lastDate);
		return $days;
	}

	private function getSeasonId($date) {
		$month = (int) date("m", $date);
		foreach ($this->timeData as $id => $season) {
			if (in_array($month, $season['months'])) {
				return $id;
			}
		}
		return FALSE;
	}

	private function getSeason($seasonId) {
		$months = $this->timeData[$seasonId]['months'];
		$start = mktime(0, 0, 0, $months[0], 1, 2000);
		$end = mktime(0, 0, 0, end($months), 1, 2000);
		return array($start, $end);
	}

	private function getDefaultOpenTime($date) {
		$day = date("N", $date) - 1;
		$seasonId = $this->getSeasonId($date);
		$days = $this->timeData[$seasonId]['days'];
		if ($days[$day]) {
			return $days[$day];
		}
		return FALSE;
	}

	public function getOpenTime($date) {
		if (isset($this->eventData[$date]['open'])) {
			if ($this->eventData[$date]['open']) {
				return $this->eventData[$date]['open'];
			}
		} else {
			return $this->getDefaultOpenTime($date);
		}
		return FALSE;
	}

	public function getEvent($date) {
		if (isset($this->eventData[$date]['event'])) {
			return $this->eventData[$date]['event'];
		}
		return FALSE;
	}

	public function setNextEvent() {
		foreach ($this->eventData as $date => $data) {
			if (isset($data['event']) && $this->thisDate <= $date) {
				$data['event']['date'] = $date;
				return $data['event'];
			}
		}
		return FALSE;
	}

	public function getAttr($date) {
		$opentime = $this->getOpenTime($date);
		if ($date == $this->activeDate) {
			$class = "active ";
		} else {
			$class = NULL;
		}

		if (!$opentime) {
			return array('class' => $class . 'closed', 'title' => 'Zavřeno');
		}
		$event = $this->getEvent($date);
		if ($event) {
			return array('class' => $class . 'event', 'title' => $event[1]);
		}
		if ($this->isTimeChanged($date)) {
			return array('class' => $class . 'changed', 'title' => 'Změna otevírací doby');
		}
		return array('class' => $class, 'title' => 'Otevřeno');
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
