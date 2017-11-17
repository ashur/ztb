<?php

/*
 * This file is part of zoostheboss
 */
namespace ZTB;

use Cranberry\Filesystem;
use ImagickDraw;
use PHPUnit\Framework\TestCase;

class TextTest extends TestCase
{
	/**
	 * @var	Cranberru\Filesystem\Directory
	 */
	protected $fixturesDirectory;

	public function getFixturesDirectory() : Filesystem\Directory
	{
		if( $this->fixturesDirectory == null )
		{
			$fixturesPathname = sprintf( '%s/fixtures', dirname( __DIR__ ) );
			$this->fixturesDirectory = new Filesystem\Directory( $fixturesPathname );
		}

		return $this->fixturesDirectory;
	}

	public function getFontFile() : Filesystem\File
	{
		$fontFile = $this
			->getFixturesDirectory()
			->getChild( 'slkscr.ttf' );

		return $fontFile;
	}

	public function getFontFileMock() : Filesystem\File
	{
		$fontFileMock = $this->getMockBuilder( Filesystem\File::class )
			->disableOriginalConstructor()
			->setMethods( ['exists','getPathname'] )
			->getMock();

		return $fontFileMock;
	}

	public function getImagickDrawMock() : ImagickDraw
	{
		$imagickDrawMock = $this->getMockBuilder( ImagickDraw::class )
			->disableOriginalConstructor()
			->setMethods( ['setFillColor','setFont'] )
			->getMock();

		return $imagickDrawMock;
	}

	/**
	 * @expectedException	ZTB\Exception\RuntimeException
	 * @expectedExceptionCode	ZTB\Exception\RuntimeException::FILESYSTEM_NODE_NOT_FOUND
	 */
	public function test___construct_withNonExistentFontFile_throwsException()
	{
		$imagickDrawMock = $this->getImagickDrawMock();
		$fontFileMock = $this->getFontFileMock();
		$fontFileMock
			->method( 'exists' )
			->willReturn( false );

		$text = new Text( $imagickDrawMock, $fontFileMock, '#f8d535' );
	}

	public function test___construct_setsFont_usingFontFilePathname()
	{
		$fontFilePathname = (string) microtime( true );

		$imagickDrawMock = $this->getImagickDrawMock();
		$imagickDrawMock
			->expects( $this->once() )
			->method( 'setFont' )
			->with( $fontFilePathname );

		$fontFileMock = $this->getFontFileMock();
		$fontFileMock
			->method( 'exists' )
			->willReturn( true );
		$fontFileMock
			->method( 'getPathname' )
			->willReturn( $fontFilePathname );

		$text = new Text( $imagickDrawMock, $fontFileMock, '#f8d535' );
	}

	public function test___construct_setsFontColor_usingFontColorString()
	{
		$fontColor = sprintf( '#%s', substr( sha1( microtime( true ) ), 0, 6 ) );

		$imagickDrawMock = $this->getImagickDrawMock();
		$imagickDrawMock
			->expects( $this->once() )
			->method( 'setFillColor' )
			->with( $fontColor );

		$fontFileMock = $this->getFontFileMock();
		$fontFileMock
			->method( 'exists' )
			->willReturn( true );

		$text = new Text( $imagickDrawMock, $fontFileMock, $fontColor );
	}

	public function test_getImagickDraw()
	{
		$imagickDraw = new ImagickDraw();
		$fontFile = $this->getFontFile();
		$expectedFontColor = sprintf( 'srgb(%s,%s,%s)', rand(0,255), rand(0,255), rand(0,255) );

		$text = new Text( $imagickDraw, $fontFile, $expectedFontColor );

		$actualFontColor = $text
			->getImagickDraw()
			->getFillColor()
			->getColorAsString();

		$this->assertEquals( $expectedFontColor, $actualFontColor );
	}
}
