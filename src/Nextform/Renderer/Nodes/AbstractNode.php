<?php

namespace Nextform\Renderer\Nodes;

use Nextform\Fields\AbstractField;
use Nextform\Renderer\Chunks\NodeChunk;
use Nextform\Renderer\Helper\IdHelper;
use Nextform\Renderer\Traversable;

abstract class AbstractNode implements Traversable
{
    /**
     * @var array
     */
    public static $tags = [];

    /**
     * @var string
     */
    public static $tag = '';

    /**
     * @var boolean
     */
    public static $ignoreSelf = false;

    /**
     * @var boolean
     */
    public static $allowShort = true;

    /**
     * @var boolean
     */
    public static $allowChildren = true;

    /**
     * @var string
     */
    public $id = '';

    /**
     * @var AbstractField
     */
    public $field = null;

    /**
     * @var array
     */
    protected $children = [];

    /**
     * @param AbstractField $field
     */
    public function __construct(AbstractField $field)
    {
        $this->field = $field;
        $this->id = $field->id;
    }

    /**
     * @param string $name
     * @param array $parameters
     * @return mixed
     */
    public function __call($name, $parameters)
    {
        if (method_exists($this->field, $name)) {
            return call_user_func_array([&$this->field, $name], $parameters);
        }

        throw new Exception\MethodNotFoundException(
            sprintf('Method "%s" not found', $name)
        );
    }

    /**
     * @param string $name
     * @return AbstractNode
     */
    public function __get($name)
    {
        foreach ($this->children as $child) {
            if ($child->id == IdHelper::real($name)) {
                return $child;
            }
        }

        return null;
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
     * @param AbstractNode $child
     */
    public function append(AbstractNode $child)
    {
        $this->children[] = $child;
    }

    /**
     * @return AbstractField
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * @return string
     */
    public function getAttributeString()
    {
        $content = '';

        foreach ($this->field->getAttributes() as $name => $value) {
            if (substr(trim($value), 0, 1) == '{') {
                $jsonObj = json_decode($value);

                if (json_last_error() == JSON_ERROR_NONE && $jsonObj instanceof \stdClass) {
                    $content .= ' ' . sprintf("%s='%s'", $name, json_encode($jsonObj));
                } else {
                    $content .= ' ' . sprintf('%s="%s"', $name, $value);
                }
            } else {
                $content .= ' ' . sprintf('%s="%s"', $name, $value);
            }
        }

        return trim($content);
    }

    /**
     * @param NodeChunk $chunk
     * @return self
     */
    public function update(NodeChunk $chunk)
    {
        $chunk->set($this->content());

        foreach ($chunk->getChildren() as $child) {
            $child->node->update($child);
        }
    }

    /**
     * @return string
     */
    public function content()
    {
        $content = $this->field->getContent();
        $output = '';

        if (true == static::$ignoreSelf) {
            $output = $content . NodeChunk::CHILDREN_VAR;
        } else {
            $tagName = '';

            if ( ! empty(static::$tag)) {
                $tagName = static::$tag;
            } else {
                $fieldClass = get_class($this->field);
                $tagName = $fieldClass::$tag;
            }

            $attributeStr = $this->getAttributeString();

            if ( ! empty($attributeStr)) {
                $attributeStr = ' ' . $attributeStr;
            }

            if (static::$allowShort && count($this->children) == 0) {
                $output .= sprintf('<%s%s />', $tagName, $attributeStr);
            } else {
                $output .= sprintf(
                    '<%s%s>%s</%s>',
                    $tagName,
                    $attributeStr,
                    $content . NodeChunk::CHILDREN_VAR,
                    $tagName
                );
            }
        }

        return $output;
    }

    /**
     * @return NodeChunk
     */
    public function render()
    {
        $chunk = new NodeChunk($this);
        $output = $this->content();

        $chunk->set($output);

        if (count($this->children) > 0) {
            foreach ($this->children as $child) {
                $chunk->add($child->render());
            }
        }

        return $chunk;
    }
}
