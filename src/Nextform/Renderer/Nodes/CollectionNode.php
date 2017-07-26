<?php

namespace Nextform\Renderer\Nodes;

class CollectionNode extends AbstractNode
{
    /**
     * @var string
     */
    const COLLECTION_TAG = 'nextform-collection';

    /**
     * @var array
     */
    public static $tags = ['collection'];

    /**
     * @var boolean
     */
    public static $ignoreSelf = true;

    /**
     * @return NodeChunk
     */
    public function render() {
        $chunk = parent::render();

        if ($this->hasAttribute('name')) {
            $name = $this->getAttribute('name');
            $element = sprintf(
                '<%s data-name="%s">%s</%s>',
                self::COLLECTION_TAG, $name, '%s', self::COLLECTION_TAG
            );

            $chunk->wrap($element);
        }

        return $chunk;
    }
}
