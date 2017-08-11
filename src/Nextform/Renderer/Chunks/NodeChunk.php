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
    public function wrap($content, $beneath = false, $overrideChildren = false)
    {
        // Do not wrap ghost fields
        if ( ! $this->node->field->isGhost()) {
            return parent::wrap($content, $beneath, $overrideChildren);
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function add(AbstractChunk $chunk, $index = -1)
    {
        // Insert ghost chunks always first
        if ($chunk instanceof NodeChunk && $chunk->node->field->isGhost()) {
            $index = 0;
        }

        parent::add($chunk, $index);
    }

    /**
     * {@inheritDoc}
     */
    public function setFrontend($enabled, $recursive = false)
    {
        parent::setFrontend($enabled, $recursive);

        $validations = $this->node->field->getValidation();

        if (true == $this->frontend) {
            foreach ($validations as $model) {
                $this->node->field->setAttribute('data-validator-' . $model->name, $model->value);
                $this->node->field->setAttribute('data-error-' . $model->name, $model->error);
            }
        } else {
            foreach ($validations as $model) {
                $this->node->field->removeAttribute('data-validator-' . $model->name);
                $this->node->field->removeAttribute('data-error-' . $model->name);
            }
        }
    }
}
