<?php

/*
 * This file is part of zoostheboss
 */
namespace ZTB;

use PHPUnit\Framework\TestCase;

class HistoryTest extends TestCase
{
	/**
	 * @return	array
	 */
	public function domainItemProvider() : array
	{
		return [
			['domain-' . microtime( true ), 'value-' . microtime( true )]
		];
	}

	/**
	 * @dataProvider	domainItemProvider
	 */
	public function test_addDomainItem( $domainName, $domainItem )
	{
		$history = new History();

		$this->assertFalse( $history->hasDomainItem( $domainName, $domainItem ) );

		$history->addDomainItem( $domainName, $domainItem );
		$this->assertTrue( $history->hasDomainItem( $domainName, $domainItem ) );
	}

	public function test_createFromJSONEncodedFile()
	{
		$domainName = 'domain-' . microtime( true );
		$domainItem = 'item-' . microtime( true );

		$data = [ $domainName => [$domainItem] ];
		$json = json_encode( $data );

		$historyFileStub = $this->createMock( \Cranberry\Filesystem\File::class );
		$historyFileStub
			->method( 'getContents' )
			->willReturn( $json );

		$history = History::createFromJSONEncodedFile( $historyFileStub );

		$this->assertTrue( $history->hasDomainItem( $domainName, $domainItem ) );
	}

	/**
	 * @expectedException	InvalidArgumentException
	 */
	public function test_createFromJSONEncodedFile_WithInvalidJSONThrowsException()
	{
		$historyFileStub = $this->createMock( \Cranberry\Filesystem\File::class );
		$historyFileStub
			->method( 'getContents' )
			->willReturn( 'this is not valid JSON ' . microtime( true ) );

		$history = History::createFromJSONEncodedFile( $historyFileStub );
	}

	public function test_getAllDomainItems()
	{
		$domainName = 'domain-' . microtime( true );
		$data = [$domainName => ['item1', 'item2'] ];

		$history = new History( $data );

		$this->assertEquals( $data[$domainName], $history->getAllDomainItems( $domainName ) );
	}

	/**
	 * @expectedException	DomainException
	 */
	public function test_getAllDomainItems_OfUndefinedDomainThrowsException()
	{
		$history = new History();

		$domainName = 'domain-' . microtime( true );
		$history->getAllDomainItems( $domainName );
	}

	public function test_hasDomainReturnsBool()
	{
		$domainName = 'domain-' . microtime( true );
		$data = [$domainName => ['item1', 'item2'] ];

		$history = new History();
		$this->assertFalse( $history->hasDomain( $domainName ) );

		$history = new History( $data );
		$this->assertTrue( $history->hasDomain( $domainName ) );
	}

	public function test_hasDomainItemReturnsBool()
	{
		$domainName = 'domain-' . microtime( true );
		$itemName = 'item-' . microtime( true );
		$data = [$domainName => [$itemName, 'item2'] ];

		$history = new History( $data );
		$this->assertTrue( $history->hasDomainItem( $domainName, $itemName ) );
		$this->assertFalse( $history->hasDomainItem( $domainName, 'item3' ) );
	}

	/*
	 * Rather than throw a DomainException, which adds more error handling and
	 * complexity to code using History, it's sufficient to just return false
	 * when neither a domain nor an item exist.
	 */
	public function test_hasDomainItem_OfUndefinedDomainReturnsFalse()
	{
		$data = ['domain' => ['item1', 'item2'] ];
		$history = new History( $data );
		$this->assertFalse( $history->hasDomainItem( 'domain2', 'item1' ) );
	}

	/**
	 * @expectedException	DomainException
	 */
	public function test_removeDomain_WithUndefinedDomainThrowsException()
	{
		$history = new History();
		$history->removeDomain( 'domain-' . microtime( true ) );
	}

	public function test_removeDomain()
	{
		$history = new History();

		$domainName = 'domain-' . microtime( true );

		$history->addDomainItem( $domainName, 'item-' . microtime( true ) );
		$this->assertTrue( $history->hasDomain( $domainName ) );

		$history->removeDomain( 'domain-' . microtime( true ) );
		$this->assertFalse( $history->hasDomain( $domainName ) );
	}

	public function test_writeToFile_UsingWritableFile()
	{
		$history = new History();

		$domainName = 'domain-' . microtime( true );
		$domainItem = 'item-' . microtime( true );

		$data = [$domainName => [$domainItem]];
		$expectedContents = json_encode( $data );

		$history->addDomainItem( $domainName, $domainItem );

		$historyFileMock = $this
			->getMockBuilder( \Cranberry\Filesystem\File::class )
			->disableOriginalConstructor()
			->setMethods( ['putContents'] )
			->getMock();

		$historyFileMock
			->expects( $this->once() )
			->method( 'putContents' )
			->with( $this->equalTo( $expectedContents ) );

		$history->writeToFile( $historyFileMock );
	}
}
