<?php

namespace Nextform\Renderer;

use Nextform\Renderer\Chunks\AbstractChunk;
use Nextform\Renderer\Chunks\ChunkCollection;
use Nextform\Renderer\Chunks\GroupChunk;
use Nextform\Renderer\Chunks\NodeChunk;

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
    private $config = [
        'frontend' => false,
        'encoding' => 'utf8',
        'tidy' => []
    ];

    /**
     * @var array
     */
    private $tidy = [];

    /**
     * @var string
     */
    private $encoding = 'utf8';

    /**
     * @var string
     */
    private $template = '';

    /**
     * @param AbstractChunk $root
     */
    public function __construct(AbstractChunk $root)
    {
        $this->root = $root;
    }

    /**
     * @param array
     * @return self
     */
    public function config($config = [])
    {
        if (array_key_exists('frontend', $config) &&
            $config['frontend'] != $this->config['frontend']) {
            $this->root->setFrontend($config['frontend'], true);
        }

        $this->config = array_merge($this->config, $config);

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
     * @param string $strOrFile
     * @return self
     */
    public function template($strOrFile)
    {
        if (is_file($strOrFile)) {
            $this->template = file_get_contents($strOrFile);
        } else {
            $this->template = $strOrFile;
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
     * @param string $name
     * @return AbstractChunk
     */
    public function chunk($name)
    {
        if ($chunk = $this->root->{$name}) {
            return $chunk;
        }

        throw new Exception\ChunkNotFoundException(
            sprintf('Chunk with id "%s" not found', $name)
        );
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
        return $this->chunk($name);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        if ( ! empty($this->template)) {
            $parsedTemplate = $this->template;
            $varSearch = '{{field:%s}}';

            preg_match_all('/' . sprintf($varSearch, '(.*?)') . '/', $this->template, $matches);

            foreach ($matches[1] as $varName) {
                $fields = $this->get($varName);
                $parsedTemplate = str_replace(
                    sprintf($varSearch, $varName),
                    $fields->render(),
                    $parsedTemplate
                );
            }

            $ghostElements = $this->each(function ($chunk) use (&$parsedTemplate) {
                if ($chunk instanceof NodeChunk &&
                    $chunk->node->field->isGhost()) {
                    $parsedTemplate = $chunk->render() . $parsedTemplate;
                }
            });

            try {
                $this->root->wrap($parsedTemplate, true, true);
            } catch (\Exception $exc) {
                trigger_error($exc, E_USER_ERROR);
                return '';
            }
        }

        $output = $this->root->render();

        if ( ! empty($this->config['tidy'])) {
            if ( ! class_exists('\tidy')) {
                throw new Exception\TidyNotFoundException('PHP tidy extension is not installed');
            }

            $tidy = new \tidy;
            $tidy->parseString(
                $output,
                array_merge(['show-body-only' => true], $this->config['tidy']),
                $this->config['encoding']
            );
            $tidy->cleanRepair();

            return (string) $tidy;
        }

        return $output;
    }
}
