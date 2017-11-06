<?php

require realpath(__DIR__ . '/../vendor/autoload.php');

use Nextform\Config\XmlConfig;
use Nextform\Renderer\Renderer;
use Symfony\Component\Yaml\Yaml;

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


$config = new XmlConfig('./assets/sample.xml');
$renderer = new Renderer($config);
$output = $renderer->render()->config([
    'tidy' => [
        'indent' => true,
        'indent-spaces' => 4,
        'wrap' => 400,
        'input-xml' => true,
        'output-xhtml' => true
    ]
]);

echo $output;

// $scheme = Yaml::parse(file_get_contents('./assets/scheme.yml'));

// print_r($scheme);
