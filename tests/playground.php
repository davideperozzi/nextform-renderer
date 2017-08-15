<?php

require realpath(__DIR__ . '/../vendor/autoload.php');

use Nextform\Config\XmlConfig;
use Nextform\Fields\InputField;
use Nextform\Renderer\Renderer;

$config = new XmlConfig('
	<form name="sample-form" action="" novalidate="true">
		<input type="text" name="firstname" placeholder="Firstname">
			<validation required="true" minlength="3">
				<errors>
					<minlength>Too short. %s characters at least</minlength>
				</errors>
			</validation>
		</input>
		<input type="text" name="lastname" placeholder="Lastname">
			<validation required="true" maxlength="10" minlength="3">
				<errors>
					<maxlength>Too long. %s characters is maximum</maxlength>
					<minlength>Too short. %s characters at least</minlength>
				</errors>
			</validation>
		</input>
		<textarea name="description">
			<validation required="true" maxlength="200">
				<errors>
					<required>Textarea required</required>
				</errors>
			</validation>
		</textarea>
		<collection name="test">
			<input type="checkbox" name="test" value="test1"/>
			<input type="checkbox" name="test" value="test2"/>
			<input type="checkbox" name="test" value="test3"/>
            <validation required="true">
                <modifiers required-min="5"></modifiers>
                <errors>
                    <required>This field is required</required>
                </errors>
            </validation>
		</collection>
		<select name="price">
			<options>
				<option value="p1">5€</option>
				<option value="p2">15€</option>
				<option value="p3">150€</option>
			</options>
		</select>
		<input type="text" name="test1" placeholder="Test 1"></input>
		<input type="text" name="test2" placeholder="Test 2"></input>
		<input type="reset" name="reset-sample" />
		<input type="submit" name="submit-sample" />
		<defaults>
			<validation>
				<errors>
					<required>This field is required</required>
					<maxlength>Default maxlength error</maxlength>
				</errors>
			</validation>
		</defaults>
	</form>
', true);


$ghostField = new InputField();
$ghostField->setAttribute('name', 'ghost');
$ghostField->setGhost(true);

$config->addField($ghostField);

$renderer = new Renderer($config);
$output = $renderer->render()->config([
    'frontend' => true,
    'tidy' => [
        'indent' => true,
        'indent-spaces' => 4,
        'wrap' => 400,
        'input-xml' => true,
        'output-xhtml' => true
    ]
]);

// $output->each(function ($chunk, $content) {
//     $chunk->wrap('<div class="input-wrapper" style="display: inline-block">' . $content . '</div>');
// });

// $output->submitSample->wrap('<div class="submit-btn-wrapper" style="display: inline-block">%s</div>');

// $output->group(
//     ['firstname', 'lastname'],
//     function ($chunk, $content) {
//         $chunk->wrap('<div class="group-wrapper">' . $content . '</div>');
//     }
// );

// $output->group(
//     ['price', 'description'],
//     function ($chunk, $content) {
//         $chunk->wrap('<div class="group-wrapper">' . $content . '</div>');
//     }
// );

// $output->group(
//     [
//         ['firstname', 'lastname'],
//         ['price', 'description']
//     ],
//     function ($chunk, $content) {
//         $chunk->wrap('<div class="group-wrapper">' . $content . '</div>');
//     }
// );

// $output->group(['test1', 'test2'], function ($chunk, $content) {
//     $chunk->wrap('<div class="test-wrapper">%s</div>');
// });

// $output->group(
//     [
//         [
//             ['firstname', 'lastname'],
//             ['price', 'description']
//         ],
//         ['test1', 'test2']
//     ],
//     function ($chunk, $content) {
//         $chunk->wrap('<div class="whole-wrapper">' . $content . '</div>');
//     }
// );

// $output->get(
//     [
//         [
//             [
//                 ['firstname', 'lastname'],
//                 ['price', 'description']
//             ],
//             ['test1', 'test2']
//         ]
//     ]
// )->each(function ($chunk, $content) {
//     $chunk->wrap('<div class="after-group-wrap">' . $content . '</div>');
// });

// $output->group(['submit-sample', 'reset-sample'], function ($chunk, $content) {
//     $chunk->wrap('<div class="button-wrapper">' . $content . '</div>');
// });

// $output->flush();

// echo '<hr>';
// echo '<pre>';

// print_r($_GET);

$template = '
    <div class="form-inner-wrapper">
        <div>{{field:lastname}}</div>
        <div>{{field:firstname}}</div>
        <div>{{field:test}}</div>
    </div>
';

$output->price->wrap('<div class="option-wrapper">%s</div>', true);
$output->template($template)->each(function ($chunk) {
    $chunk->node->setAttribute('test', 1);
    $chunk->wrap('<div class="input-wrapper">%s</div>');
});

echo $output;
