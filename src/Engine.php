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
	protected $firstNameCorpusPool=[];

	/**
	 * @var	array
	 */
	protected $firstNameFilters=[];

	/**
	 * @var	ZTB\History
	 */
	protected $history;

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
	 * @param	ZTB\History	$history
	 *
	 * @param	Cranberry\Filesystem\Directory	$corporaDirectory
	 *
	 * @return	void
	 */
	public function __construct( History $history, Filesystem\Directory $corporaDirectory )
	{
		$this->history = $history;
		$this->corporaDirectory = $corporaDirectory;
	}

	/**
	 * Returns whether a given string contains one or zero hyphens
	 *
	 * @param	string	$string
	 *
	 * @return	bool
	 */
	static public function ___filterMultipleHyphens( string $string ) : bool
	{
		return substr_count( $string, '-' ) <= 1;
	}

	/**
	 * Returns whether a given string contains any space characters
	 *
	 * @param	string	$string
	 *
	 * @return	bool
	 */
	static public function ___filterSpaces( string $string ) : bool
	{
		return substr_count( $string, ' ' ) < 1;
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
	 * Returns random value from first-name Corpus pool
	 *
	 * @return	string
	 */
	public function getRandomFirstName() : string
	{
		$filters = $this->firstNameFilters;

		do
		{
			$didPassAllFilters = true;
			$firstName = $this->getRandomValueFromCorpusPool( $this->firstNameCorpusPool, $this->history );

			foreach( $filters as $filter )
			{
				$didPassFilter = call_user_func( $filter, $firstName );
				$didPassAllFilters = $didPassAllFilters && $didPassFilter;
			}
		}
		while( $didPassAllFilters == false );

		return $firstName;
	}

	/**
	 * Returns random value from honorific Corpus pool
	 *
	 * @return	string
	 */
	public function getRandomHonorific() : string
	{
		$filters = $this->honorificsFilters;

		do
		{
			$didPassAllFilters = true;
			$honorific = $this->getRandomValueFromCorpusPool( $this->honorificsCorpusPool, $this->history );

			foreach( $filters as $filter )
			{
				$didPassFilter = call_user_func( $filter, $honorific );
				$didPassAllFilters = $didPassAllFilters && $didPassFilter;
			}
		}
		while( $didPassAllFilters == false );

		return $honorific;
	}

	/**
	 * Returns random value from last-name Corpus pool
	 *
	 * @return	string
	 */
	public function getRandomLastName() : string
	{
		$filters = $this->lastNameFilters;

		do
		{
			$didPassAllFilters = true;
			$lastName = $this->getRandomValueFromCorpusPool( $this->lastNameCorpusPool, $this->history );

			foreach( $filters as $filter )
			{
				$didPassFilter = call_user_func( $filter, $lastName );
				$didPassAllFilters = $didPassAllFilters && $didPassFilter;
			}
		}
		while( $didPassAllFilters == false );

		return $lastName;
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
	 * @param	Callable	$filter
	 *
	 * @return	void
	 */
	public function registerFirstNameFilter( Callable $filter )
	{
		$this->firstNameFilters[] = $filter;
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
	 * @param	Callable	$filter
	 *
	 * @return	void
	 */
	public function registerHonorificsFilter( Callable $filter )
	{
		$this->honorificsFilters[] = $filter;
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
	 * @param	Callable	$filter
	 *
	 * @return	void
	 */
	public function registerLastNameFilter( Callable $filter )
	{
		$this->lastNameFilters[] = $filter;
	}
}
