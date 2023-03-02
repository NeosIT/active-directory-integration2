# You are on the 2.x branch which is no longer maintained

# Next Active Directory Integration
Next Active Directory Integration allows WordPress to authenticate, authorize, create and update users against Microsoft Active Directory. Next ADI ist a complete rewrite of its predecessor [Active Directory Integration](https://wordpress.org/plugins/active-directory-integration/). You can easily import users from your Active Directory into your WordPress instance and keep both synchronized through Next Active Directory Integration's features.

If you like this plug-in we'd like to encourage you to purchase a support plan from [https://active-directory-wp.com/](https://active-directory-wp.com/shop-overview/) to support the ongoing development of this plug-in.

## Important requirement changes
As of *2022-11-28* NADI does *no* longer support PHP versions in *7.x* branch. The reason is that security support for PHP version prior 8.x have been dropped by the maintainers as you can see in the official [PHP documentation](http://php.net/supported-versions.php).
For security reasons and in order to use NADI in 2023 we hereby politely encourage you to migrate your environments to at least PHP 8.0 until then.

Thank you all for your support and understanding.

## Getting Started
You can download the ready-to-use version from the [WordPress.org Plugin Directory](https://wordpress.org/plugins/next-active-directory-integration) or from the [SVN repository](https://plugins.svn.wordpress.org/next-active-directory-integration) hosted by WordPress.org.

If you are developer, you can also clone this Git repository inside the *wp-content/plugins* directory of your WordPress environment.

After the cloning you have to update the dependencies with help of *Composer* (execute `composer install` inside the cloned repository folder).
To install composer follow the instructions on [https://getcomposer.org/download/](https://getcomposer.org/download/).

## Development
### Contributing
- Pull requests and changes must be made against the `develop` or a dedicated `feature` or `bugfix` branch. Name the branches either `bugfix/${GITHUB_ISSUE_NUMBER}` or `feature/${GITHUB_ISSUE_NUMBER}`.
- Any changes must be added in the `UNRELEASED` section of the `readme.txt` for documentation purposes.

### Commit messages
1. We are using [conventional commits](https://www.conventionalcommits.org/en/v1.0.0/) since mid 2022.
2. Git commits should be signed-off (`git commit -s -m "..."`).

Use the following commit message format:

```bash
fix: some fix for an existing GitHub issue (#123)
chore: some dependency update
``` 

### Release process
When releasing a new version, the changes from `develop` must be merged to `main`. 
The `main` version must be tagged accordingly (`${MAJOR}.${MINOR}.${BUGFIX}`) and then pushed. A new tag is automatically tested, gets then pushed to wordpress.org and will be automatically released.

To create a new release, do the following:

1. `git checkout main && git merge develop`
2. Switch the `= UNRELEASED =` header in `readme.txt`'s changelog section to `${VERSION}`.
3. Create and push the new tag
```bash
git add readme.txt
git commit --amend
git tag -s -a ${VERSION} -m "release: ${VERSION}"
git push origin ${VERSION}
```

4. `git checkout develop` and prepend the `= UNRELEASED =` header in the `readme.txt` changelog section
```bash
git add readme.txt
git commit -s -m "release: preparation for next release"
git push origin develop
```

### Referencing issues
Due to the development history of NADI, we have a combination of old Jira issues and newer GitHub issues. References to the old Jira issues stay as they are. For newer GitHub issues we are using the following format:

#### Documentation
For documentation purposes (e.g. references in `readme.txt`, `@issue` annotation in unit tests or a badge inside the official documentation), we use `#${GITHUB_ISSUE_NUMBER}`, e.g. `#555`.

#### Tests
Test methods are prefixed with `GH_{GITHUB_ISSUE_NUMBER}_`, e.g. `GH_555_` for easier filtering.
Each test method must have (as described before) the annotation `@issue #${GITHUB_ISSUE_NUMBER}` assigned.

### Testing
Tests are made with PHPUnit 9.5 Get PHPUnit 9.5 with

```shell
# get PHPUnit
wget https://phar.phpunit.de/phpunit-9.5.phar
```

#### Running unit tests

```shell
cd active-directory-integration2
# run unit tests with default PHPUnit configuration
php ./vendor/bin/phpunit --testsuite "unit" --configuration phpunit.xml
``` 

#### Running integration tests 

```shell
cd active-directory-integration2
# running integration test against a local install Active Directory instance
# executing the ITs with PHP binary is required for of passing environment variables to the test
php -d AD_ENDPOINT=127.0.0.1 -d AD_PORT=389 -d AD_USERNAME=username@domain.com -d AD_PASSWORD=Password -d AD_USE_TLS='' -d AD_SUFFIX=@domain.com -d AD_BASE_DN='DC=domain,DC=com' path/to/phpunit.phar --testsuite "integration" --no-coverage
```

#### Running all tests

```shell
cd active-directory-integration2
# running integration test against a local install Active Directory instance
# executing the ITs with PHP binary is required for of passing environment variables to the test
php -d AD_ENDPOINT=127.0.0.1 -d AD_PORT=389 -d AD_USERNAME=username@domain.com -d AD_PASSWORD=Password -d AD_USE_TLS='' -d AD_SUFFIX=@domain.com -d AD_BASE_DN='DC=domain,DC=com' path/to/phpunit.phar --no-coverage
```

#### Running all tests in PhpStorm

Run > Edit Configurations > Defaults > PHPUnit
	
- Test Runner options: `--test-suffix Test.php,IT.php`
- Interpreter options: `-d AD_ENDPOINT=127.0.0.1 -d AD_PORT=389 -d AD_USERNAME=Administrator -d AD_PASSWORD=Pa$$w0rd -d AD_USE_TLS='' -d AD_SUFFIX=@test.ad -d AD_BASE_DN='DC=test,DC=ad'`

#### Update translation

After changing the next_ad_int-de_DE.po you have to build the `next_ad_int-de_DE.mo` and `next_ad_int-de_DE_formal.mo` file.
```shell
# Execute this command inside the plugin root folder (with the index.php)
ant compile-all-languages
# or execute this:
ant -Dmsgfmt=/path/to/gettext/msgfmt compile-all-languages
```
Make sure that you have GNU gettext with *msgfmt* installed.

It is also possible to generate the `next_ad_int-de_DE.mo` with Poedit (or some other .po tool). You can create a copy from the `next_ad_int-de_DE.mo` file and name it `next_ad_int-de_DE_formal.mo`.

### Continuous Integration
We are using GitHub Action for the CI/CD process. You can find everything related to CI/CD inside `.github/workflows`.

The branches

- *main*
- and *develop*

will be automatically tested.

