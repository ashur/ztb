<?php

/*
 * This file is part of zoostheboss
 */
namespace ZTB;

use Cranberry\Filesystem;

class Engine
{
	/**
	 * @var	array
	 */
	protected $characterNameCorpusPool=[];

	/**
	 * @var	array
	 */
	protected $characterNameFilters=[];

	/**
	 * @var	array
	 */
	protected $firstNameCorpusPool=[];

	/**
	 * @var	array
	 */
	protected $firstNameFilters=[];

	/**
	 * @var	Cranberry\Filesystem\File
	 */
	protected $fontFile;

	/**
	 * @var	array
	 */
	protected $globalFilters=[];

	/**
	 * @var	ZTB\History
	 */
	protected $history;

	/**
	 * @var	Cranberry\Filesystem\File
	 */
	protected $historyFile;

	/**
	 * @var	array
	 */
	protected $honorificsCorpusPool=[];

	/**
	 * @var	array
	 */
	protected $honorificsFilters=[];

	/**
	 * @var	array
	 */
	protected $lastNameCorpusPool=[];

	/**
	 * @var	array
	 */
	protected $lastNameFilters=[];

	/**
	 * @var	ZTB\Corpus
	 */
	protected $namePatternCorpus;

	/**
	 * @var	array
	 */
	protected $occupationsCorpusPool=[];

	/**
	 * @var	array
	 */
	protected $occupationsFilters=[];

	/**
	 * @var	array
	 */
	protected $performerPrefixesCorpusPool=[];

	/**
	 * @var	ZTB\Corpus
	 */
	protected $prefixPatternCorpus;

	/**
	 * @var	ZTB\Corpus
	 */
	protected $rolePatternCorpus;

	/**
	 * @param	ZTB\History	$history
	 *
	 * @param	Cranberry\Filesystem\Directory	$corporaDirectory
	 *
	 * @return	void
	 */
	public function __construct( Filesystem\File $historyFile, Filesystem\Directory $corporaDirectory, Filesystem\File $fontFile )
	{
		$this->historyFile = $historyFile;
		$this->history = History::createFromJSONEncodedFile( $historyFile );

		$this->corporaDirectory = $corporaDirectory;

		$this->fontFile = $fontFile;

		$this->namePatternCorpus = new Corpus( 'name_pattern', ['%F %L', '%F', '%F '] );
		$this->prefixPatternCorpus = new Corpus( 'prefix_pattern', ['%P'] );
		$this->rolePatternCorpus = new Corpus( 'role_pattern', ['the %O', 'the %O', '%C', '%C', '%O #%n'] );
	}

	/**
	 * Returns whether a given string contains an acceptable number of hyphens
	 *
	 * @param	string	$string
	 *
	 * @param	int	$maxCount
	 *
	 * @return	bool
	 */
	static public function ___filterHyphens( string $string, int $maxCount ) : bool
	{
		return substr_count( $string, '-' ) <= $maxCount;
	}

	/**
	 * Returns whether a given string contains any space characters
	 *
	 * @param	string	$string
	 *
	 * @param	int	$maxCount
	 *
	 * @return	bool
	 */
	static public function ___filterSpaces( string $string, int $maxCount ) : bool
	{
		return substr_count( $string, ' ' ) <= $maxCount;
	}

	/**
	 * Returns whether a given string appears in a given array of unwanted words
	 *
	 * @param	string	$string
	 *
	 * @param	array	$unwantedWords
	 *
	 * @return	bool
	 */
	static public function ___filterUnwantedWords( string $string, array $unwantedWords ) : bool
	{
		$string = strtolower( $string );
		foreach( $unwantedWords as $unwantedWord )
		{
			$pattern = sprintf( '/%s/', $unwantedWord );
			$result = preg_match( $pattern, $string );

			if( $result == 1 )
			{
				return false;
			}
		}
		return true;
	}

	/**
	 * Returns Corpus object instantiated from JSON-encoded data file
	 *
	 * @param	string	$category
	 *
	 * @param	string	$corpus
	 *
	 * @param	string	$domain
	 *
	 * @throws	InvalidArgumentException	If source file not found
	 *
	 * @return	ZTB\Corpus
	 */
	public function getCorpus( string $category, string $corpus, string $domain=null ) : Corpus
	{
		$corpusFile = $this->corporaDirectory
			->getChild( $category, Filesystem\Node::DIRECTORY )
			->getChild( "{$corpus}.json", Filesystem\Node::FILE );

		if( !$corpusFile->exists() )
		{
			throw new \InvalidArgumentException( "Corpus file not found: '{$corpusFile}'" );
		}

		$corpus = Corpus::createFromJSONEncodedFile( $corpusFile, $domain );
		return $corpus;
	}

	/**
	 * Returns font File object
	 *
	 * @return	Cranberry\Filesystem\File
	 */
	public function getFontFile() : Filesystem\File
	{
		return $this->fontFile;
	}

	/**
	 * Returns a performer name
	 *
	 * @return	string
	 */
	public function getPerformerName() : string
	{
		$namePattern = $this->getRandomValueFromCorpus( $this->namePatternCorpus, $this->history );
		$this->history->addDomainItem( $this->namePatternCorpus->getName(), $namePattern );

		/* First Name */
		if( substr_count( $namePattern, '%F' ) )
		{
			$firstName = $this->getRandomFirstName();
			$namePattern = str_replace( '%F', $firstName, $namePattern );
		}
		/* Last Name */
		if( substr_count( $namePattern, '%L' ) )
		{
			$lastName = $this->getRandomLastName();
			$namePattern = str_replace( '%L', $lastName, $namePattern );
		}
		/* Honorific */
		if( substr_count( $namePattern, '%H' ) )
		{
			$honorific = $this->getRandomHonorific();
			$namePattern = str_replace( '%H', $honorific, $namePattern );
		}

		$namePattern = trim( $namePattern );
		$namePattern = ucwords( $namePattern, " \t\r\n\f\v-" );

		return $namePattern;
	}

	/**
	 * Returns a performer prefix
	 *
	 * @return	string
	 */
	public function getPerformerPrefix() : string
	{
		$prefixPattern = $this->getRandomValueFromCorpus( $this->prefixPatternCorpus, $this->history );
		$this->history->addDomainItem( $this->prefixPatternCorpus->getName(), $prefixPattern );

		/* Prefix */
		if( substr_count( $prefixPattern, '%P' ) )
		{
			$prefix = $this->getRandomPerformerPrefix();
			$prefixPattern = str_replace( '%P', $prefix, $prefixPattern );
		}

		$prefixPattern = trim( $prefixPattern );
		return $prefixPattern;
	}

	/**
	 * Returns a random, inexhausted Corpus object from the given pool.
	 *
	 * If all Corpus objects are exhausted, reset their domains in the given
	 * History object and try again.
	 *
	 * @param	array		$corpusPool		An array of Corpus objects
	 *
	 * @param	ZTB\History	&$history		Passed by reference
	 *
	 * @return	ZTB\Corpus
	 */
	static public function getRandomCorpusFromPool( array $corpusPool, History &$history ) : Corpus
	{
		if( count( $corpusPool ) == 0 )
		{
			throw new \UnderflowException( 'Corpus pool is empty' );
		}

		shuffle( $corpusPool );
		foreach( $corpusPool as $corpus )
		{
			if( !self::isCorpusExhausted( $corpus, $history ) )
			{
				return $corpus;
			}
		}

		/* All Corpus objects are exhausted, so we'll reset all relevant domains
		   in the given History object */
		foreach( $corpusPool as $corpus )
		{
			$history->removeDomain( $corpus->getName() );
		}

		return self::getRandomCorpusFromPool( $corpusPool, $history );
	}

	/**
	 * Returns random value from given Corpus pool which passes given filters
	 *
	 * @param	array	$filterQueue
	 *
	 * @param	array	$corpusPool
	 *
	 * @return	string
	 */
	protected function getRandomFilteredStringFromCorpusPool( array $filterQueue, array $corpusPool ) : string
	{
		$filters = array_merge( $this->globalFilters, $filterQueue );

		do
		{
			$didPassAllFilters = true;
			$string = $this->getRandomValueFromCorpusPool( $corpusPool, $this->history );

			foreach( $filters as $filter )
			{
				$filterParams = $filter['params'];
				array_unshift( $filterParams, $string );

				$didPassFilter = call_user_func_array( $filter['callback'], $filterParams );
				$didPassAllFilters = $didPassAllFilters && $didPassFilter;
			}
		}
		while( $didPassAllFilters == false );

		return $string;
	}

	/**
	 * Returns random, filtered value from character name Corpus pool
	 *
	 * @return	string
	 */
	public function getRandomCharacterName() : string
	{
		return $this->getRandomFilteredStringFromCorpusPool( $this->characterNameFilters, $this->characterNameCorpusPool );
	}

	/**
	 * Returns random, filtered value from first-name Corpus pool
	 *
	 * @return	string
	 */
	public function getRandomFirstName() : string
	{
		return $this->getRandomFilteredStringFromCorpusPool( $this->firstNameFilters, $this->firstNameCorpusPool );
	}

	/**
	 * Returns random, filtered value from honorific Corpus pool
	 *
	 * @return	string
	 */
	public function getRandomHonorific() : string
	{
		return $this->getRandomFilteredStringFromCorpusPool( $this->honorificsFilters, $this->honorificsCorpusPool );
	}

	/**
	 * Returns random, filtered value from last-name Corpus pool
	 *
	 * @return	string
	 */
	public function getRandomLastName() : string
	{
		return $this->getRandomFilteredStringFromCorpusPool( $this->lastNameFilters, $this->lastNameCorpusPool );
	}

	/**
	 * Returns random, filtered value from occupations Corpus pool
	 *
	 * @return	string
	 */
	public function getRandomOccupation() : string
	{
		return $this->getRandomFilteredStringFromCorpusPool( $this->occupationsFilters, $this->occupationsCorpusPool );
	}

	/**
	 * Returns random, filtered value from performer prefixes Corpus pool
	 *
	 * @return	string
	 */
	public function getRandomPerformerPrefix() : string
	{
		return $this->getRandomFilteredStringFromCorpusPool( [], $this->performerPrefixesCorpusPool );
	}

	/**
	 * Returns a random value from the given Corpus object which is not present
	 * in the associated History domain.
	 *
	 * If all Corpus values are exhausted, reset the domain in the given History
	 * object and try again
	 *
	 * @return    string
	 */
	static public function getRandomValueFromCorpus( Corpus $corpus, History &$history ) : string
	{
		$corpusName = $corpus->getName();
		$corpusValues = $corpus->getAllItems();

		if( count( $corpusValues ) == 0 )
		{
			throw new \UnderflowException( 'Corpus is empty' );
		}

		shuffle( $corpusValues );

		foreach( $corpusValues as $corpusValue )
		{
			if( !$history->hasDomainItem( $corpusName, $corpusValue ) )
			{
				return $corpusValue;
			}
		}

		/* All values are exhausted, so we'll reset the relevant domain in the
		   given History object */
		$history->removeDomain( $corpusName );

		return self::getRandomValueFromCorpus( $corpus, $history );
	}

	/**
	 * Returns a random string from the given Corpus pool.
	 *
	 * @param	array		$corpusPool		An array of Corpus objects
	 *
	 * @param	ZTB\History	$history
	 *
	 * @return	string
	 */
	static public function getRandomValueFromCorpusPool( array $corpusPool, History $history ) : string
	{
		$randomCorpus = self::getRandomCorpusFromPool( $corpusPool, $history );
		$randomValue = self::getRandomValueFromCorpus( $randomCorpus, $history );

		$history->addDomainItem( $randomCorpus->getName(), $randomValue );

		return $randomValue;
	}

	/**
	 * Returns a character name
	 *
	 * @return	string
	 */
	public function getRole() : string
	{
		$rolePattern = $this->getRandomValueFromCorpus( $this->rolePatternCorpus, $this->history );
		$this->history->addDomainItem( $this->rolePatternCorpus->getName(), $rolePattern );

		/* Occupation */
		if( substr_count( $rolePattern, '%O' ) )
		{
			$occupation = $this->getRandomOccupation();
			$occupation = ucwords( $occupation, " \t\r\n\f\v-" );

			$rolePattern = str_replace( '%O', $occupation, $rolePattern );
		}
		/* Character Name */
		if( substr_count( $rolePattern, '%C' ) )
		{
			$occupation = $this->getRandomCharacterName();
			$occupation = ucwords( $occupation, " \t\r\n\f\v-" );

			$rolePattern = str_replace( '%C', $occupation, $rolePattern );
		}
		/* Number */
		if( substr_count( $rolePattern, '%n' ) )
		{
			$roleNumber = $this->getRandomValueFromCorpus( new Corpus( 'role_number', [1,2,3] ), $this->history );
			$this->history->addDomainItem( 'role_number', $roleNumber );

			$rolePattern = str_replace( '%n', $roleNumber, $rolePattern );
		}

		$rolePattern = trim( $rolePattern );

		return $rolePattern;
	}

	/**
	 * Finds whether all items in a corpus appear in the history
	 *
	 * @param	ZTB\Corpus	$corpus
	 *
	 * @param	ZTB\History	$history
	 *
	 * @return	bool
	 */
	static public function isCorpusExhausted( Corpus $corpus, History $history ) : bool
	{
		$corpusItems = $corpus->getAllItems();
		$corpusName = $corpus->getName();

		if( !$history->hasDomain( $corpusName ) )
		{
			return false;
		}

		$historyItems = $history->getAllDomainItems( $corpusName );

		$itemsDiff = array_diff( $corpusItems, $historyItems );

		return count( $itemsDiff ) == 0;
	}

	/**
	 * Finds whether all corpora in a pool are exhausted
	 *
	 * @param	array		$corpusPool		An array of Corpus objects
	 *
	 * @param	ZTB\History	$history
	 *
	 * @return	bool
	 */
	static public function isCorpusPoolExhausted( array $corpusPool, History $history ) : bool
	{
		$poolIsExhausted = true;

		foreach( $corpusPool as $corpus )
		{
			$poolIsExhausted = $poolIsExhausted && self::isCorpusExhausted( $corpus, $history );
		}

		return $poolIsExhausted;
	}

	/**
	 * Pushes a filter onto the end of the given filter queue
	 *
	 * @param	array	$filterQueue
	 *
	 * @param	Callable	$filterCallback
	 *
	 * @param	array	$filterParams
	 *
	 * @return	void
	 */
	protected function registerFilter( array &$filterQueue, Callable $filterCallback, array $filterParams=[] )
	{
		$filter['callback'] = $filterCallback;
		$filter['params'] = $filterParams;

		$filterQueue[] = $filter;
	}

	/**
	 * Register given Corpus in character-name pool
	 *
	 * @param	ZTB\Corpus	$corpus
	 *
	 * @return	void
	 */
	public function registerCharacterNameCorpus( Corpus $corpus )
	{
		$this->characterNameCorpusPool[] = $corpus;
	}

	/**
	 * Pushes a filter onto the end of the character name filter queue
	 *
	 * @param	Callable	$filterCallback
	 *
	 * @param	array	$filterParams
	 *
	 * @return	void
	 */
	public function registerCharacterNameFilter( Callable $filterCallback, array $filterParams=[] )
	{
		$this->registerFilter( $this->characterNameFilters, $filterCallback, $filterParams );
	}

	/**
	 * Register given Corpus in first-name pool
	 *
	 * @param	ZTB\Corpus	$corpus
	 *
	 * @return	void
	 */
	public function registerFirstNameCorpus( Corpus $corpus )
	{
		$this->firstNameCorpusPool[] = $corpus;
	}

	/**
	 * Pushes a filter onto the end of the first name filter queue
	 *
	 * @param	Callable	$filterCallback
	 *
	 * @param	array	$filterParams
	 *
	 * @return	void
	 */
	public function registerFirstNameFilter( Callable $filterCallback, array $filterParams=[] )
	{
		$this->registerFilter( $this->firstNameFilters, $filterCallback, $filterParams );
	}

	/**
	 * Pushes a filter onto the end of the global filter queue
	 *
	 * @param	Callable	$filterCallback
	 *
	 * @param	array	$filterParams
	 *
	 * @return	void
	 */
	public function registerGlobalFilter( Callable $filterCallback, array $filterParams=[] )
	{
		$this->registerFilter( $this->globalFilters, $filterCallback, $filterParams );
	}

	/**
	 * Register given Corpus in honorifics pool
	 *
	 * @param	ZTB\Corpus	$corpus
	 *
	 * @return	void
	 */
	public function registerHonorificsCorpus( Corpus $corpus )
	{
		$this->honorificsCorpusPool[] = $corpus;
	}

	/**
	 * Pushes a filter onto the end of the honorifics filter queue
	 *
	 * @param	Callable	$filterCallback
	 *
	 * @param	array	$filterParams
	 *
	 * @return	void
	 */
	public function registerHonorificsFilter( Callable $filterCallback, array $filterParams=[] )
	{
		$this->registerFilter( $this->honorificsFilters, $filterCallback, $filterParams );
	}

	/**
	 * Register given Corpus in last-name pool
	 *
	 * @param	ZTB\Corpus	$corpus
	 *
	 * @return	void
	 */
	public function registerLastNameCorpus( Corpus $corpus )
	{
		$this->lastNameCorpusPool[] = $corpus;
	}

	/**
	 * Pushes a filter onto the end of the last name filter queue
	 *
	 * @param	Callable	$filterCallback
	 *
	 * @param	array	$filterParams
	 *
	 * @return	void
	 */
	public function registerLastNameFilter( Callable $filterCallback, array $filterParams=[] )
	{
		$this->registerFilter( $this->lastNameFilters, $filterCallback, $filterParams );
	}

	/**
	 * Register given Corpus in occupations pool
	 *
	 * @param	ZTB\Corpus	$corpus
	 *
	 * @return	void
	 */
	public function registerOccupationsCorpus( Corpus $corpus )
	{
		$this->occupationsCorpusPool[] = $corpus;
	}

	/**
	 * Pushes a filter onto the end of the occupations filter queue
	 *
	 * @param	Callable	$filterCallback
	 *
	 * @param	array	$filterParams
	 *
	 * @return	void
	 */
	public function registerOccupationsFilter( Callable $filterCallback, array $filterParams=[] )
	{
		$this->registerFilter( $this->occupationsFilters, $filterCallback, $filterParams );
	}

	/**
	 * Register given Corpus in performer-prefix pool
	 *
	 * @param	ZTB\Corpus	$corpus
	 *
	 * @return	void
	 */
	public function registerPerformerPrefixCorpus( Corpus $corpus )
	{
		$this->performerPrefixesCorpusPool[] = $corpus;
	}

	/**
	 * Write contents of history object to file
	 *
	 * @return	void
	 */
	public function writeHistory()
	{
		$this->history->writeToFile( $this->historyFile );
	}
}
