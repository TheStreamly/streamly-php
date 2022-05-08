<?php

namespace Streamly;

use Streamly\Input\Dsn;
use Streamly\Input\Options;
use Streamly\Input\Http;
use Streamly\Input\Server;
use Streamly\Session;
use Streamly\Store\Store;
use Streamly\Store\Providers\RequestProvider;
use Streamly\Store\Providers\StoreProviderInterface;
use Streamly\Entity\Event;

class Streamly
{
	/**
	 * @var Streamly|null
	 */
	private static ?self $instance = null;

	/**
	 * @var Dsn|null
	 */
	private static ?Dsn $dsn = null;

	/**
	 * @var Options|null
	 */
	private static ?Options $options = null;

	/**
	 * @var Http|null
	 */
	private static ?Http $http = null;

	/**
	 * @var Server|null
	 */
	private static ?Server $server = null;

	/**
	 * @var string
	 */
	private static string $traceId;

	/**
	 * @var string
	 */
	private static string $sessionId;

	/**
	 * @var string
	 */
	private static string $userId;

	/**
	 *
	 */
	protected function __construct() { }

	/**
	 * @return void
	 */
	protected function __clone() { }

	/**
	 * @param string $dsn
	 * @param array $options
	 * @return void
	 * @throws Exceptions\InvalidDsnException
	 */
	public static function Initialize(string $dsn, array $options = []): void
	{
		if(isset($options['storeProvider']) === false || !($options['storeProvider'] instanceof StoreProviderInterface)) {
			$options['storeProvider'] = new RequestProvider();
		}
		
		self::$dsn = new Dsn($dsn);
		self::$options = new Options($options);
		self::$http = new Http($_SERVER);
		self::$server = new Server();
		self::$traceId = Session::traceId();
		self::$sessionId = Session::sessionId();
		self::$userId = Session::userId();

		self::getInstance();

		// Event
		if(self::getOptions()->get('initializeRequest', true) === true) {
			Activity('streamly.initialize');
		}

		// Log
		\Streamly\Log(
			sprintf(
				'Initialize for %s',
				$dsn
			)
		);
	}

	/**
	 * @return string
	 */
	public static function traceId(): string
	{
		return self::$traceId;
	}

	/**
	 * @return string
	 */
	public static function sessionId(): string
	{
		return self::$sessionId;
	}

	/**
	 * @return string
	 */
	public static function userId(): string
	{
		return self::$userId;
	}

	/**
	 * @return bool
	 */
	public static function isInitialize(): bool
	{
		return self::$dsn !== null;
	}

	/**
	 * @return Dsn|null
	 */
	public static function getDsn(): ?Dsn
	{
		return self::$dsn;
	}

	/**
	 * @return Options|null
	 */
	public static function getOptions(): ?Options
	{
		return self::$options;
	}

	/**
	 * @return Http
	 */
	public static function getHttp(): Http
	{
		return self::$http;
	}

	/**
	 * @return Server
	 */
	public static function getServer(): Server
	{
		return self::$server;
	}

	/**
	 * @return void
	 */
	public static function Close(): void
	{
		// Event
		if(self::getOptions()->get('initializeRequest', true) === true) {
			Activity('streamly.close');
		}

		// Close
		$store = new Store(Streamly::$options->get('storeProvider'));
		$store->close(Streamly::traceId());

		// Log
		\Streamly\Log('Close');
	}

	/**
	 * @return Streamly
	 */
	public static function getInstance(): Streamly
	{
		if (self::$instance === null) {
			self::$instance = new static();
		}

		return self::$instance;
	}
}
