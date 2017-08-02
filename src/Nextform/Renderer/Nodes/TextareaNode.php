<?php

namespace Nextform\Renderer\Nodes;

class TextareaNode extends AbstractNode
{
    /**
     * @var array
     */
    public static $tags = ['textarea'];

     /**
     * @var boolean
     */
    public static $allowChildren = false;

    /**
     * @var boolean
     */
    public static $allowShort = false;
}
