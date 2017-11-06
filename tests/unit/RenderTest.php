<?php

namespace Nextform\Parser\Tests;

use Nextform\Config\XmlConfig;
use Nextform\Fields\InputField;
use Nextform\Renderer\Chunks\NodeChunk;
use Nextform\Renderer\NodeBuffer;
use Nextform\Renderer\Nodes\InputNode;
use Nextform\Renderer\Renderer;
use PHPUnit\Framework\TestCase;

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


    public function setUp()
    {
        $this->validConfig = new XmlConfig(realpath(__DIR__ . '/../assets/sample.xml'));
    }

    /**
     * @return Renderer
     */
    private function getRenderer()
    {
        return new Renderer($this->validConfig);
    }

    /**
     * @return NodeBuffer
     */
    private function getOutput()
    {
        return $this->getRenderer()->render();
    }


    public function testRendererCreate()
    {
        $this->assertTrue($this->getRenderer() instanceof Renderer);
    }


    public function testRendererTraversable()
    {
        $renderer = $this->getRenderer();
        $counter = 0;

        $renderer->render()->each(function ($chunk) use (&$counter) {
            $counter++;
        });

        $this->assertEquals($counter, $this->maxFields);
    }

    /**
     * @expectedException Nextform\Renderer\Exception\ChunkNotFoundException
     */
    public function testInalidChunkNodeId()
    {
        $output = $this->getOutput();

        $output->invalidchunkid;
    }


    public function testValidChunk()
    {
        $output = $this->getOutput();

        $this->assertTrue($output->firstname instanceof NodeChunk);
    }


    public function testValidChunkNode()
    {
        $output = $this->getOutput();

        $this->assertTrue($output->firstname->node instanceof InputNode);
    }


    public function testValidChunkNodeId()
    {
        $output = $this->getOutput();

        $this->assertEquals($output->firstname->id, 'firstname');
        $this->assertTrue($output->sepField instanceof NodeChunk);
    }


    public function testChunkType()
    {
        $output = $this->getOutput();

        $this->assertEquals($output->description->id, 'description');
        $this->assertTrue($output->description instanceof NodeChunk);
    }


    public function testChunkSimpleWrap()
    {
        $output = $this->getOutput();
        $output->firstname->wrap('<div class="wrap">%s</div>');

        $this->assertEquals(
            $output->firstname->render(),
            '<div class="wrap"><input type="text" name="firstname" /></div>'
        );

        $output->firstname->wrap('<div class="wrap2">%s</div>');

        $this->assertEquals(
            $output->firstname->render(),
            '<div class="wrap2"><div class="wrap"><input type="text" name="firstname" /></div></div>'
        );
    }

    /**
     * @expectedException Nextform\Renderer\Exception\ChunkNotFoundException
     */
    public function testChunkInvalidIdGroup()
    {
        $output = $this->getOutput();
        $output->group(['firstname', 'invalidid'], function ($chunk) {
        });
    }

    /**
     * @expectedException Nextform\Renderer\Chunks\Exception\NotEnoughChunksException
     */
    public function testChunkTooFewChunksGroup()
    {
        $output = $this->getOutput();
        $output->group(['firstname'], function ($chunk) {
        });
    }


    public function testChunkValidGroupWrap()
    {
        $output = $this->getOutput();

        $output->group(['firstname', 'lastname'], function ($chunk, $content) {
            $chunk->wrap('<div class="group">' . $content . '</div>');
        });

        $output->get([
            ['firstname', 'lastname']
        ])->each(function ($chunk) {
            $this->assertEquals(
                $chunk->render(),
                '<div class="group"><input type="text" name="firstname" /><input type="text" name="lastname" /></div>'
            );
        });
    }


    public function testChunkValidGroupWrapWithoutCallback()
    {
        $output = $this->getOutput();

        $output->group(['firstname', 'lastname']);
        $output->each(function ($chunk, $content) {
            $chunk->wrap('<div>' . $content . '</div>');
        });

        $output->get([
            ['firstname', 'lastname']
        ])->each(function ($chunk) {
            $this->assertEquals(
                $chunk->render(),
                '<div><input type="text" name="firstname" /><input type="text" name="lastname" /></div>'
            );
        });
    }

    public function testCollectionRendering()
    {
        $renderer = new Renderer(new XmlConfig('
            <form>
                <collection name="test">
                    <input type="checkbox" name="test[]" value="test1"/>
                    <input type="checkbox" name="test[]" value="test2"/>
                    <input type="checkbox" name="test[]" value="test3"/>
                </collection>
            </form>
        ', true));

        $this->assertEquals(
            $renderer->render()->test->render(),
            '<nextform-collection data-name="test"><input type="checkbox" name="test[]" value="test1" /><input type="checkbox" name="test[]" value="test2" /><input type="checkbox" name="test[]" value="test3" /></nextform-collection>'
        );
    }

    public function testIgnoreChunkRendering()
    {
        $output = $this->getOutput();

        $output->firstname->ignore(true);
        $this->assertEquals($output->firstname->render(), '');

        $output->firstname->ignore(false);
        $this->assertNotEquals($output->firstname->render(), '');

        $output->ignore(['firstname']);
        $this->assertEquals($output->firstname->render(), '');

        $output->ignore(['firstname'], false);
        $this->assertNotEquals($output->firstname->render(), '');
    }

    public function testTemplateStringRendering()
    {
        $output = $this->getOutput();
        $output->template('
            <div class="firstname-wrapper">{{field:firstname}}</div>
            <div class="lastname-wrapper">{{field:firstname}}</div>
        ');

        $this->assertEquals($output, '<form name="sample" action="test.php" novalidate="true">
            <div class="firstname-wrapper"><input type="text" name="firstname" /></div>
            <div class="lastname-wrapper"><input type="text" name="firstname" /></div>
        </form>');
    }

    public function testFrontendChunkRendering()
    {
        $output = $this->getOutput()->config(['frontend' => true]);

        $this->assertEquals($output->firstname->render(), '<input type="text" name="firstname" data-validator-required="true" data-error-required="This field is required" data-validator-minlength="3" data-error-minlength="Too short. 3 characters at least" />');

        $output->config(['frontend' => false]);

        $this->assertEquals($output->firstname->render(), '<input type="text" name="firstname" />');
    }

    public function testGhostInputRendering()
    {
        $config = new XmlConfig('
            <form>
                <input type="text" name="test" />
            </form>
        ', true);

        $ghostInput = new InputField();
        $ghostInput->setAttribute('name', 'ghost');
        $ghostInput->setGhost(true);

        $config->addField($ghostInput);

        $renderer = new Renderer($config);
        $output = $renderer->render();

        $output->each(function ($chunk) {
            $chunk->wrap('<div>%s</div>');
        });

        $this->assertEquals(
            $output,
            '<form><input name="ghost" /><div><input type="text" name="test" /></div></form>'
        );
    }

    public function testJsonAttributeRendering() {
        $renderer = new Renderer(
            new XmlConfig('
                <form>
                    <input type="text" name="test" data-json=\'{"test":"test"}\' />
                </form>
            ', true)
        );

        $output = $renderer->render();

        $this->assertEquals($output->test->node->getAttribute('data-json'), '{"test":"test"}');
        $this->assertEquals($output->test->render(), '<input type="text" name="test" data-json=\'{"test":"test"}\' />');
    }
}
