<?php

/*
 * This file is part of zoostheboss
 */
namespace ZTB;

use Cranberry\Filesystem\File;

class Corpus
{
	/**
	 * @var	array
	 */
	protected $items=[];

	/**
	 * @param	string	$name
	 *
	 * @param	array	$items
	 *
	 * @return	void
	 */
	public function __construct( array $items )
	{
		$this->items = $items;
	}

	/**
	 * Factory method for creating Corpus object using a file containing
	 * JSON-encoded data
	 *
	 * @param	Cranberry\Filesystem\File	$file
	 *
	 * @param	string	$domain
	 *
	 * @throws	InvalidArgumentException	If file contents cannot be decoded
	 *
	 * @throws	DomainException	If specified domain not found in data
	 *
	 * @return	ZTB\Corpus
	 */
	static public function createFromJSONEncodedFile( File $file, string $domain ) : self
	{
		$data = json_decode( $file->getContents(), true );
		if( json_last_error() != JSON_ERROR_NONE )
		{
			throw new \InvalidArgumentException( "Could not decode '{$file}': " . json_last_error_msg(), json_last_error() );
		}

		if( !array_key_exists( $domain, $data ) )
		{
			throw new \DomainException( "Domain '{$domain}' not found in '{$file}'" );
		}

		return new self( $data[$domain] );
	}

	/**
	 * Returns array of all corpus items
	 *
	 * @return	array
	 */
	public function getAllItems() : array
	{
		return $this->items;
	}

	/**
	 * Returns a random corpus item
	 *
	 * @return	string
	 */
	public function getRandomItem() : string
	{
		$index = array_rand( $this->items );
		return $this->items[$index];
	}
}
