<?php

namespace App;

use Nette\Application\UI;

class Calendar extends UI\Control {

	private $opentime;
	private $calendar;

	public function __construct($opentime, $calendar) {
		parent::__construct();
		$this->opentime = $opentime;
		$this->calendar = $this->setCalendar($calendar);
	}

	public function render() {
		$template = $this->template;
		$this->template->calendar = $this->calendar;
		$template->render(__DIR__ . '/Calendar.latte');
	}

	public function renderOpenTime() {
		$template = $this->template;
		$this->template->opentime = $this->opentime;
		$template->render(__DIR__ . '/OpenTime.latte');
	}

	public function isTimeChanged($date) {
		$day = date("N", $date);
		$openTime = $this->calendar[$date]['time'];
		$defaultOpenTime = $this->getDefaultOpenTime($day);
		if ($openTime == $defaultOpenTime) {
			return FALSE;
		}
		return TRUE;
	}

	private function getDefaultOpenTime($day) {
		if (isset($this->opentime[$day]['open']) && isset($this->opentime[$day]['close'])) {
			return array(
				 'open' => $this->opentime[$day]['open'],
				 'close' => $this->opentime[$day]['close']
			);
		}
		return NULL;
	}

	public function getActiveOpenTime($date) {
		if ($this->calendar[$date]['time']) {
			return $this->calendar[$date]['time'];
		} else {
			return FALSE;
		}
	}

	public function getEvent($date) {
		if ($this->calendar[$date]['event']) {
			return $this->calendar[$date]['event'];
		} else {
			return FALSE;
		}
	}

	public function getAttr($date, $name) {
		if (!$this->calendar[$date]) {
			$attr = array('class' => 'c', 'title' => 'Zavřeno');
		} elseif ($this->calendar[$date]['event']) {
			$attr = array('class' => 'e', 'title' => $this->calendar[$date]['event']['name']);
		} elseif ($this->isTimeChanged($date)) {
			$attr = array('class' => 'd', 'title' => 'Změna otevírací doby');
		} else {
			$attr = array('class' => NULL, 'title' => 'Otevřeno');
		}
		if (isset($attr[$name])) {
			return $attr[$name];
		}
		return FALSE;
	}

	private function setCalendar($calendar) {
		$date = strtotime(date('o-\\WW'));
		$data = array();
		for ($week = 1; $week <= 5; $week++) {
			for ($day = 1; $day <= 7; $day++) {
				$data[$date] = $this->setDayData($date, $calendar);
				$date = strtotime("+1 day", $date);
			}
		}
		return $data;
	}

	private function setDayData($date, $calendar) {
		$day = date("N", $date);
		$open = $this->getOpenTime($day, $date, $calendar);
		$closed = $this->getCloseTime($day, $date, $calendar);
		$event = $this->setEvent($date, $calendar);
		if ($open && $closed) {
			return array(
				 'time' => array('open' => $open, 'close' => $closed),
				 'event' => $event
			);
		}
		return FALSE;
	}

	private function setEvent($date, $calendar) {
		if (isset($calendar[$date]['event']['name']) && isset($calendar[$date]['event']['start'])) {
			return $calendar[$date]['event'];
		}
		return FALSE;
	}

	private function getOpenTime($day, $date, $calendar) {
		if (isset($calendar[$date]['open']) && !$calendar[$date]['open']) {
			return FALSE;
		}
		$time = FALSE;
		if (isset($calendar[$date]['event']['name']) && isset($calendar[$date]['event']['start'])) {
			$time = $calendar[$date]['event']['start'];
		}
		if (isset($this->opentime[$day]['open'])) {
			$time = $this->opentime[$day]['open'];
		}
		if (isset($calendar[$date]['open']) && $calendar[$date]['open']) {
			$time = $calendar[$date]['open'];
		}
		if ($time) {
			return $time;
		}
		return FALSE;
	}

	private function getCloseTime($day, $date, $calendar) {
		if (isset($calendar[$date]['open']) && !$calendar[$date]['open']) {
			return FALSE;
		}
		$time = FALSE;
		if (isset($this->opentime[$day]['close'])) {
			$time = $this->opentime[$day]['close'];
		}
		if (isset($calendar[$date]['close'])) {
			$time = $calendar[$date]['close'];
		}
		if ($time) {
			return $time;
		}
		return FALSE;
	}

}
