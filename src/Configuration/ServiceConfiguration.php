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
		->tag( 'taglock.service' );

	$excludedPaths = [
		'Configuration/ServiceConfiguration.php',
		'Contract/*',
		'Core/*',
		'Enum/*',
		'Exception/*',
		'Util/*',
	];

	$services->load( 'GoSuccess\\TagLock\\', __DIR__ . '/../*' )
		->exclude( __DIR__ . '/../{' . implode( ',', $excludedPaths ) . '}' )
		->public();

	// Store service IDs tagged with 'taglock.service' in a parameter
	$container->parameters()->set( 'taglock.service_ids', tagged_iterator( 'taglock.service' ) );
};
