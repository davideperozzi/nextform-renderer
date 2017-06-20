<?php

namespace Nextform\Renderer\Chunks;

use Nextform\Renderer\Helper\IdHelper;
use Nextform\Renderer\Traversable;

abstract class AbstractChunk implements Traversable
{
	/**
	 * @var string
	 */
	const UID_PREFIX = 'chunk_';

	/**
	 * @var string
	 */
	const CHILDREN_VAR = '{__children__}';

	/**
	 * @var string
	 */
	const CONTENT_VAR = '{__content__}';

	/**
	 * @var integer
	 */
	private static $counter = 0;

	/**
	 * @var string
	 */
	public $id = '';

	/**
	 * @var string
	 */
	protected $content = AbstractChunk::CHILDREN_VAR;

	/**
	 * @var array
	 */
	protected $children = [];

	/**
	 *
	 */
	public function __construct() {
		if ( ! empty($this->id)) {
			$this->id = static::generateUid();
		}
	}

	/**
	 * @return string
	 */
	private static function generateUid() {
		return AbstractChunk::UID_PREFIX . static::$counter;
	}

	/**
	 * @param string $content
	 * @return self
	 */
	public function set($content) {
		$this->content = htmlspecialchars($content);

		return $this;
	}

	/**
	 * @return integer
	 */
	public function count() {
		return count($this->children);
	}

	/**
	 * @return \ArrayIterator
	 */
	public function getIterator() {
		return new \ArrayIterator($this->children);
	}

	/**
	 * @param callable $callback
	 * @return self
	 */
	public function each(callable $callback) {
		foreach ($this->children as $i => $child) {
			$callback($child, $i);
		}

		return $this;
	}

	/**
	 * @param AbstractChunk $chunk
	 * @param integer $index
	 * @return self
	 */
	public function add(AbstractChunk $chunk, $index = -1) {
		if ($index > -1) {
			array_splice($this->children, $index, 0, [$chunk]);
		}
		else {
			$this->children[] = $chunk;
		}

		return $this;
	}

	/**
	 * @return string
	 */
	public function get() {
		return htmlspecialchars_decode(
			str_replace(AbstractChunk::CHILDREN_VAR, $this->children(), $this->content)
		);
	}

	/**
	 * @param string $id
	 * @return AbstractChunk
	 */
	public function __get($id) {
		foreach ($this->children as $child) {
			if ($child->id == IdHelper::real($id)) {
				return $child;
			}
		}

		return null;
	}

	/**
	 * @param string $content
	 * @throws Exception\NoChunkContentFound
	 * @return self
	 */
	public function wrap($content) {
		$contentActive = preg_match('/' . AbstractChunk::CONTENT_VAR . '/', $content);
		$sprintfActive = preg_match('/%s/', $content);

		if ( ! $contentActive && ! $sprintfActive) {
			throw new Exception\NoChunkContentFound(
				'You need to define the place in which the content will be rendered'
			);
		}

		$replaceContent = htmlspecialchars($content);

		if ($contentActive) {
			$this->content = str_replace(
				AbstractChunk::CONTENT_VAR,
				$this->content,
				$replaceContent
			);
		}
		else if ($sprintfActive) {
			$this->content = sprintf($replaceContent, $this->content);
		}

		return $this;
	}

	/**
	 * @param AbstractChunk $child
	 * @return integer
	 */
	public function remove(AbstractChunk $child) {
		$index = array_search($child, $this->children);

		if ($index >= 0) {
			array_splice($this->children, $index, 1);
		}

		return $index;
	}

	/**
	 * @return string
	 */
	protected function children() {
		$content = '';

		foreach ($this->children as $child) {
			$content .= $child->get();
		}

		return $content;
	}
}