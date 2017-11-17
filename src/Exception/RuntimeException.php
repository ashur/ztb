<?php

/*
 * This file is part of ZTB
 */
namespace ZTB\Exception;

class RuntimeException extends Exception
{
	const FILESYSTEM_PERMISSION_DENIED = 1;
	const FILESYSTEM_NODE_NOT_FOUND = 2;
}
