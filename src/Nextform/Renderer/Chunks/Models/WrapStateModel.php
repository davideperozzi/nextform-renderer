<?php

namespace Nextform\Renderer\Chunks\Models;

class WrapStateModel
{
    /**
     * @var string
     */
    public $content = '';

    /**
     * @var boolean
     */
    public $beneath = false;

    /**
     * @var boolean
     */
    public $overrideChildren = false;

    /**
     *
     * @param string $content
     * @param boolean beneath
     * @param boolean $overrideChildren
     */
    public function __construct($content, $beneath, $overrideChildren) {
        $this->content = $content;
        $this->beneath = $beneath;
        $this->overrideChildren = $overrideChildren;
    }
}