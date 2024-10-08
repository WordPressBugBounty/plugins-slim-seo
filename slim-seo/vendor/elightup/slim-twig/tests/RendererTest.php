<?php
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use SlimTwig\Renderer;

class RendererTest extends TestCase {
	#[DataProvider('providerTestRender')]
	public function testRender( $originalContent, $expectedResult ) {
		$data = [
			'plugin' => 'SlimTwig',
			'person' => [
				'first' => [
					'name' => 'World',
				],
			],
		];

		$result = Renderer::render( $originalContent, $data );
		$this->assertSame( $expectedResult, $result );
	}

	public static function providerTestRender() {
		return [
			[
				'Hello World. Check out new plugin SlimTwig', // no variable
				'Hello World. Check out new plugin SlimTwig',
			],
			[
				'Hello {{ plugin }}', // single variable
				'Hello SlimTwig',
			],
			[
				'Hello {{ person.first }}. Check out new plugin {{ plugin }}{{ not_exists }}', // Array and not exists variable
				'Hello Array. Check out new plugin SlimTwig',
			],
			[
				'Hello {{ person.first.name }}. { this is something new }, or {{ is a broken brackets',
				'Hello World. { this is something new }, or {{ is a broken brackets',
			],
		];
	}
}
