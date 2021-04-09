<?php
namespace SimpleJwtLoginTests;

class AuthCodeBuilderTest  extends \PHPUnit\Framework\TestCase{

	/**
	 * @dataProvider AuthCodeBuilderArrayProvider
	 * @param array $data
	 * @param array $expected
	 */
	public function testBuilderFromArray($data, $expected){
		$builder = new \SimpleJWTLogin\Modules\AuthCodeBuilder($data);
		$this->assertSame($expected, $builder->toArray());
		$this->assertSame($expected['code'], $builder->getCode());
		$this->assertSame($expected['role'], $builder->getRole());
		$this->assertSame($expected['expiration_date'], $builder->getExpirationDate());
	}

	public function AuthCodeBuilderArrayProvider() {
		return[
			0 => [
				'data' => [
					'code' => '',
					'role' => '',
					'expiration_date' => '',
				],
				'expected' => [
					'code'=> '',
					'role' => '',
					'expiration_date' => '',
				]
			],
			1 => [
				'data' => [
					'code' => '123',
					'role' => '',
					'expiration_date' => '',
				],
				'expected' => [
					'code'=> "123",
					'role' => '',
					'expiration_date' => '',
				]
			],
			2 => [
				'data' => [
					'code' => '123',
					'role' => 'administrator',
					'expiration_date' => '2020-01-01 10:00:00',
				],
				'expected' => [
					'code'=> "123",
					'role' => 'administrator',
					'expiration_date' => '2020-01-01 10:00:00',
				]
			]
		];
	}

	/**
	 * @dataProvider AuthCodeBuilderStringProvider
	 * @param array $data
	 * @param array $expected
	 */
	public function testBuilderFromString($data, $expected){
		$builder = new \SimpleJWTLogin\Modules\AuthCodeBuilder($data);
		$this->assertSame($expected, $builder->toArray());
	}

	public function AuthCodeBuilderStringProvider() {
		return [
			0 => [
				'data' => '',
				'expected' => [
					'code'=> '',
					'role' => '',
					'expiration_date' => '',
				]
			],
			1 => [
				'data' => '1',
				'expected' => [
					'code'=> '1',
					'role' => '',
					'expiration_date' => '',
				]
			],
			2 => [
				'data' => null,
				'expected' => [
					'code'=> '',
					'role' => '',
					'expiration_date' => '',
				]
			],
		];
	}


}
