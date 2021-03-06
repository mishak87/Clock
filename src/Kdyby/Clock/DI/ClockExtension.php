<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Clock\DI;

use Kdyby;
use Kdyby\Clock\UnexpectedValueException;
use Nette;
use Nette\PhpGenerator as Code;



if (!class_exists('Nette\DI\CompilerExtension')) {
	class_alias('Nette\Config\CompilerExtension', 'Nette\DI\CompilerExtension');
	class_alias('Nette\Config\Compiler', 'Nette\DI\Compiler');
	class_alias('Nette\Config\Helpers', 'Nette\DI\Config\Helpers');
}

if (isset(Nette\Loaders\NetteLoader::getInstance()->renamed['Nette\Configurator']) || !class_exists('Nette\Configurator')) {
	unset(Nette\Loaders\NetteLoader::getInstance()->renamed['Nette\Configurator']); // fuck you
	class_alias('Nette\Config\Configurator', 'Nette\Configurator');
}

/**
 * @author Michael Moravec
 * @author Filip Procházka <filip@prochazka.su>
 */
class ClockExtension extends Nette\DI\CompilerExtension
{

	public $defaults = array(
		'provider' => 'standard',
	);

	public static $providers = array(
		'standard' => 'Kdyby\Clock\Providers\StandardProvider',
		'request' => 'Kdyby\Clock\Providers\RequestTimeProvider',
	);



	public function loadConfiguration()
	{
		$config = $this->getConfig($this->defaults);
		$builder = $this->getContainerBuilder();

		$providerImpl = $config['provider'];
		if (isset(self::$providers[$providerImpl])) {
			$providerImpl = self::$providers[$providerImpl];
		}

		if (!class_exists($providerImpl)) {
			throw new UnexpectedValueException("DateTime provider implementation class '$providerImpl' does not exist or could not be loaded.");
		}

		if (!Nette\Reflection\ClassType::from($providerImpl)->implementsInterface('Kdyby\Clock\IDateTimeProvider')) {
			throw new UnexpectedValueException("DateTime provider implementation class '$providerImpl' must implement interface Kdyby\\Clock\\IDateTimeProvider.");
		}

		$providerDef = $builder->addDefinition($this->prefix('dateTimeProvider'))
			->setClass('Kdyby\Clock\IDateTimeProvider')
			->setFactory($providerImpl);

		if ($config['provider'] === 'request') {
			$providerDef->setArguments(array(new Code\PhpLiteral('isset($_SERVER["REQUEST_TIME"]) ? $_SERVER["REQUEST_TIME"] : time()')));
			$providerDef->addTag('run');
		}
	}



	public static function register(Nette\Configurator $configurator)
	{
		$configurator->onCompile[] = function ($config, Nette\DI\Compiler $compiler) {
			$compiler->addExtension('clock', new ClockExtension());
		};
	}

}
