<?php

/*
 * This file is part of zoostheboss
 */
namespace ZTB;

use PHPUnit\Framework\TestCase;

class CorpusTest extends TestCase
{
	/**
	 * Many of the corpus files in https://github.com/dariusk/corpora use the
	 * same string for their filenames and the JSON element which contains an
	 * array of corpus items (the "domain") — ex., "flowers.json", "flowers: []"
	 * The JSON file-based Corpus factory will treat this as default behavior.
	 */
	public function test_createFromJSONEncodedFile_UsesCorpusFilenameByDefault()
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

		$corpusFileStub
			->method( 'getBasename' )
			->willReturn( 'condiments' );	// This stub method is simulating a
											// SplFileInfo::getBasename('.json')

		$corpus = Corpus::createFromJSONEncodedFile( $corpusFileStub );

		$this->assertEquals( $items, $corpus->getAllItems() );
	}

	/**
	 * Some of the corpus files in https://github.com/dariusk/corpora use
	 * different strings for their filenames and the data domain. The JSON
	 * file-based Corpus factory will support overriding the default domain.
	 */
	public function test_createFromJSONEncodedFile_WithDomainDefined()
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

		$corpusFileStub
			->method( 'getBasename' )
			->willReturn( 'condiments' );	// This stub method is simulating a
											// SplFileInfo::getBasename('.json')

		$corpus = Corpus::createFromJSONEncodedFile( $corpusFileStub, 'toppings' );

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
		$corpusName = 'name-' . microtime( true );
		$corpus = new Corpus( $corpusName, $items );

		$this->assertEquals( $items, $corpus->getAllItems() );
	}

	public function test_getName()
	{
		$corpusName = 'name-' . microtime( true );
		$corpus = new Corpus( $corpusName, [] );

		$this->assertEquals( $corpusName, $corpus->getName() );
	}

	public function test_getRandomItem()
	{
		$items = ['aioli', 'ajvar', 'amba'];
		$corpusName = 'name-' . microtime( true );
		$corpus = new Corpus( $corpusName, $items );

		$this->assertTrue( in_array( $corpus->getRandomItem(), $items ) );
	}
}
