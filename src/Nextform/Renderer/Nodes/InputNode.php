<?php

namespace Nextform\Renderer\Nodes;

class InputNode extends AbstractNode
{
    /**
     * @var array
     */
    public static $tags = ['input'];

    /**
     * @var boolean
     */
    public static $allowChildren = false;
}
