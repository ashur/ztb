<?php

/*
 * This file is part of zoostheboss
 */
namespace ZTB;

use PHPUnit\Framework\TestCase;

class EngineTest extends TestCase
{
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
		$engine = new Engine();

		$history = new History( $historyData );

		$corpusItems = ['aioli','mayonnaise'];
		$corpus = new Corpus( 'condiments', $corpusItems );

		$this->assertEquals( $isCorpusExhausted, $engine->isCorpusExhausted( $corpus, $history ) );
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
		$engine = new Engine();

		$history = new History( $historyData );

		$corpusPool[] = new Corpus( 'condiments', ['aioli','mayonnaise'] );
		$corpusPool[] = new Corpus( 'fruits', ['blueberry','raisin'] );

		$this->assertEquals( $isCorpusPoolExhausted, $engine->isCorpusPoolExhausted( $corpusPool, $history ) );
	}
}
