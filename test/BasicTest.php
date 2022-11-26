<?php

namespace Dreitier\Test;

use Dreitier\Nadi\Log\NadiLog;
use Dreitier\Util\Internal\Native;
use Dreitier\WordPress\Multisite\Ui\Actions;
use Dreitier\WordPress\WordPressSiteRepository;
use Mockery;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Basic class for unit tests
 *
 * @author Tobias Hellmann <the@neos-it.de>
 * @access private
 */
abstract class BasicTest extends TestCase
{
	public static function setUpBeforeClass(): void
	{
		NadiLog::$isTestmode = true;
		NadiLog::createLogger();
	}

	public function setUp(): void
	{
		\WP_Mock::setUp();
	}

	public function tearDown(): void
	{
		\WP_Mock::tearDown();
		Mockery::close();
	}

	/**
	 * Create a mocked object using the phpunit MockBuilder.
	 * This method ignores the constructor and will not delegate call to real methods.
	 *
	 * @param $className
	 *
	 * @return MockObject
	 */
	public function createMock($className): MockObject
	{
		if (!class_exists($className) && !interface_exists($className)) {
			echo "You create a new class/interface '$className'. Be careful.";
		}

		return $this->getMockBuilder($className)
			->disableOriginalConstructor()
			->disableProxyingToOriginalMethods()
			->getMock();
	}

	/**
	 * Create a mocked object using the phpunit MockBuilder.
	 * This method ignores the constructor and will not delegate call to real methods.
	 * Furthermore you can add mocked method to this object with the param methods.
	 *
	 * @param $className
	 *
	 * @return MockObject
	 */
	public function createMockWithMethods($className, $methods)
	{
		if (!class_exists($className) && !interface_exists($className)) {
			echo "You create a new class/interface '$className'. Be careful.";
		}

		return $this->getMockBuilder($className)
			->disableOriginalConstructor()
			->disableProxyingToOriginalMethods()
			->setMethods($methods)
			->getMock();
	}

	/**
	 * Simple expected exception behaviour.
	 *
	 * @param $exception
	 * @param $exceptionMessage
	 */
	public function expectExceptionThrown($exception, $exceptionMessage = '')
	{
		$this->expectException($exception, $exceptionMessage);
	}

	/**
	 * Simple behaviour for mock
	 *
	 * @param object $sut
	 * @param string $method method should be executed
	 * @param null $willReturn if not null the value is returned
	 */
	public function behave($sut, $method, $willReturn = null)
	{
		$builder = $sut->expects($this->any())
			->method($method);

		if ($willReturn !== null) {
			$builder->willReturn($willReturn);
		}
	}

	/**
	 * @param $class MockObject
	 * @param $time
	 * @param $method
	 * @param $with
	 * @param $will
	 */
	public function expects($class, $time, $method, $with, $will)
	{
		$class->expects($time)
			->method($method)
			->with($with)
			->willReturn($will);
	}

	/**
	 * Create an anonymous mock which can be fully customized
	 *
	 * @param array|null $methods
	 *
	 * @return MockObject
	 */
	public function createAnonymousMock($methods)
	{
		return $this->getMockBuilder('stdClass')
			->setMethods($methods)
			->getMock();
	}

	/**
	 * Create a mocked object using the phpunit MockBuilder.
	 *
	 * @param $class
	 * @param $constructor
	 * @param $methods
	 *
	 * @return MockObject
	 */
	public function createMockedObject($class, $constructor, $methods)
	{
		return $this->getMockBuilder($class)
			->setConstructorArgs($constructor)
			->setMethods($methods)
			->getMock();
	}

	/**
	 * Make a private or protected method accessible and invoke it.
	 *
	 * @param       $object
	 * @param       $methodName
	 * @param array $parameters
	 *
	 * @return mixed
	 */
	protected function invokeMethod(&$object, $methodName, $parameters = array())
	{
		$reflector = new \ReflectionClass(get_class($object));
		$method = $reflector->getMethod($methodName);
		$method->setAccessible(true);

		return $method->invokeArgs($object, $parameters);
	}

	/**
	 * Mock away the given wordpress function.
	 *
	 * @param       $name
	 * @param array $parameters
	 */
	protected function mockWordpressFunction($name, $parameters = array())
	{
		\WP_Mock::wpFunction($name, $parameters);
	}

	/**
	 * Create a mocked version of Adi_Util_Internal_Native.
	 *
	 * @return \Mockery\MockInterface
	 */
	protected function createMockedNative()
	{
		return $this->getMockBuilder(Native::class)
			->disableOriginalConstructor()
			->getMock();
	}

	/**
	 * Create a mock for the given class using a alias prefix.
	 * This allows us to mock static methods.
	 *
	 * @param $clazzName
	 *
	 * @return \Mockery\MockInterface
	 */
	protected function createUtilClassMock($clazzName)
	{
		return Mockery::mock('alias:' . $clazzName);
	}

	/**
	 * Create a mock for a {@link WP_User}.
	 *
	 * @return \WP_User
	 */
	protected function createWpUserMock()
	{
		$user = $this->createMock('WP_User');
		$user->ID = 2;
		$user->user_login = 'max@test.ad';

		return $user;
	}

	/**
	 * Call the given {@see $callback} and return the fetched result from the output buffer.
	 *
	 * @param $callback
	 *
	 * @return string
	 */
	protected function captureOutput($callback)
	{
		ob_start();
		$callback();
		$result = ob_get_contents();
		ob_end_clean();

		return $result;
	}

	/**
	 * Create a simple mock for the WordPress translation function __.
	 */
	protected function mockFunction__()
	{
		\WP_Mock::wpFunction('__', array(
			'args' => array(\WP_Mock\Functions::type('string'), 'next-active-directory-integration'),
			'times' => '0+',
			'return_arg' => 0
		));
	}

	/**
	 * Create a simple mock for the WordPress translation function esc_html__.
	 */
	protected function mockFunctionEsc_html__()
	{
		\WP_Mock::wpFunction('esc_html__', array(
			'args' => array(\WP_Mock\Functions::type('string'), 'next-active-directory-integration'),
			'times' => '0+',
			'return_arg' => 0
		));
	}
}
