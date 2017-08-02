<?php

namespace Nextform\Renderer\Chunks;

use Nextform\Renderer\Nodes\AbstractNode;

class NodeChunk extends AbstractChunk
{
    /**
     * @var AbstractNode
     */
    public $node = null;

    /**
     * @param string $html
     * @param AbstractNode $node
     */
    public function __construct(AbstractNode &$node)
    {
        parent::__construct();

        $this->node = $node;
        $this->id = $node->id;
    }

    /**
     * {@inheritDoc}
     * @throws Exception\NoChunkContentFound
     */
    public function wrap($content, $beneath = false, $overrideChildren = false) {
        if (true == $beneath && false == $this->node::$allowChildren) {
            throw new Exception\ChunkChildrenNotSupported(
                'This chunk does not support children. Wrapping beneath is not possible.'
            );
        }

        return parent::wrap($content, $beneath, $overrideChildren);
    }
}
