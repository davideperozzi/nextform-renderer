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

    /**
     * {@inheritDoc}
     */
    public function setFrontend($enabled, $recursive = false)
    {
        parent::setFrontend($enabled, $recursive);

        if (true == $this->frontend) {
            $validations = $this->node->field->getValidation();

            foreach ($validations as $model) {
                $this->node->field->setAttribute('data-validator-' . $model->name, $model->value);
                $this->node->field->setAttribute('data-error-' . $model->name, $model->error);
            }
        }

        // @todo: Remove frontend validation attributes.
        // This needs the removeAttribute method implemented in the field.
    }
}
