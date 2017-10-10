<?php

/*
 * This file is part of zoostheboss
 */
namespace ZTB;

use PHPUnit\Framework\TestCase;

class CorpusTest extends TestCase
{
	public function test_createFromJSONEncodedFile()
	{
		$items = ['aioli', 'ajvar', 'amba'];
		$data = [
			'description'	=> 'A list of condiments',
			'condiments'	=> $items
		];
		$json = json_encode( $data );

		$corpusFileStub = $this->createMock( \Cranberry\Filesystem\File::class );
		$corpusFileStub
			->method( 'getContents' )
			->willReturn( $json );

		$corpus = Corpus::createFromJSONEncodedFile( $corpusFileStub, 'condiments' );

		$this->assertEquals( $items, $corpus->getAllItems() );
	}

	/**
	 * @expectedException	DomainException
	 */
	public function test_createFromJSONEncodedFile_WithInvalidDomainThrowsException()
	{
		$items = ['aioli', 'ajvar', 'amba'];
		$data = [
			'description'	=> 'A list of condiments',
			'toppings'	=> $items
		];
		$json = json_encode( $data );

		$corpusFileStub = $this->createMock( \Cranberry\Filesystem\File::class );
		$corpusFileStub
			->method( 'getContents' )
			->willReturn( $json );

		$corpus = Corpus::createFromJSONEncodedFile( $corpusFileStub, 'condiments', 'garnishes' );
	}

	/**
	 * @expectedException	InvalidArgumentException
	 */
	public function test_createFromJSONEncodedFile_WithInvalidJSONThrowsException()
	{
		$corpusFileStub = $this->createMock( \Cranberry\Filesystem\File::class );
		$corpusFileStub
			->method( 'getContents' )
			->willReturn( 'this is not valid JSON ' . microtime( true ) );

		$corpus = Corpus::createFromJSONEncodedFile( $corpusFileStub, 'condiments' );
	}

	public function test_getAllItems()
	{
		$items = ['aioli', 'ajvar', 'amba'];
		$corpus = new Corpus( $items );

		$this->assertEquals( $items, $corpus->getAllItems() );
	}

	public function test_getRandomItem()
	{
		$items = ['aioli', 'ajvar', 'amba'];
		$corpus = new Corpus( $items );

		$this->assertTrue( in_array( $corpus->getRandomItem(), $items ) );
	}
}
