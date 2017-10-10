<?php

/*
 * This file is part of zoostheboss
 */
namespace ZTB;

class Engine
{
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
	public function getRandomCorpusFromPool( array $corpusPool, History &$history ) : Corpus
	{
		shuffle( $corpusPool );
		foreach( $corpusPool as $corpus )
		{
			if( !$this->isCorpusExhausted( $corpus, $history ) )
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

		return $this->getRandomCorpusFromPool( $corpusPool, $history );
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
	public function isCorpusExhausted( Corpus $corpus, History $history ) : bool
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
	public function isCorpusPoolExhausted( array $corpusPool, History $history ) : bool
	{
		$poolIsExhausted = true;

		foreach( $corpusPool as $corpus )
		{
			$poolIsExhausted = $poolIsExhausted && $this->isCorpusExhausted( $corpus, $history );
		}

		return $poolIsExhausted;
	}
}
