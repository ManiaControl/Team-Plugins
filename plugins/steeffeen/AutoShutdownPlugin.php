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
 * @version 0.1
 */
class AutoShutdownPlugin implements Plugin, CallbackListener, TimerListener {
	/*
	 * Constants
	 */
	const ID                        = 51;
	const VERSION                   = 0.1;
	const NAME                      = 'Auto Shutdown Plugin';
	const AUTHOR                    = 'steeffeen';
	const SETTING_SHUTDOWN_INTERVAL = 'Shutdown Interval in Days';

	/**
	 * Private Properties
	 */
	/** @var ManiaControl $maniaControl */
	private $maniaControl = null;

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
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_SHUTDOWN_INTERVAL, 7);

		// Register callbacks
		$this->maniaControl->callbackManager->registerCallbackListener(PlayerManager::CB_SERVER_EMPTY, $this, 'handleServerEmptyCallback');
		$this->maniaControl->timerManager->registerTimerListening($this, 'handle12HoursTimerCallback', 12 * 3600 * 1000);
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
	 * Handle Each12Hours Timer Callback
	 */
	public function handle12HoursTimerCallback() {
		$this->checkShutdown();
	}

	/**
	 * Check if the Server should be shut down and perform it if necessary
	 */
	private function checkShutdown() {
		// Check if server is empty
		if (!$this->maniaControl->server->isEmpty()) {
			return;
		}

		// Check if shutdown interval is reached
		$networkStats         = $this->maniaControl->client->getNetworkStats();
		$uptimeDays           = $networkStats->uptime / (24 * 3600);
		$shutdownIntervalDays = $this->maniaControl->settingManager->getSettingValue($this, self::SETTING_SHUTDOWN_INTERVAL);
		if ($shutdownIntervalDays <= 0 || $uptimeDays < $shutdownIntervalDays) {
			return;
		}

		// Shut down server
		$this->maniaControl->client->stopServer();

		// Quit ManiaControl
		$this->maniaControl->quit("AutoShutdownPlugin: Shut down Server after {$shutdownIntervalDays} Days.");
	}
}
