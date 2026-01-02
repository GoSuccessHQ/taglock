<?php

/**
 * Service Configuration
 *
 * Configures services for the TagLock plugin using Symfony's DependencyInjection component.
 */

declare(strict_types=1);

namespace GoSuccess\TagLock\Configuration;

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

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
		'DTO/*',
		'Enum/*',
		'Exception/*',
		'Util/*',
	];

	// Load all services
	$services->load( 'GoSuccess\\TagLock\\', __DIR__ . '/../*' )
		->exclude( __DIR__ . '/../{' . implode( ',', $excludedPaths ) . '}' )
		->public();

	// Manually tag Route services because instanceof() doesn't work reliably with load()
	$services->get( \GoSuccess\TagLock\Route\AccessCheckRoute::class )
		->tag( 'taglock.api_route' );

	$services->get( \GoSuccess\TagLock\Route\SettingsRoute::class )
		->tag( 'taglock.api_route' );
};
