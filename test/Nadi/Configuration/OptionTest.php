<?php

namespace Dreitier\Nadi\Configuration;

use Dreitier\Ldap\Attribute\Description;
use Dreitier\Nadi\Configuration\Options;
use Dreitier\Test\BasicTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * @author Christopher Klein <ckl[at]dreitier[dot]com>
 * @access private
 */
class OptionTest extends BasicTestCase
{
	/**
	 * @since #209
	 */
	#[Test]
	public function GH_209_optionAttributes_allowLazyLoadingOfDescriptionAndTitle()
	{
		$title = 'some title';
		$description = 'some description';

		$sut = Option::make([
			'title' => fn() => $title,
			'description' => fn() => $description,
		]);

		$this->assertEquals($title, $sut['title']);
		$this->assertEquals($description, $sut['description']);
	}

	/**
	 * @since #209
	 */
	#[Test]
	public function GH_209_optionAttributes_canBeStillConfiguredAsStringsOrArrays()
	{
		$validSelectOptions = ['value'];
		$internalNameValue = 'my_internal_option_name';

		$sut = Option::make([
			'valid_select_options' => $validSelectOptions,
			'internal_name' => $internalNameValue,
		]);

		$this->assertEquals($validSelectOptions, $sut['valid_select_options']);
		$this->assertEquals($internalNameValue, $sut['internal_name']);
	}

	/**
	 * @since #209
	 */
	#[Test]
	public function GH_209_optionAttributes_areImmutable()
	{
		$sut = Option::make([
			'key' => 'value',
		]);

		$this->expectExceptionMessageMatches("/immutable/");
		$sut['key'] = 'new_value';
	}
}