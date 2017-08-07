<?php

namespace Nextform\Renderer\Nodes;

class CollectionNode extends AbstractNode
{
    /**
     * @var array
     */
    public static $tags = ['collection'];

    /**
     * @var [type]
     */
    public static $tag = 'nextform-collection';

    /**
     * @return NodeChunk
     */
    public function render()
    {
        $chunk = parent::render();

        if ($this->hasAttribute('name')) {
            // Transform to valid HTML
            $this->setAttribute('data-name', $this->getAttribute('name'));
            $this->removeAttribute('name');
        }

        return $chunk;
    }
}
