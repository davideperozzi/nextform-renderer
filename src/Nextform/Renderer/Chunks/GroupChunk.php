<?php

namespace Nextform\Renderer\Chunks;

class GroupChunk extends AbstractChunk
{
	/**
	 * @var string
	 */
	const CHUNK_ID_SEPERATOR = '_';

	/**
	 * @param array $chunks
	 */
	public function __construct($chunks) {
		parent::__construct();

		if (count($chunks) < 2) {
			throw new Exception\NotEnoughChunksException(
				sprintf(
					'You need at lease 2 chunks to create a group. %s given',
					count($chunks)
				)
			);
		}

		$this->id = '';

		foreach ($chunks as $chunk) {
			$this->children[] = $chunk;

			$this->id .= (!empty($this->id) ? GroupChunk::CHUNK_ID_SEPERATOR : '');
			$this->id .= $chunk->id;
		}

	}
}