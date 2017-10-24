<?php

/*
 * This file is part of ZTB
 */
namespace ZTB;

use Cranberry\Filesystem\File;
use Imagick;

class Image
{
	const HEIGHT = 450;
	const WIDTH = 600;

	/**
	 * @var	array
	 */
	protected $filters=[];

	/**
	 * @var	Imagick
	 */
	protected $image;

	/**
	 * @param	Cranberry\Filesystem\File	$file
	 *
	 * @return	void
	 */
	public function __construct( File $imageFile )
	{
		$this->image = new Imagick( $imageFile->getPathname() );

		$this->pushFilter( ['self', '___filterCorrectOrientation'] );
		$this->pushFilter( ['self', '___filterResize'] );
		$this->pushFilter( ['self', '___filterCrop'] );
	}

	/**
	 * Blurs image
	 *
	 * @param	Imagick	$image	Passed by reference
	 *
	 * @return	Imagick
	 */
	static public function ___filterBlur( Imagick &$image )
	{
		$image->gaussianBlurImage( 2, 2 );
	}

	/**
	 * Blurs image on individual channels
	 *
	 * @param	Imagick	$image	Passed by reference
	 *
	 * @return	Imagick
	 */
	static public function ___filterChannelBlur( Imagick &$image )
	{
		$radius = 2;
		$image->motionBlurImage( $radius, 1, 10, Imagick::COLOR_CYAN );
		$image->motionBlurImage( $radius, 3, 20, Imagick::COLOR_MAGENTA );
		$image->motionBlurImage( $radius, 4, 80, Imagick::COLOR_YELLOW );
		$image->motionBlurImage( $radius, 1, 40, Imagick::COLOR_BLACK );
	}

	/**
	 * Colorizes image
	 *
	 * @param	Imagick	$image	Passed by reference
	 *
	 * @return	Imagick
	 */
	static public function ___filterColorize( Imagick &$image )
	{
		$opacity = 0.27;
		$opacityHex = sprintf( '%02s', dechex( floor( $opacity * 255 ) ) );

		$overlay = new Imagick();
		$overlayPattern = "gradient:#fca15e{$opacityHex}-#f07e2b{$opacityHex}";
		$overlayPattern = "gradient:#a7b355{$opacityHex}-#f07e2b{$opacityHex}";

		$overlay->newPseudoImage( self::WIDTH, self::HEIGHT, $overlayPattern );

		$image->compositeImage( $overlay, Imagick::COMPOSITE_OVERLAY, 0, 0 );
	}

	/**
	 * Rotates image to match orientation defined in EXIF data
	 *
	 * @param	Imagick	$image	Passed by reference
	 *
	 * @return	Imagick
	 */
	static public function ___filterCorrectOrientation( Imagick &$image )
	{
		switch( $image->getImageOrientation() )
		{
			case Imagick::ORIENTATION_LEFTTOP:
			case Imagick::ORIENTATION_RIGHTTOP:
				$degrees = 90;
				break;

			case Imagick::ORIENTATION_BOTTOMLEFT:
			case Imagick::ORIENTATION_BOTTOMRIGHT:
				$degrees = 180;
				break;

			case Imagick::ORIENTATION_LEFTBOTTOM:
			case Imagick::ORIENTATION_RIGHTBOTTOM:
				$degrees = 270;
				break;

			default:
				$degrees = 0;
				break;
		}

		$image->rotateImage( '#00000000', $degrees );
		$image->setImageOrientation( Imagick::ORIENTATION_TOPLEFT );
	}

	/**
	 * Crops image to 600 x 450, centered horizontally and vertically
	 *
	 * @param	Imagick	$image	Passed by reference
	 *
	 * @return	Imagick
	 */
	static public function ___filterCrop( Imagick &$image )
	{
		$xOffset = ($image->getImageWidth() - self::WIDTH) / 2;
		$yOffset = ($image->getImageHeight() - self::HEIGHT) / 2;

		$image->cropImage( self::WIDTH, self::HEIGHT, $xOffset, $yOffset );
	}

	/**
	 * Resizes image to 600 x 450
	 *
	 * @param	Imagick	$image	Passed by reference
	 *
	 * @return	Imagick
	 */
	static public function ___filterResize( Imagick &$image )
	{
		$image->resizeImage( self::WIDTH, self::HEIGHT, Imagick::FILTER_CATROM, 1 );
	}

	/**
	 * Adjusts image saturation and contrast
	 *
	 * @param	Imagick	$image	Passed by reference
	 *
	 * @return	Imagick
	 */
	static public function ___filterSaturate( Imagick &$image )
	{
		$image->modulateImage( 100, 110, 100 );
		$image->contrastImage( 0 );
	}

	/**
	 * Processes filters queue
	 *
	 * @return	void
	 */
	public function applyFilters()
	{
		foreach( $this->filters as $filter )
		{
			call_user_func_array( $filter, [&$this->image] );
		}
	}

	/**
	 * Writes image data to target file
	 *
	 * @param	Cranberry\Filesystem\File	$targetFile
	 *
	 * @return	void
	 */
	public function exportTo( File $targetFile )
	{
		if( !$targetFile->exists() )
		{
			$targetFile->create( true );
		}

		$this->image->writeImages( $targetFile->getPathname(), true );
	}

	/**
	 * Pushes filter onto the end of the filters queue
	 *
	 * @param	Callable	$filter
	 *
	 * @return	void
	 */
	public function pushFilter( $filter )
	{
		array_push( $this->filters, $filter );
	}
}
