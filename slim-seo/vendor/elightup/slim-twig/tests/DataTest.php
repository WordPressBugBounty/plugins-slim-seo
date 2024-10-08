<?php
use PHPUnit\Framework\TestCase;
use SlimTwig\Data;

class DataTest extends TestCase {
	public function testKeyIsSingle() {
		$data  = [
			'foo' => 'bar',
		];
		$this->assertSame( Data::get( $data, 'foo' ), 'bar' );
	}

	public function testKeyIsMulti() {
		$data  = [
			'foo' => [
				'bar' => 'baz',
			],
		];
		$this->assertSame( Data::get( $data, 'foo.bar' ), 'baz' );
	}

	public function testKeyIsObject() {
		$data  = [
			'foo' => (object) [
				'bar' => 'baz',
				'pax' => 'vax',
			],
		];
		$this->assertSame( Data::get( $data, 'foo.bar' ), 'baz' );
	}

	public function testKeyIsObjectObject() {
		$data  = (object) [
			'foo' => (object) [
				'bar' => [
					'baz' => 'baz value',
				],
				'pax' => 'vax',
			],
		];
		$this->assertSame( Data::get( $data, 'foo.bar.baz' ), 'baz value' );
	}

	public function testKeyNotExists() {
		$data  = (object) [
			'foo' => (object) [
				'bar' => [
					'baz' => 'baz value',
				],
				'pax' => 'vax',
			],
		];
		$this->assertSame( Data::get( $data, 'foo.bar.pax' ), null );
	}
}
