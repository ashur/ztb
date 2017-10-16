<?php

/*
 * This file is part of zoostheboss
 */
namespace ZTB;

use PHPUnit\Framework\TestCase;

class EngineTest extends TestCase
{
	/**
	 * Returns mock of file containing JSON-encoded History data
	 *
	 * @return	\Cranberry\Filesystem\File
	 */
	public function getHistoryFileMock() : \Cranberry\Filesystem\File
	{
		$historyFileMock = $this
			->getMockBuilder( \Cranberry\Filesystem\File::class )
			->disableOriginalConstructor()
			->setMethods( ['getContents','putContents'] )
			->getMock();

		$historyFileMock
			->method( 'getContents' )
			->willReturn( '[]' );

		return $historyFileMock;
	}

	public function provider____filterHyphens() : array
	{
		return [
			['Cinderford', 0, true],
			['Cinderford', 1, true],
			['Electro-Mechanical', 0, false],
			['Electro-Mechanical', 1, true],
			['Bradford-On-Avon', 1, false],
			['Bradford-On-Avon', 2, true],
			['Chapel-En-Le-Frith', 1, false],
			['Chapel-En-Le-Frith', 3, true],
		];
	}

	/**
	 * @dataProvider	provider____filterHyphens
	 */
	public function test____filterHyphens( string $string, int $maxCount, bool $expectedResult )
	{
		$actualResult = Engine::___filterHyphens( $string, $maxCount );
		$this->assertEquals( $expectedResult, $actualResult );
	}

	public function provider____filterSpaces() : array
	{
		return [
			['Cinderford', 0, true],
			['Cinderford', 1, true],
			['Lotus Flower', 0, false],
			['Lotus Flower', 1, true],
			['The Worshipful The Mayor', 2, false],
			['The Worshipful The Mayor', 3, true],
		];
	}

	/**
	 * @dataProvider	provider____filterSpaces
	 */
	public function test____filterSpaces( string $string, int $maxCount, $expectedResult )
	{
		$actualResult = Engine::___filterSpaces( $string, $maxCount );
		$this->assertEquals( $expectedResult, $actualResult );
	}

	public function provider____filterUnwantedWords() : array
	{
		return [
			['Pepsi', ['pepsi','snoopy'], false],
			['Snoopy', ['pepsi','snoopy'], false],
			['Max', ['pepsi','snoopy'], true],
			['gaming change person', ['pepsi','snoopy','\s?person'], false],
		];
	}

	/**
	 * @dataProvider	provider____filterUnwantedWords
	 */
	public function test____filterUnwantedWords( string $string, array $unwantedWords, bool $expectedResult )
	{
		$actualResult = Engine::___filterUnwantedWords( $string, $unwantedWords );
		$this->assertEquals( $expectedResult, $actualResult );
	}

	public function provider_getCorpus() : array
	{
		return [
			['condiments', null],
			['condiments', 'condiments'],
			['toppings', 'toppings'],
		];
	}

	/**
	 * @dataProvider	provider_getCorpus
	 */
	public function test_getCorpus( $domainName, $domainParam )
	{
		$corpusItems = ['aioli', 'ajvar', 'amba'];
		$corpusData = [
			'description'	=> 'A list of condiments',
			$domainName	=> $corpusItems
		];
		$json = json_encode( $corpusData );

		/* Create stubs */
		$corporaDirectoryStub = $this->createMock( \Cranberry\Filesystem\Directory::class );
		$categoryDirectoryStub = $this->createMock( \Cranberry\Filesystem\Directory::class );
		$corpusFileStub = $this->createMock( \Cranberry\Filesystem\File::class );

		/* Wire up method return values */
		$corpusFileStub
			->method( 'exists' )
			->willReturn( true );
		$corpusFileStub
			->method( 'getContents' )
			->willReturn( $json );
		$corpusFileStub
			->method( 'getBasename' )
			->willReturn( 'condiments' );	// This stub method is simulating
											// SplFileInfo::getBasename('.json')

		$categoryDirectoryStub
			->method( 'getChild' )
			->willReturn( $corpusFileStub );

		$corporaDirectoryStub
			->method( 'getChild' )
			->willReturn( $categoryDirectoryStub );

		$historyFileMock = $this->getHistoryFileMock();
		$engine = new Engine( $historyFileMock, $corporaDirectoryStub );

		$corpus = $engine->getCorpus( 'foods', 'condiments', $domainParam );

		$this->assertEquals( $corpusItems, $corpus->getAllItems() );
	}

	/**
	 * @expectedException	InvalidArgumentException
	 */
	public function test_getCorpus_UsingNonExistentCorpusFileThrowsException()
	{
		/* Create stubs */
		$corporaDirectoryStub = $this
			->getMockBuilder( \Cranberry\Filesystem\Directory::class )
			->disableOriginalConstructor()
			->getMock();

		$categoryDirectoryStub = $this
			->getMockBuilder( \Cranberry\Filesystem\Directory::class )
			->disableOriginalConstructor()
			->getMock();

		$corpusFileStub = $this
			->getMockBuilder( \Cranberry\Filesystem\File::class )
			->disableOriginalConstructor()
			->getMock();

		/* Wire up method return values */
		$corpusFileStub
			->method( 'exists' )
			->willReturn( false );

		$categoryDirectoryStub
			->method( 'getChild' )
			->willReturn( $corpusFileStub );

		$corporaDirectoryStub
			->method( 'getChild' )
			->willReturn( $categoryDirectoryStub );

		$historyFileMock = $this->getHistoryFileMock();
		$engine = new Engine( $historyFileMock, $corporaDirectoryStub );
		$engine->getCorpus( 'foo', 'bar' );
	}

	public function test_getPerformerName()
	{
		$historyFileMock = $this->getHistoryFileMock();

		$historyFileMock
			->expects( $this->once() )
			->method( 'putContents' )
			->with( $this->stringContains( 'name_pattern' ) );

		$corporaDirectoryStub = $this
			->getMockBuilder( \Cranberry\Filesystem\Directory::class )
			->disableOriginalConstructor()
			->getMock();

		$engine = new Engine( $historyFileMock, $corporaDirectoryStub );

		$engine->registerFirstNameCorpus( new Corpus( 'fruits', ['blueberry'] ) );
		$engine->registerLastNameCorpus( new Corpus( 'cities', ['Avondale'] ) );

		$nameCandidates[] = 'Blueberry Avondale';
		$nameCandidates[] = 'Blueberry';

		/* Test multiple times to make sure we're not just randomly succeeding */
		for( $i=1; $i<=5; $i++ )
		{
			$this->assertTrue( in_array( $engine->getPerformerName(), $nameCandidates ) );
		}

		$engine->writeHistory();
	}

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

	public function test_getRole()
	{
		$historyFileMock = $this->getHistoryFileMock();
		$corporaDirectoryStub = $this
			->getMockBuilder( \Cranberry\Filesystem\Directory::class )
			->disableOriginalConstructor()
			->getMock();

		$engine = new Engine( $historyFileMock, $corporaDirectoryStub );

		$engine->registerCharacterNameCorpus( new Corpus( 'foobar', ['foo', 'bar-baz'] ) );
		$engine->registerOccupationsCorpus( new Corpus( 'colors', ['red orange yellow', 'green', 'blue-indigo violet'] ) );

		$expectedRoles[] = 'the Red Orange Yellow';
		$expectedRoles[] = 'the Green';
		$expectedRoles[] = 'the Blue-Indigo Violet';
		$expectedRoles[] = 'Foo';
		$expectedRoles[] = 'Bar-Baz';

		sort( $expectedRoles );

		/* Test multiple times to make sure we're not just randomly succeeding */
		$actualRoles = [];
		for( $i=1; $i<=10; $i++ )
		{
			$actualRoles[] = $engine->getRole();
		}

		$actualRoles = array_unique( $actualRoles );
		sort( $actualRoles );

		$this->assertEquals( $expectedRoles, $actualRoles );
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

	public function test_registerCharacterName_CorpusAndFilter()
	{
		$historyFileMock = $this->getHistoryFileMock();
		$corporaDirectoryStub = $this
			->getMockBuilder( \Cranberry\Filesystem\Directory::class )
			->disableOriginalConstructor()
			->getMock();

		$engine = new Engine( $historyFileMock, $corporaDirectoryStub );
		$corpus = new Corpus( 'firstNames', ['Foo', 'Bar Baz'] );

		$engine->registerCharacterNameCorpus( $corpus );
		$engine->registerCharacterNameFilter( [$engine, '___filterSpaces'], [0] );

		/* Test multiple times to make sure we're not just randomly succeeding */
		for( $i=1; $i<5; $i++ )
		{
			$this->assertEquals( 'Foo', $engine->getRandomCharacterName() );
		}
	}

	public function test_registerFirstNameCorpus()
	{
		$historyFileMock = $this->getHistoryFileMock();
		$corporaDirectoryStub = $this
			->getMockBuilder( \Cranberry\Filesystem\Directory::class )
			->disableOriginalConstructor()
			->getMock();

		$engine = new Engine( $historyFileMock, $corporaDirectoryStub );

		$corpus = new Corpus( 'fruits', ['blueberry'] );
		$engine->registerFirstNameCorpus( $corpus );

		$this->assertEquals( 'blueberry', $engine->getRandomFirstName() );
	}

	public function test_registerFirstNameFilter()
	{
		$historyFileMock = $this->getHistoryFileMock();
		$corporaDirectoryStub = $this
			->getMockBuilder( \Cranberry\Filesystem\Directory::class )
			->disableOriginalConstructor()
			->getMock();

		$engine = new Engine( $historyFileMock, $corporaDirectoryStub );
		$corpus = new Corpus( 'hyphen', ['Cinderford','Bradford-On-Avon'] );

		$engine->registerFirstNameCorpus( $corpus );
		$engine->registerFirstNameFilter( [$engine, '___filterHyphens'], [1] );

		/* Test multiple times to make sure we're not just randomly succeeding */
		for( $i=1; $i<5; $i++ )
		{
			$this->assertEquals( 'Cinderford', $engine->getRandomFirstName() );
		}
	}

	public function test_registerGlobalFilter()
	{
		$historyFileMock = $this->getHistoryFileMock();
		$corporaDirectoryStub = $this
			->getMockBuilder( \Cranberry\Filesystem\Directory::class )
			->disableOriginalConstructor()
			->getMock();

		$engine = new Engine( $historyFileMock, $corporaDirectoryStub );
		$corpus = new Corpus( 'first_names', ['Pepsi','Snoopy','Max'] );

		$engine->registerFirstNameCorpus( $corpus );

		$unwantedWords = ['pepsi','snoopy'];
		$engine->registerGlobalFilter( [$engine, '___filterUnwantedWords'], [$unwantedWords] );

		/* Test multiple times to make sure we're not just randomly succeeding */
		for( $i=1; $i<5; $i++ )
		{
			$this->assertEquals( 'Max', $engine->getRandomFirstName() );
		}
	}

	public function test_registerHonorificsCorpus()
	{
		$historyFileMock = $this->getHistoryFileMock();
		$corporaDirectoryStub = $this
			->getMockBuilder( \Cranberry\Filesystem\Directory::class )
			->disableOriginalConstructor()
			->getMock();

		$engine = new Engine( $historyFileMock, $corporaDirectoryStub );

		$corpus = new Corpus( 'honorifics', ['Admiral'] );
		$engine->registerHonorificsCorpus( $corpus );

		$this->assertEquals( 'Admiral', $engine->getRandomHonorific() );
	}

	public function test_registerHonorificsFilter()
	{
		$historyFileMock = $this->getHistoryFileMock();
		$corporaDirectoryStub = $this
			->getMockBuilder( \Cranberry\Filesystem\Directory::class )
			->disableOriginalConstructor()
			->getMock();

		$engine = new Engine( $historyFileMock, $corporaDirectoryStub );
		$corpus = new Corpus( 'hyphen', ['Dr.','Vice Chancellor'] );

		$engine->registerHonorificsCorpus( $corpus );
		$engine->registerHonorificsFilter( [$engine, '___filterSpaces'], [0] );

		/* Test multiple times to make sure we're not just randomly succeeding */
		for( $i=1; $i<5; $i++ )
		{
			$this->assertEquals( 'Dr.', $engine->getRandomHonorific() );
		}
	}

	public function test_registerLastNameCorpus()
	{
		$historyFileMock = $this->getHistoryFileMock();
		$corporaDirectoryStub = $this
			->getMockBuilder( \Cranberry\Filesystem\Directory::class )
			->disableOriginalConstructor()
			->getMock();

		$engine = new Engine( $historyFileMock, $corporaDirectoryStub );

		$corpus = new Corpus( 'condiments', ['mayonnaise'] );
		$engine->registerLastNameCorpus( $corpus );

		$this->assertEquals( 'mayonnaise', $engine->getRandomLastName() );
	}

	public function test_registerLastNameFilter()
	{
		$historyFileMock = $this->getHistoryFileMock();
		$corporaDirectoryStub = $this
			->getMockBuilder( \Cranberry\Filesystem\Directory::class )
			->disableOriginalConstructor()
			->getMock();

		$engine = new Engine( $historyFileMock, $corporaDirectoryStub );
		$corpus = new Corpus( 'hyphen', ['Cinderford','Bradford-On-Avon'] );

		$engine->registerLastNameCorpus( $corpus );
		$engine->registerLastNameFilter( [$engine, '___filterHyphens'], [1] );

		/* Test multiple times to make sure we're not just randomly succeeding */
		for( $i=1; $i<5; $i++ )
		{
			$this->assertEquals( 'Cinderford', $engine->getRandomLastName() );
		}
	}

	public function test_registerOccupationsCorpus()
	{
		$historyFileMock = $this->getHistoryFileMock();
		$corporaDirectoryStub = $this
			->getMockBuilder( \Cranberry\Filesystem\Directory::class )
			->disableOriginalConstructor()
			->getMock();

		$engine = new Engine( $historyFileMock, $corporaDirectoryStub );

		$corpus = new Corpus( 'colors', ['violet'] );
		$engine->registerOccupationsCorpus( $corpus );

		$this->assertEquals( 'violet', $engine->getRandomOccupation() );
	}

	public function test_registerOccupationsFilter()
	{
		$historyFileMock = $this->getHistoryFileMock();
		$corporaDirectoryStub = $this
			->getMockBuilder( \Cranberry\Filesystem\Directory::class )
			->disableOriginalConstructor()
			->getMock();

		$engine = new Engine( $historyFileMock, $corporaDirectoryStub );
		$corpus = new Corpus( 'colors', ['violet','red orange yellow green blue indigo'] );

		$engine->registerOccupationsCorpus( $corpus );
		$engine->registerOccupationsFilter( [$engine, '___filterSpaces'], [4] );

		/* Test multiple times to make sure we're not just randomly succeeding */
		for( $i=1; $i<5; $i++ )
		{
			$this->assertEquals( 'violet', $engine->getRandomOccupation() );
		}
	}

	public function test_registerPerformerPrefix_CorpusAndFilter()
	{
		$historyFileMock = $this->getHistoryFileMock();
		$corporaDirectoryStub = $this
			->getMockBuilder( \Cranberry\Filesystem\Directory::class )
			->disableOriginalConstructor()
			->getMock();

		$engine = new Engine( $historyFileMock, $corporaDirectoryStub );
		$corpus = new Corpus( 'colors', ['violet','red orange yellow green blue indigo'] );

		$engine->registerPerformerPrefixCorpus( $corpus );
		$engine->registerGlobalFilter( [$engine, '___filterSpaces'], [4] );

		/* Test multiple times to make sure we're not just randomly succeeding */
		for( $i=1; $i<5; $i++ )
		{
			$this->assertEquals( 'violet', $engine->getRandomPerformerPrefix() );
		}
	}

	public function test_writeHistory()
	{
		$historyFileMock = $this->getHistoryFileMock();

		$historyFileMock
			->expects( $this->once() )
			->method( 'putContents' )
			->with( $this->equalTo( '[]' ) );

		$corporaDirectoryStub = $this
			->getMockBuilder( \Cranberry\Filesystem\Directory::class )
			->disableOriginalConstructor()
			->getMock();

		$engine = new Engine( $historyFileMock, $corporaDirectoryStub );
		$engine->writeHistory();
	}
}
