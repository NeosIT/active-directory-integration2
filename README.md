# Next Active Directory Integration
Next Active Directory Integration allows WordPress to authenticate, authorize, create and update users against Microsoft Active Directory. Next ADI ist a complete rewrite of its predecessor Active Directory Integration](https://wordpress.org/plugins/active-directory-integration/). You can easily import users from your Active Directory into your WordPress instance and keep both synchronized through Next Active Directory Integration's features

## Running
You can download the ready-to-use version from [WordPress.org](https://wordpress.org/plugins/next-active-directory-integration) or from the [SVN repository](https://plugins.svn.wordpress.org/next-active-directory-integration) hosted by WordPress.org.

## Running (for developers)
This Git repository has to be cloned inside the *wp-content/plugins* directory of your WordPress environment.

After the cloning you have to update the dependencies with help of *Composer* (execute `composer install` inside the cloned repository folder).
To install composer follow the instructions on [https://getcomposer.org/download/](https://getcomposer.org/download/).
	
### Testing
Tests are made with PHPUnit. Get PHPUnit with

	# get PHPUnit
	wget https://phar.phpunit.de/phpunit.phar

#### Running unit tests

 	cd active-directory-integration2
	# run unit tests with default PHPUnit configuration
	php path/to/phpunit.phar --testsuite "unit" --configuration phpunit.xml

#### Running integration tests 

	cd active-directory-integration2
	# running integration test against a local install Active Directory instance
	# executing the ITs with PHP binary is required for of passing environment variables to the test
	php -d AD_ENDPOINT=127.0.0.1 -d AD_PORT=389 -d AD_USERNAME=username@domain.com -d AD_PASSWORD=Password -d AD_USE_TLS='' -d AD_SUFFIX=@domain.com -d AD_BASE_DN='DC=domain,DC=com' path/to/phpunit.phar --testsuite "integration" --no-coverage

#### Running all tests

	cd active-directory-integration2
	# running integration test against a local install Active Directory instance
	# executing the ITs with PHP binary is required for of passing environment variables to the test
	php -d AD_ENDPOINT=127.0.0.1 -d AD_PORT=389 -d AD_USERNAME=username@domain.com -d AD_PASSWORD=Password -d AD_USE_TLS='' -d AD_SUFFIX=@domain.com -d AD_BASE_DN='DC=domain,DC=com' path/to/phpunit.phar --no-coverage

#### Running all tests in PhpStorm
	
	Run > Edit Configurations > Defaults > PHPUnit
	
	Test Runner options: --test-suffix Test.php,IT.php
	Interpreter options: -d AD_ENDPOINT=127.0.0.1 -d AD_PORT=389 -d AD_USERNAME=Administrator -d AD_PASSWORD=Pa$$w0rd -d AD_USE_TLS='' -d AD_SUFFIX=@test.ad -d AD_BASE_DN='DC=test,DC=ad'

### Continuous Integration
NADI uses Ant for an easier CI process. The *build.xml* supports different targets. The main targets are

 - full-build: execute static analysis, PHPUnit tests, documentation
 - quick-build: linting, PHPUnit tests
 - static-analysis: linting, loc, pdepend, phpcs, phpcpd

More specialized tasks are

 - phploc-ci: Lines of Code
 - pdepend: Calculating software metrics with PHP_Depend
 - phpcs-ci: Find coding violations using PHP_CodeSniffer
 - phpcpd-ci: Find duplicate code using PHPCPD
 - phpunit: Run unit tests
 - phpdox: Create documentation

You can provide the variable *php* (*-Dphp=path-to-php-binary*) and *pdepend*, *phpcpd*, *phpcs*, *phpdox*, *phploc*, *phpunit* to configure the tool paths:

	ant -Dphp=/usr/bin/php-5.6.15 -Dpdepend=/opt/php-env/ci/pdepend.phar -Dphpcpd=/opt/php-env/ci/phpcpd.phar
	

