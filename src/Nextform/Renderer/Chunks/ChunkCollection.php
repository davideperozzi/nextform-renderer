<?php

namespace Nextform\Renderer\Chunks;

use Nextform\Renderer\Traversable;

class ChunkCollection implements Traversable
{
	/**
	 * @var array
	 */
	private $chunks = [];

	/**
	 * @param array $chunks
	 */
	public function __construct($chunks = []) {
		$this->chunks = $chunks;
	}

	/**
	 * @param AbstractChunk $chunk
	 * @return self
	 */
	public function add(AbstractChunk $chunk) {
		$this->chunks[] = $chunk;

		return $this;
	}

	/**
	 * @return integer
	 */
	public function count() {
		return count($this->chunks);
	}

	/**
	 * @return \ArrayIterator
	 */
	public function getIterator() {
		return new \ArrayIterator($this->chunks);
	}

	/**
	 * @param callable $callback
	 * @return self
	 */
	public function each(callable $callback) {
		foreach ($this->chunks as $i => $chunk) {
			$callback($chunk, AbstractChunk::CONTENT_VAR);
		}

		return $this;
	}
}