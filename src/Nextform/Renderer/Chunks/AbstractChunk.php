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
    protected $content = self::CHILDREN_VAR;

    /**
     * @var boolean
     */
    protected $contentInjected = false;

    /**
     * @var array
     */
    protected $children = [];

    /**
     * @var boolean
     */
    protected $ignore = false;

    /**
     * @var array
     */
    protected $wrapStates = [];

    public function __construct()
    {
        if ( ! empty($this->id)) {
            $this->id = static::generateUid();
        }
    }

    /**
     * @return string
     */
    private static function generateUid()
    {
        return self::UID_PREFIX . static::$counter;
    }

    /**
     * @param string $content
     * @return self
     */
    public function set($content)
    {
        $this->content = htmlspecialchars($content);

        return $this;
    }

    /**
     * @return integer
     */
    public function count()
    {
        return count($this->children);
    }

    /**
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->children);
    }

    /**
     * @param callable $callback
     * @return self
     */
    public function each(callable $callback)
    {
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
    public function add(AbstractChunk $chunk, $index = -1)
    {
        if ($index > -1) {
            array_splice($this->children, $index, 0, [$chunk]);
        } else {
            $this->children[] = $chunk;
        }

        return $this;
    }

    /**
     * @return string
     */
    public function render()
    {
        if (true == $this->ignore) {
            return '';
        }

        $content = $this->applyWrap($this->content);

        return htmlspecialchars_decode(
            str_replace(self::CHILDREN_VAR, $this->children(), $content)
        );
    }

    /**
     * @param boolean $ignore
     * @return self
     */
    public function ignore($ignore)
    {
        $this->ignore = $ignore;
    }

    /**
     * @param string $id
     * @return AbstractChunk
     */
    public function __get($id)
    {
        foreach ($this->children as $child) {
            if ($child->id == IdHelper::real($id)) {
                return $child;
            }
        }

        return null;
    }

    /**
     * @param string $content
     * @return string
     */
    private function applyWrap($content)
    {
        foreach ($this->wrapStates as $state) {
            $contentActive = preg_match('/' . self::CONTENT_VAR . '/', $state->content);
            $sprintfActive = preg_match('/%s/', $state->content);

            if ( ! $contentActive && ! $sprintfActive && ! $state->overrideChildren) {
                throw new Exception\NoChunkContentFound(
                    'You need to define the place in which the content will be rendered'
                );
            }

            $replaceContent = htmlspecialchars($state->content);

            if (true == $state->beneath) {
                $searchStr = '%s';

                if ($contentActive) {
                    $searchStr = self::CONTENT_VAR;
                }

                $replaceContent = str_replace($searchStr, self::CHILDREN_VAR, $replaceContent);
                $content = str_replace(
                    self::CHILDREN_VAR,
                    $replaceContent,
                    $content
                );
            } else {
                if ($contentActive) {
                    $content = str_replace(
                        self::CONTENT_VAR,
                        $content,
                        $replaceContent
                    );
                } elseif ($sprintfActive) {
                    $content = sprintf($replaceContent, $content);
                }
            }
        }

        return $content;
    }

    /**
     * @param string $content
     * @param boolean $beneath
     * @param boolean $overrideChildren
     * @throws Exception\NoChunkContentFound
     * @return self
     */
    public function wrap($content, $beneath = false, $overrideChildren = false)
    {
        $this->wrapStates[] = new Models\WrapStateModel(
            $content,
            $beneath,
            $overrideChildren
        );

        return $this;
    }

    /**
     * @param AbstractChunk $child
     * @return integer
     */
    public function remove(AbstractChunk $child)
    {
        $index = array_search($child, $this->children);

        if ($index >= 0) {
            array_splice($this->children, $index, 1);
        }

        return $index;
    }

    /**
     * @return string
     */
    protected function children()
    {
        $content = '';

        foreach ($this->children as $child) {
            $content .= $child->render();
        }

        return $content;
    }

    /**
     * @return array
     */
    public function getChildren()
    {
        return $this->children;
    }
}
