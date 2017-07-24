<?php

namespace Nextform\Renderer;

use Nextform\Config\AbstractConfig;

class Renderer
{
    /**
     * @var array
     */
    public static $ctors = [
        __NAMESPACE__ . '\Nodes\FormNode',
        __NAMESPACE__ . '\Nodes\InputNode',
        __NAMESPACE__ . '\Nodes\SelectNode',
        __NAMESPACE__ . '\Nodes\OptionNode',
        __NAMESPACE__ . '\Nodes\TextareaNode',
        __NAMESPACE__ . '\Nodes\CollectionNode'
    ];

    /**
     * @var array
     */
    private $ctorCache = [];

    /**
     * @var AbstractConfig
     */
    private $config = null;

    /**
     * @var Nodes\AbstractNode
     */
    private $root = null;

    /**
     * @param AbstractConfig $config
     */
    public function __construct(AbstractConfig $config)
    {
        $this->config = $config;

        // Create root node
        $rootField = $this->config->getFields()->getRoot();
        $nodeCtor = $this->getNodeCtor($rootField::$tag);
        $this->root = new $nodeCtor($rootField);

        // Add field nodes
        $this->createNodes();
    }

    /**
     * @param string $name
     * @return AbstractNode
     */
    public function __get($name)
    {
        if ($node = $this->root->{$name}) {
            return $node;
        }

        throw new Exception\NodeNotFoundException(
            sprintf('Node with name "%s" not found', $name)
        );
    }

    /**
     * @param string $tag
     * @return string
     */
    private function getNodeCtor($tag)
    {
        if (array_key_exists($tag, $this->ctorCache)) {
            return $this->ctorCache[$tag];
        }

        foreach (static::$ctors as $ctor) {
            if (in_array($tag, $ctor::$tags)) {
                return $this->ctorCache[$tag] = $ctor;
            }
        }

        return null;
    }

    /**
     * @param AbstractNode $node
     */
    private function appendChildren(&$node)
    {
        $field = $node->getField();

        if ($field->hasChildren()) {
            foreach ($field->getChildren() as $child) {
                $childNode = $this->createNode($child);

                $node->append($childNode);
            }
        }
    }

    /**
     * @param AbstractField $field
     * @throws Exception\NodeNotFoundException if not node renderer was found
     * @return AbstractNode
     */
    private function createNode(&$field)
    {
        $ctor = $this->getNodeCtor($field::$tag);

        if ($ctor) {
            $node = new $ctor($field);

            $this->appendChildren($node);

            return $node;
        }

        throw new Exception\NodeNotFoundException(
                sprintf('Node for tag "%s" not found', $field::$tag)
            );


        return null;
    }


    private function createNodes()
    {
        foreach ($this->config->getFields() as $field) {
            $node = $this->createNode($field);

            if ( ! is_null($node)) {
                $this->root->append($node);
            }
        }
    }

    /**
     * @return NodeBuffer
     */
    public function render()
    {
        return new NodeBuffer($this->root->render());
    }
}
