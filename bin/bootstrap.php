<?php

/**
 * This file is part of ZTB
 */
namespace ZTB;

use Cranberry\Filesystem;
use Cranberry\Shell;
use Cranberry\Shell\Input;
use Cranberry\Shell\Output;
use Cranberry\Shell\Middleware;

$___bootstrap = function( Shell\Application &$app )
{
	/*
	 * Middleware
	 */

	/**
	 * Configures Engine object based on local environment.
	 *
	 * Once the engine object is configured, it is registered as a parameter for
	 * all remaining middleware.
	 *
	 * @param	Cranberry\Shell\Input\InputInterface	$input
	 *
	 * @param	Cranberry\Shell\Output\OutputInterface	$output
	 *
	 * @return	Middleware\Middleware::CONTINUE
	 */
	$___init = function( Input\InputInterface $input, Output\OutputInterface $output )
	{
		if( !$input->hasEnv( 'ZTB_DATA' ) )
		{
			throw new \RuntimeException( sprintf( Application::ERROR_STRING_ENV, 'ZTB_DATA' ) );
		}
		if( !$input->hasEnv( 'ZTB_CORPORA' ) )
		{
			throw new \RuntimeException( sprintf( Application::ERROR_STRING_ENV, 'ZTB_CORPORA' ) );
		}

		/* Corpora */
		$corporaPathname = $input->getEnv( 'ZTB_CORPORA' );
		$corporaDirectory = new Filesystem\Directory( $corporaPathname );
		if( !$corporaDirectory->exists() )
		{
			throw new \RuntimeException( sprintf( "Invalid corpora directory: '%s' not found", $corporaDirectory->getPathname() ) );
		}

		/* Data directory */
		$dataPathname = $input->getEnv( 'ZTB_DATA' );
		$dataDirectory = new Filesystem\Directory( $dataPathname );
		if( !$dataDirectory->exists() )
		{
			throw new \RuntimeException( sprintf( "Invalid data directory: '%s' not found", $dataDirectory->getPathname() ) );
		}
		if( !$dataDirectory->isWritable() )
		{
			throw new \RuntimeException( sprintf( "Invalid data directory: Insufficient permissions for '%s'", $dataDirectory->getPathname() ) );
		}

		/* History */
		$historyFile = $dataDirectory->getChild( 'history.json', Filesystem\Node::FILE );
		if( !$historyFile->exists() )
		{
			$historyFile->putContents( '[]' );
		}

		/* Engine */
		$engine = new Engine( $historyFile, $corporaDirectory );
		$this->registerMiddlewareParameter( $engine );

		return Middleware\Middleware::CONTINUE;
	};
	$app->pushMiddleware( new Middleware\Middleware( $___init ) );

	/*
	 * Commands
	 */
	// ...

	/*
	 * Error Middleware
	 */
	$___runtime = function( Input\InputInterface $input, Output\OutputInterface $output, \RuntimeException $exception )
	{
		$output->write( sprintf( '%s: %s', $this->getName(), $exception->getMessage() ) . PHP_EOL );
	};
	$app->pushErrorMiddleware( new Middleware\Middleware( $___runtime, \RuntimeException::class ) );
};
