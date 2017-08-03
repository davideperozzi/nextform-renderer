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

        $this->node->field->onChange(function () {
            $this->node->update($this);
        });
    }
}
