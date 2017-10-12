<?php

/*
 * This file is part of zoostheboss
 */
namespace ZTB;

use PHPUnit\Framework\TestCase;

class EngineTest extends TestCase
{
	public function test_getRandomCorpusFromPool_ReturnsNonExhaustedCorpus()
	{
		$historyData = 	[
			'colors' => ['red','blue','green'],
			'condiments' => ['mayonnaise', 'aioli'],
			'fruits' => ['blueberry'],
		];
		$history = new History( $historyData );

		$corpusPool[] = new Corpus( 'colors', ['green','red','blue'] );
		$corpusPool[] = new Corpus( 'condiments', ['aioli','mayonnaise'] );

		$expectedCorpus = new Corpus( 'fruits', ['apple','blueberry'] );
		$corpusPool[] = $expectedCorpus;

		shuffle( $corpusPool );

		$this->assertEquals( $expectedCorpus, Engine::getRandomCorpusFromPool( $corpusPool, $history ) );
	}

	public function test_getRandomCorpusFromPool_RemovesAllCorpusDomainsFromHistoryWhenPoolIsExhausted()
	{
		$historyData = 	[
			'colors' => ['red','blue','green'],
			'condiments' => ['mayonnaise', 'aioli'],
			'fruits' => ['apple','blueberry'],
		];
		$history = new History( $historyData );

		$corpusPool[] = new Corpus( 'colors', ['green','red','blue'] );
		$corpusPool[] = new Corpus( 'condiments', ['aioli','mayonnaise'] );
		$corpusPool[] = new Corpus( 'fruits', ['apple','blueberry'] );

		shuffle( $corpusPool );

		$randomCorpus = Engine::getRandomCorpusFromPool( $corpusPool, $history );
		$randomCorpusName = $randomCorpus->getName();

		$this->assertTrue( in_array( $randomCorpus, $corpusPool ) );

		foreach( $corpusPool as $corpus )
		{
			$this->assertFalse( $history->hasDomain( $corpus->getName() ) );
		}
	}

	public function test_getRandomValueFromCorpus_ReturnsValueNotInHistory()
	{
		$historyData = [ 'fruits' => ['apple','raisin'] ];
		$history = new History( $historyData );

		$corpus = new Corpus( 'fruits', ['apple','blueberry','raisin'] );

		$randomValue = Engine::getRandomValueFromCorpus( $corpus, $history );
		$this->assertEquals( 'blueberry', $randomValue );
	}

	public function test_getRandomValueFromCorpus_RemovesDomainFromHistoryWhenCorpusIsExhausted()
	{
		$historyData = [ 'fruits' => ['blueberry','apple','raisin'] ];
		$history = new History( $historyData );

		$corpus = new Corpus( 'fruits', ['apple','blueberry','raisin'] );

		$randomValue = Engine::getRandomValueFromCorpus( $corpus, $history );

		$this->assertFalse( $history->hasDomain( 'fruits' ) );
		$this->assertTrue( in_array( $randomValue, ['raisin','apple','blueberry'] ) );
	}

	/**
	 * @expectedException	UnderflowException
	 */
	public function test_getRandomValueFromCorpus_WithEmptyCorpusThrowsException()
	{
		$history = new History();
		$corpus = new Corpus( 'corpus', [] );

		$randomValue = Engine::getRandomValueFromCorpus( $corpus, $history );
	}

	public function test_getRandomValueFromCorpusPool()
	{
		$historyData = 	[
			'colors' => ['red','blue','green'],
			'condiments' => ['mayonnaise', 'aioli'],
			'fruits' => ['blueberry'],
		];
		$history = new History( $historyData );
		$this->assertFalse( $history->hasDomainItem( 'fruits', 'apple' ) );

		$corpusPool[] = new Corpus( 'colors', ['green','red','blue'] );
		$corpusPool[] = new Corpus( 'condiments', ['aioli','mayonnaise'] );
		$corpusPool[] = new Corpus( 'fruits', ['apple','blueberry'] );

		$randomValue = Engine::getRandomValueFromCorpusPool( $corpusPool, $history );
		$this->assertEquals( 'apple', $randomValue );
		$this->assertTrue( $history->hasDomainItem( 'fruits', 'apple' ) );
	}

	/**
	 * @expectedException	UnderflowException
	 */
	public function test_getRandomCorpusFromPool_WithEmptyPoolThrowsException()
	{
		$history = new History();
		$corpusPool = [];

		$randomValue = Engine::getRandomCorpusFromPool( $corpusPool, $history );
	}

	public function provider_isCorpusExhausted() : array
	{
		return
		[
			[
				[
					'condiments' => ['mayonnaise', 'aioli'],
				],
				true
			],
			[
				[
					'condiments' => ['aioli'],
				],
				false
			],
			[
				[], false
			],
		];
	}

	/**
	 * Deterine whether all items in a corpus appear in a given history object
	 *
	 * @dataProvider	provider_isCorpusExhausted
	 */
	public function test_isCorpusExhausted_returnsBool( array $historyData, bool $isCorpusExhausted )
	{
		$history = new History( $historyData );

		$corpusItems = ['aioli','mayonnaise'];
		$corpus = new Corpus( 'condiments', $corpusItems );

		$this->assertEquals( $isCorpusExhausted, Engine::isCorpusExhausted( $corpus, $history ) );
	}

	public function provider_isCorpusPoolExhausted() : array
	{
		return
		[
			[
				[
					'condiments' => ['mayonnaise', 'aioli'],
					'fruits' => ['raisin', 'blueberry'],
				],
				true
			],
			[
				[
					'condiments' => ['mayonnaise', 'aioli'],
					'fruits' => ['apple', 'raisin', 'blueberry'],
				],
				true
			],
			[
				[
					'condiments' => ['mayonnaise', 'aioli'],
					'fruits' => ['apple', 'blueberry'],
				],
				false
			],
			[
				[
					'fruits' => ['raisin', 'blueberry', 'apple'],
				],
				false
			],
			[
				[], false
			],
		];
	}

	/**
	 * Deterine whether all corpora in a pool are exhausted
	 *
	 * @dataProvider	provider_isCorpusPoolExhausted
	 */
	public function test_isCorpusPoolExhausted_returnsBool( array $historyData, bool $isCorpusPoolExhausted )
	{
		$history = new History( $historyData );

		$corpusPool[] = new Corpus( 'condiments', ['aioli','mayonnaise'] );
		$corpusPool[] = new Corpus( 'fruits', ['blueberry','raisin'] );

		$this->assertEquals( $isCorpusPoolExhausted, Engine::isCorpusPoolExhausted( $corpusPool, $history ) );
	}

	public function test_registerFirstNameCorpus()
	{
		$history = new History();
		$engine = new Engine( $history );

		$corpus = new Corpus( 'fruits', ['blueberry'] );
		$engine->registerFirstNameCorpus( $corpus );

		$this->assertEquals( 'blueberry', $engine->getRandomFirstName() );
	}

	public function test_registerLastNameCorpus()
	{
		$history = new History();
		$engine = new Engine( $history );

		$corpus = new Corpus( 'condiments', ['mayonnaise'] );
		$engine->registerLastNameCorpus( $corpus );

		$this->assertEquals( 'mayonnaise', $engine->getRandomLastName() );
	}
}
