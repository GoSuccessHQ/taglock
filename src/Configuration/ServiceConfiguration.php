<?php

/**
 * Service Configuration
 *
 * Configures services for the TagLock plugin using Symfony's DependencyInjection component.
 */

declare(strict_types=1);

namespace GoSuccess\TagLock\Configuration;

use GoSuccess\TagLock\Contract\CrmProviderInterface;
use GoSuccess\TagLock\Provider\CrmProviderFactory;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function defined;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

defined( 'ABSPATH' ) || exit;

return static function ( ContainerConfigurator $container ): void {
	$services = $container
		->services()
		->defaults()
		->autowire()
		->autoconfigure()
		->tag( 'taglock.service' )
		->bind( 'iterable $routes', tagged_iterator( 'taglock.api_route' ) );

	$excludedPaths = [
		'Configuration/ServiceConfiguration.php',
		'Core/*',
		'Dto/*',
		'Enum/*',
		'Exception/*',
		'Util/*',
	];

	$services->load( 'GoSuccess\\TagLock\\', __DIR__ . '/../*' )
		->exclude( __DIR__ . '/../{' . implode( ',', $excludedPaths ) . '}' )
		->public();

	// Register CrmProviderInterface alias to use the factory
	$services->set( CrmProviderInterface::class )
		->factory( [ service( CrmProviderFactory::class ), 'getProvider' ] )
		->public();
};
