<?php

/*
 * This file is part of zoostheboss
 */
namespace ZTB;

use Cranberry\Filesystem;
use Imagick;
use PHPUnit\Framework\TestCase;

class ImageTest extends TestCase
{
	/**
	 * @param	string	$filename
	 *
	 * @return	Cranberry\Filesystem\File
	 */
	public function getFixtureFile( string $filename ) : Filesystem\File
	{
		$fixturesPath = dirname( __DIR__ ) . '/fixtures';
		$fixturesDirectory = new Filesystem\Directory( $fixturesPath );

		$imageFile = $fixturesDirectory->getChild( $filename, Filesystem\Node::FILE );
		return $imageFile;
	}

	/**
	 * @param	string	$filename
	 *
	 * @return	Cranberry\Filesystem\File
	 */
	public function getTempFile( string $filename ) : Filesystem\File
	{
		$tempPath = dirname( __DIR__ ) . '/tmp';
		$tempDirectory = new Filesystem\Directory( $tempPath );

		$tempFile = $tempDirectory->getChild( $filename, Filesystem\Node::FILE );
		return $tempFile;
	}

	/**
	 * @return	Cranberry\Filesystem\File
	 */
	public function getImageFileStub() : Filesystem\File
	{
		$imageFileStub = $this
			->createMock( Filesystem\File::class );

		return $imageFileStub;
	}

	public function test___filterCrop()
	{
		$sourceImageFile = $this->getFixtureFile( '700x550.png' );
		$sourceImage = new Imagick( $sourceImageFile->getPathname() );

		$this->assertEquals( 550, $sourceImage->getImageHeight() );
		$this->assertEquals( 700, $sourceImage->getImageWidth() );

		Image::___filterCrop( $sourceImage );

		$this->assertEquals( Image::CROP_HEIGHT, $sourceImage->getImageHeight() );
		$this->assertEquals( Image::CROP_WIDTH, $sourceImage->getImageWidth() );
	}

	public function test_filterQueueContainsCropByDefault()
	{
		$sourceImageFile = $this->getFixtureFile( '700x550.png' );
		$filteredImageFile = $this->getTempFile( microtime( true ) . '.png' );

		$sourceImage = new Image( $sourceImageFile );

		$this->assertFalse( $filteredImageFile->exists() );

		$sourceImage->applyFilters();
		$sourceImage->exportTo( $filteredImageFile );

		$this->assertTrue( $filteredImageFile->exists() );

		$filteredImage = new Imagick( $filteredImageFile->getPathname() );

		$this->assertEquals( Image::CROP_HEIGHT, $filteredImage->getImageHeight() );
		$this->assertEquals( Image::CROP_WIDTH, $filteredImage->getImageWidth() );
	}

	static public function tearDownAfterClass()
	{
		$tempPath = dirname( __DIR__ ) . '/tmp';
		$tempDirectory = new Filesystem\Directory( $tempPath );

		$tempDirectory->delete();
	}
}
