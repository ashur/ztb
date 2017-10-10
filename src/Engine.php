<?php

/*
 * This file is part of zoostheboss
 */
namespace ZTB;

class Engine
{
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
	 * Finds whether all corpora in a pool are exhaused
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
