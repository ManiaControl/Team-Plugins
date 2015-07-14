<?php

namespace steeffeen;

use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\TimerListener;
use ManiaControl\ManiaControl;
use ManiaControl\Players\PlayerManager;
use ManiaControl\Plugins\Plugin;

/**
 * Auto Shutdown Plugin
 *
 * @author  steeffeen
 * @version 0.2
 */
class AutoShutdownPlugin implements Plugin, CallbackListener, TimerListener {
	/*
	 * Constants
	 */
	const ID                               = 51;
	const VERSION                          = 0.2;
	const NAME                             = 'Auto Shutdown Plugin';
	const AUTHOR                           = 'steeffeen';
	const SETTING_SHUTDOWN_INTERVAL_ENABLE = 'Enable Interval Shutdown';
	const SETTING_SHUTDOWN_INTERVAL        = 'Shutdown Interval in Days';
	const SETTING_TIMED_SHUTDOWN           = ' Enable timed Shutdown';
	const SETTING_TIMED_SHUTDOWN_DAY       = ' Weekday of timed Shutdown';
	const SETTING_TIMED_SHUTDOWN_HOUR      = ' Hour of timed Shutdown';
	
	/**
	 * Private Properties
	 */
	/** @var ManiaControl $maniaControl */
	private $maniaControl      = null;
	private $shutdownRequested = false;

	/**
	 * @see \ManiaControl\Plugins\Plugin::getId()
	 */
	public static function getId() {
		return self::ID;
	}

	/**
	 * @see \ManiaControl\Plugins\Plugin::getName()
	 */
	public static function getName() {
		return self::NAME;
	}

	/**
	 * @see \ManiaControl\Plugins\Plugin::getVersion()
	 */
	public static function getVersion() {
		return self::VERSION;
	}

	/**
	 * @see \ManiaControl\Plugins\Plugin::getAuthor()
	 */
	public static function getAuthor() {
		return self::AUTHOR;
	}

	/**
	 * @see \ManiaControl\Plugins\Plugin::getDescription()
	 */
	public static function getDescription() {
		return "Plugin shutting down your Server when it's empty to restart it every few days. You need an automatic Restart Mechanism to use this Plugin.";
	}

	/**
	 * @see \ManiaControl\Plugins\Plugin::prepare()
	 */
	public static function prepare(ManiaControl $maniaControl) {
	}

	/**
	 * @see \ManiaControl\Plugins\Plugin::load()
	 */
	public function load(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;

		// Init settings
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_SHUTDOWN_INTERVAL_ENABLE, true);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_SHUTDOWN_INTERVAL, 7);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_TIMED_SHUTDOWN, false);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_TIMED_SHUTDOWN_DAY, array("Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"));
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_TIMED_SHUTDOWN_HOUR, array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23));

		// Register callbacks
		$this->maniaControl->getCallbackManager()->registerCallbackListener(PlayerManager::CB_SERVER_EMPTY, $this, 'handleServerEmptyCallback');
		$this->maniaControl->getTimerManager()->registerTimerListening($this, 'handleHourlyTimerCallback', 3600 * 1000);
	}

	/**
	 * @see \ManiaControl\Plugins\Plugin::unload()
	 */
	public function unload() {
	}

	/**
	 * Handle Server Empty Callback
	 */
	public function handleServerEmptyCallback() {
		$this->checkShutdown();
	}

	/**
	 * Handle Each 1 Hour Timer Callback
	 */
	public function handleHourlyTimerCallback() {
		$this->checkShutdown();
	}

	/**
	 * Check if the Server should be shut down and perform it if necessary
	 */
	private function checkShutdown() {
		$intervalShutdownEnabled = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_SHUTDOWN_INTERVAL_ENABLE);
		if ($intervalShutdownEnabled) {
			// Check if server is empty
			if (!$this->maniaControl->getServer()->isEmpty()) {
				return;
			}


			// Check if shutdown interval is reached
			$networkStats         = $this->maniaControl->getClient()->getNetworkStats();
			$uptimeDays           = $networkStats->uptime / (24 * 3600);
			$shutdownIntervalDays = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_SHUTDOWN_INTERVAL);
			if ($shutdownIntervalDays <= 0 || $uptimeDays < $shutdownIntervalDays) {
				return;
			}

			// Shut down server
			$this->maniaControl->getClient()->stopServer();

			// Quit ManiaControl
			$this->maniaControl->quit("AutoShutdownPlugin: Shut down Server after {$shutdownIntervalDays} Days.");
		}


		$timedShutdownEnabled = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_TIMED_SHUTDOWN);
		if ($timedShutdownEnabled) {
			$day  = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_TIMED_SHUTDOWN_DAY);
			$hour = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_TIMED_SHUTDOWN_HOUR);


			$networkStats = $this->maniaControl->getClient()->getNetworkStats();
			$uptimeHours  = $networkStats->uptime / (3600);

			// Check if server is running more than one hour, weekday, and time
			if ($uptimeHours > 1 && $day == date('l', time()) && intval($hour) == intval(date('G', time()))) {
				$this->shutdownRequested = true;
			}
		}

		if ($this->shutdownRequested) {
			// Check if server is empty
			if (!$this->maniaControl->getServer()->isEmpty()) {
				return;
			}

			// Shut down server
			$this->maniaControl->getClient()->stopServer();

			// Quit ManiaControl
			$this->maniaControl->quit("AutoShutdownPlugin: Shut down Server after Time Request.");
		}
	}
}
