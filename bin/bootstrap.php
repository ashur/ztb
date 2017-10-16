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

	/**
	 * Registers filters and corpora
	 *
	 * @param	Cranberry\Shell\Input\InputInterface	$input
	 *
	 * @param	Cranberry\Shell\Output\OutputInterface	$output
	 *
	 * @param	ZTB\Engine	$engine
	 *
	 * @todo	Populate $unwantedWords
	 *
	 * @return	Middleware\Middleware::CONTINUE
	 */
	$___register = function( Input\InputInterface $input, Output\OutputInterface $output, Engine $engine )
	{
		/*
		 * Filters
		 */
		$engine->registerGlobalFilter( [$engine, '___filterHyphens'], [1] );
		$engine->registerGlobalFilter( [$engine, '___filterSpaces'], [0] );

		// TODO
		$unwantedWords = [];
		$engine->registerGlobalFilter( [$engine, '___filterUnwantedWords'], [$unwantedWords] );

		/*
		 * Corpora
		 */
		$dataPathname = $input->getEnv( 'ZTB_DATA' );
		$dataDirectory = new Filesystem\Directory( $dataPathname );

		$configFile = $dataDirectory->getChild( 'config.json', Filesystem\Node::FILE );
		if( !$configFile->exists() )
		{
			throw new \RuntimeException( sprintf( "Invalid configuration: '%s' not found", $configFile->getPathname() ) );
		}
		$config = json_decode( $configFile->getContents(), true );
		if( json_last_error() != JSON_ERROR_NONE )
		{
			throw new \RuntimeException( sprintf( "Invalid configuration: '%s' in %s", json_last_error_msg(), $configFile->getPathname() ) );
		}

		foreach( ['first_names','last_names'] as $corpusPool )
		{
			if( !isset( $config[$corpusPool] ) )
			{
				throw new \RuntimeException( sprintf( "Invalid configuration: Missing required key '%s' in %s", $corpusPool, $configFile->getPathname() ) );
			}

			foreach( $config[$corpusPool] as $corpusInfo )
			{
				$corpusDomain = isset( $corpusInfo['domain'] ) ? $corpusInfo['domain'] : null;
				$corpus = $engine->getCorpus( $corpusInfo['category'], $corpusInfo['corpus'], $corpusDomain );

				switch( $corpusPool )
				{
					case 'first_names':
						$engine->registerFirstNameCorpus( $corpus );
						break;

					case 'last_names':
						$engine->registerLastNameCorpus( $corpus );
						break;
				}
			}
		}

		return Middleware\Middleware::CONTINUE;
	};
	$app->pushMiddleware( new Middleware\Middleware( $___register ) );

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
