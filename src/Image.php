<?php

/*
 * This file is part of ZTB
 */
namespace ZTB;

use Cranberry\Filesystem\File;
use Imagick;

class Image
{
	const CROP_HEIGHT = 450;
	const CROP_WIDTH = 600;

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

		$this->pushFilter( ['ZTB\Image', '___filterCrop'] );
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
		$xOffset = ($image->getImageWidth() - self::CROP_WIDTH) / 2;
		$yOffset = ($image->getImageHeight() - self::CROP_HEIGHT) / 2;

		$image->cropImage( self::CROP_WIDTH, self::CROP_HEIGHT, $xOffset, $yOffset );
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
