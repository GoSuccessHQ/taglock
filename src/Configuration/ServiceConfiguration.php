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
		->bind( 'iterable $routes', tagged_iterator( 'taglock.api_route' ) );

	$excludedPaths = [
		'Configuration/ServiceConfiguration.php',
		'Core/*',
		'DTO/*',
		'Enum/*',
		'Exception/*',
		'Util/*',
	];

	$services->load( 'GoSuccess\\TagLock\\', __DIR__ . '/../*' )
		->exclude( __DIR__ . '/../{' . implode( ',', $excludedPaths ) . '}' )
		->public();

	// Tag all Route classes as API routes
	$services->instanceof( \GoSuccess\TagLock\Contract\ApiRouteInterface::class )
		->tag( 'taglock.api_route' );

	// Tag all Service classes for automatic initialization
	$services->instanceof( \GoSuccess\TagLock\Service\AdminMenuService::class )->tag( 'taglock.autoload' );
	$services->instanceof( \GoSuccess\TagLock\Service\AssetService::class )->tag( 'taglock.autoload' );
	$services->instanceof( \GoSuccess\TagLock\Service\RestApiService::class )->tag( 'taglock.autoload' );
	$services->instanceof( \GoSuccess\TagLock\Service\ShortcodeService::class )->tag( 'taglock.autoload' );
	$services->instanceof( \GoSuccess\TagLock\Service\RestExceptionHandlerService::class )->tag( 'taglock.autoload' );
};
