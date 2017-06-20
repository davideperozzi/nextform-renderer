<?php

namespace Nextform\Renderer\Helper;

class IdHelper
{
	public static function real($id) {
		return static::toReal($id);
	}

	/**
	 * @param string $id
	 * @return string
	 */
	public static function fake($id) {
		return static::toFake($id);
	}

	/**
	 * @param string $id
	 * @return string
	 */
	private static function toReal($id) {
	    return strtolower(preg_replace('/([a-zA-Z])(?=[A-Z])/', '$1-', $id));
	}

	/**
	 * @param string $id
	 * @return string
	 */
	private static function toFake($id) {
	    return lcfirst(str_replace('-', '', ucwords($id, '-')));
	}
}