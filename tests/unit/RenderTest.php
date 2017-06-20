<?php

namespace Nextform\Parser\Tests;

use PHPUnit\Framework\TestCase;
use Nextform\Config\XmlConfig;
use Nextform\Renderer\Renderer;
use Nextform\Renderer\NodeBuffer;
use Nextform\Renderer\Nodes\InputNode;
use Nextform\Renderer\Nodes\AbstractNode;
use Nextform\Renderer\Chunks\NodeChunk;
use Nextform\Renderer\Chunks\AbstractChunk;

class RenderTest extends TestCase
{
	/**
	 * @var integer
	 */
	private $maxFields = 5;

	/**
	 * @var XmlConfig
	 */
	private $validConfig = null;

	/**
	 *
	 */
	public function setUp() {
		$this->validConfig = new XmlConfig(realpath(__DIR__ . '/../assets/sample.xml'));
	}

	/**
	 * @return Renderer
	 */
	private function getRenderer() {
		return new Renderer($this->validConfig);
	}

	/**
	 * @return NodeBuffer
	 */
	private function getOutput() {
		return $this->getRenderer()->render();
	}

	/**
	 *
	 */
	public function testRendererCreate() {
		$this->assertTrue($this->getRenderer() instanceof Renderer);
	}

	/**
	 *
	 */
	public function testRendererTraversable() {
		$renderer = $this->getRenderer();
		$counter = 0;

		$renderer->render()->each(function($chunk) use (&$counter) {
			$counter++;
		});

		$this->assertEquals($counter, $this->maxFields);
	}

	/**
	 * @expectedException Nextform\Renderer\Exception\ChunkNotFoundException
	 */
	public function testInalidChunkNodeId() {
		$output = $this->getOutput();

		$output->invalidchunkid;
	}

	/**
	 *
	 */
	public function testValidChunk() {
		$output = $this->getOutput();

		$this->assertTrue($output->firstname instanceof NodeChunk);
	}

	/**
	 *
	 */
	public function testValidChunkNode() {
		$output = $this->getOutput();

		$this->assertTrue($output->firstname->node instanceof InputNode);
	}

	/**
	 *
	 */
	public function testValidChunkNodeId() {
		$output = $this->getOutput();

		$this->assertEquals($output->firstname->id, 'firstname');
		$this->assertTrue($output->sepField instanceof NodeChunk);
	}

	/**
	 *
	 */
	public function testChunkType() {
		$output = $this->getOutput();

		$this->assertEquals($output->description->id, 'description');
		$this->assertTrue($output->description instanceof NodeChunk);
	}

	/**
	 *
	 */
	public function testUndefinedIdAutomation() {
		$output = $this->getOutput();
		$automated = false;

		$output->each(function($chunk) use (&$automated){
			if (substr($chunk->id, 0, strlen(AbstractNode::UID_PREFIX)) === AbstractNode::UID_PREFIX) {
				$automated = true;
			}
		});

		$this->assertTrue($automated);
	}

	/**
	 *
	 */
	public function testChunkSimpleWrap() {
		$output = $this->getOutput();
		$output->firstname->wrap('<div class="wrap">%s</div>');

		$this->assertEquals(
			$output->firstname->get(),
			'<div class="wrap"><input type="text" name="firstname" /></div>'
		);

		$output->firstname->wrap('<div class="wrap2">%s</div>');

		$this->assertEquals(
			$output->firstname->get(),
			'<div class="wrap2"><div class="wrap"><input type="text" name="firstname" /></div></div>'
		);
	}

	/**
	 * @expectedException Nextform\Renderer\Exception\ChunkNotFoundException
	 */
	public function testChunkInvalidIdGroup() {
		$output = $this->getOutput();
		$output->group(['firstname', 'invalidid'], function($chunk){});
	}

	/**
	 * @expectedException Nextform\Renderer\Chunks\Exception\NotEnoughChunksException
	 */
	public function testChunkTooFewChunksGroup() {
		$output = $this->getOutput();
		$output->group(['firstname'], function($chunk){});
	}

	/**
	 *
	 */
	public function testChunkValidGroupWrap() {
		$output = $this->getOutput();

		$output->group(['firstname', 'lastname'], function($chunk, $content){
			$chunk->wrap('<div class="group">' . $content . '</div>');
		});

		$output->get([['firstname', 'lastname']])->each(function($chunk){
			$this->assertEquals(
				$chunk->get(),
				'<div class="group"><input type="text" name="firstname" /><input type="text" name="lastname" /></div>'
			);
		});
	}
}