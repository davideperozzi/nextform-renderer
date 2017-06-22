<?php

namespace Nextform\Renderer\Nodes;

class CollectionNode extends AbstractNode
{
	/**
	 * @var array
	 */
	public static $tags = ['collection'];

	/**
	 * @var boolean
	 */
	public static $ignoreSelf = true;
}