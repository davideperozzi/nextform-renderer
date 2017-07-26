<?php

namespace Nextform\Renderer;

use Nextform\Renderer\Chunks\AbstractChunk;
use Nextform\Renderer\Chunks\ChunkCollection;
use Nextform\Renderer\Chunks\GroupChunk;

class NodeBuffer
{
    /**
     * @var integer
     */
    const GROUP_APPEND_LOWEST = 1;

    /**
     * @var integer
     */
    const GROUP_APPEND_HIGHEST = 2;

    /**
     * @var AbstractChunk
     */
    public $root = null;

    /**
     * @var array
     */
    private $tidy = [];

    /**
     * @var string
     */
    private $encoding = 'utf8';

    /**
     * @param AbstractChunk $root
     */
    public function __construct(AbstractChunk $root)
    {
        $this->root = $root;
    }

    /**
     * @return self
     */
    public function config($tidy = [], $encoding = 'utf8')
    {
        $this->tidy = $tidy;
        $this->encoding = $encoding;

        return $this;
    }

    /**
     * @param callable $callback
     * @return self
     */
    public function each(callable $callback)
    {
        foreach ($this->root as $chunk) {
            $callback($chunk, AbstractChunk::CONTENT_VAR);
        }

        return $this;
    }

    /**
     * @param array $ids
     * @param callable $callback
     * @return self
     */
    public function group($ids, callable $callback = null, $appendType = self::GROUP_APPEND_LOWEST)
    {
        $indices = [];
        $chunks = $this->get($ids);

        foreach ($chunks as $chunk) {
            $indices[] = $this->root->remove($chunk);
        }

        $group = new GroupChunk($chunks);

        $this->root->add(
            $group,
            $appendType == self::GROUP_APPEND_LOWEST ? min($indices) : max($indices)
        );

        if (is_callable($callback)) {
            $callback($group, AbstractChunk::CONTENT_VAR);
        }

        return $this;
    }

    /**
     * @param array $ids
     * @return string
     */
    private function getGroupId($ids)
    {
        $outer = [];

        foreach ($ids as $id) {
            if (is_array($id)) {
                $outer[] = $this->getGroupId($id);
            } else {
                $outer[] = $id;
            }
        }

        return implode($outer, GroupChunk::CHUNK_ID_SEPERATOR);
    }


    public function flush()
    {
        echo (string) $this;
    }

    /**
     * @param array $selector
     * @return ChunkCollection
     */
    public function get($selector)
    {
        $collection = new ChunkCollection();

        if (is_string($selector)) {
            $collection->add($this->{$selector});
        } else {
            foreach ($selector as $ids) {
                if (is_array($ids)) {
                    $collection->add($this->{$this->getGroupId($ids)});
                } else {
                    $collection->add($this->{$ids});
                }
            }
        }

        return $collection;
    }

    /**
     * @param array $selector
     * @param boolean $ignore
     * @return self
     */
    public function ignore($selector, $ignore = true)
    {
        $chunks = $this->get($selector);

        foreach ($chunks as $chunk) {
            $chunk->ignore($ignore);
        }

        return $this;
    }

    /**
     * @param string $name
     * @return AbstractChunk
     */
    public function __get($name)
    {
        if ($chunk = $this->root->{$name}) {
            return $chunk;
        }

        throw new Exception\ChunkNotFoundException(
            sprintf('Chunk with id "%s" not found', $name)
        );
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $output = $this->root->get();

        if ( ! empty($this->tidy)) {
            if ( ! class_exists('\tidy')) {
                throw new Exception\TidyNotFoundException('PHP tidy extension is not installed');
            }

            $tidy = new \tidy;
            $tidy->parseString(
                $output,
                array_merge(['show-body-only' => true], $this->tidy),
                $this->encoding
            );
            $tidy->cleanRepair();

            return (string) $tidy;
        }

        return $output;
    }
}
