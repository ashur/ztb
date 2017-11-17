<?php

/*
 * This file is part of ZTB
 */
namespace ZTB;

use Cranberry\Filesystem\File;
use ImagickDraw;

class Text
{
	const ERROR_STRING_INVALIDFONT='Invalid font file %s: %s.';

	/**
	 * @var	ImagickDraw
	 */
	protected $imagickDraw;

	/**
	 * @param	ImagickDraw	$imagickDraw
	 *
	 * @param	Cranberry\Filesystem\File	$fontFile
	 *
	 * @param	string	$fontColor
	 *
	 * @return	void
	 */
	public function __construct( ImagickDraw $imagickDraw, File $fontFile, string $fontColor )
	{
		if( !$fontFile->exists() )
		{
			$exceptionMessage = sprintf( self::ERROR_STRING_INVALIDFONT, $fontFile->getPathname(), 'No such file' );
			$exceptionCode = Exception\RuntimeException::FILESYSTEM_NODE_NOT_FOUND;

			throw new Exception\RuntimeException( $exceptionMessage, $exceptionCode );
		}

		$imagickDraw->setFont( $fontFile->getPathname() );
		$imagickDraw->setFillColor( $fontColor );

		$this->imagickDraw = $imagickDraw;
	}

	/**
	 * Returns ImagickDraw object
	 *
	 * @return	ImagickDraw
	 */
	public function getImagickDraw() : ImagickDraw
	{
		return $this->imagickDraw;
	}
}
