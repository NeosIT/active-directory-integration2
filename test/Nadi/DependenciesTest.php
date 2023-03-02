<?php

namespace Dreitier\Nadi;

use Dreitier\Nadi\Authentication\SingleSignOn\Ui\ShowSingleSignOnLink;
use Dreitier\Nadi\Configuration\Options;
use Dreitier\Nadi\Cron\UrlTrigger;
use Dreitier\Nadi\Multisite\Site\Ui\ExtendSiteList;
use Dreitier\Nadi\Multisite\Ui\MultisiteMenu;
use Dreitier\Nadi\User\Manager;
use Dreitier\Test\BasicTest;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @author Christopher Klein <ckl[at]dreitier[dot]com>
 * @access private
 */
class DependenciesTest extends BasicTest
{
	/**
	 * @test
	 * @issue #176
	 */
	public function GH_176_showSingleSignOnLink_class_isMissing()
	{
		$dependencies = new Dependencies();
		$this->assertTrue($dependencies->getSsoPage() instanceof ShowSingleSignOnLink);
	}
}