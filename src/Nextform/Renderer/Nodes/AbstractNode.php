<?php

namespace Nextform\Renderer\Nodes;

use Nextform\Fields\AbstractField;
use Nextform\Renderer\Helper\IdHelper;
use Nextform\Renderer\Chunks\NodeChunk;
use Nextform\Renderer\Traversable;

abstract class AbstractNode implements Traversable
{
	/**
	 * @var string
	 */
	const UID_PREFIX = 'node_';

	/**
	 * @var array
	 */
	public static $tags = [];

	/**
	 * @var string
	 */
	public static $tag = '';

	/**
	 * @var boolean
	 */
	public static $ignoreSelf = false;

	/**
	 * @var integer
	 */
	protected static $counter = 0;

	/**
	 * @var boolean
	 */
	public static $short = true;

	/**
	 * @var string
	 */
	public $id = '';

	/**
	 * @var array
	 */
	protected $children = [];

	/**
	 * @var AbstractField
	 */
	protected $field = null;

	/**
	 * @param AbstractField $field
	 */
	public function __construct(AbstractField $field) {
		static::$counter++;

		$this->field = $field;
		$this->id = $field->id;
	}

	/**
	 * @param string $name
	 * @param array $parameters
	 * @return mixed
	 */
	public function __call($name, $parameters) {
		if (method_exists($this->field, $name)) {
			return call_user_func_array([&$this->field, $name], $parameters);
		}

		throw new Exception\MethodNotFoundException(
			sprintf('Method "%s" not found', $name)
		);
	}

	/**
	 * @param string $name
	 * @return AbstractNode
	 */
	public function __get($name) {
		foreach ($this->children as $child) {
			if ($child->id == IdHelper::real($name)) {
				return $child;
			}
		}

		return null;
	}

	/**
	 * @return integer
	 */
	public function count() {
		return count($this->children);
	}

	/**
	 * @return \ArrayIterator
	 */
	public function getIterator() {
		return new \ArrayIterator($this->children);
	}

	/**
	 * @return string
	 */
	private static function generateUid() {
		return AbstractNode::UID_PREFIX . static::$counter;
	}

	/**
	 * @param AbstractNode $child
	 */
	public function append(AbstractNode $child) {
		$this->children[] = $child;
	}

	/**
	 * @return AbstractField
	 */
	public function getField() {
		return $this->field;
	}

	/**
	 * @return string
	 */
	public function getAttributeList() {
		$content = '';

		foreach ($this->field->getAttributes() as $name => $value) {
			$content .= ' ' . sprintf('%s="%s"', $name, $value);
		}

		return trim($content);
	}

	/**
	 * @return NodeChunk
	 */
	public function render() {
		$chunk = new NodeChunk($this);
		$content = $this->field->getContent();
		$output = '';

		if (true == static::$ignoreSelf) {
			$output = $content . NodeChunk::CHILDREN_VAR;
		}
		else {
			$tagName = '';

			if ( ! empty(static::$tag)) {
				$tagName = static::$tag;
			}
			else {
				$fieldClass = get_class($this->field);
				$tagName = $fieldClass::$tag;
			}

			if (static::$short && count($this->children) == 0 && empty($fieldContent)) {
				$output .= sprintf('<%s %s />', $tagName, $this->getAttributeList());
			}
			else {
				$output .= sprintf(
					'<%s %s>%s</%s>',
					$tagName,
					$this->getAttributeList(),
					$content . NodeChunk::CHILDREN_VAR,
					$tagName
				);
			}
		}

		$chunk->set($output);

		if (count($this->children) > 0) {
			foreach ($this->children as $child) {
				$chunk->add($child->render());
			}
		}

		return $chunk;
	}
}
