<?php
/**
 * Unit tests for Open Power Autoloader
 *
 * @author Tomasz "Zyx" Jędrzejewski
 * @copyright Copyright (c) 2009-2011 Invenzzia Group
 * @license http://www.invenzzia.org/license/new-bsd New BSD License
 */
namespace TestSuite;
use Opl\Autoloader\UniversalLoader;
require_once 'vfsStream/vfsStream.php';

/**
 * @covers \Opl\Autoloader\UniversalLoader
 * @runTestsInSeparateProcesses
 */
class UniversalLoaderTest extends \PHPUnit_Framework_TestCase
{
	public function testLoaderInitialization()
	{
		$loader = new UniversalLoader('./foo/bar/', 'foo');
		$this->assertEquals('foo', $loader->getNamespaceSeparator());
		$this->assertEquals('./foo/bar/', $loader->getDefaultPath());
	} // end testLoaderInitialization();

	/**
	 * @depends testLoaderInitialization
	 */
	public function testConstructorAppendsSlash()
	{
		$loader = new UniversalLoader('./foo/bar', 'foo');
		$this->assertEquals('./foo/bar/', $loader->getDefaultPath());
	} // end testConstructorAppendsSlash();

	/**
	 * @depends testLoaderInitialization
	 */
	public function testConstructorAppendsSlashToEmptyString()
	{
		$loader = new UniversalLoader('', 'foo');
		$this->assertEquals('/', $loader->getDefaultPath());
	} // end testConstructorAppendsSlash();

	public function testSetDefaultPath()
	{
		$loader = new UniversalLoader('./foo/bar/', 'foo');
		$this->assertEquals('./foo/bar/', $loader->getDefaultPath());

		$loader->setDefaultPath('./bar/joe/');
		$this->assertEquals('./bar/joe/', $loader->getDefaultPath());
	} // end testSetDefaultPath();

	/**
	 * @depends testSetDefaultPath
	 */
	public function testSetDefaultPathAppendsSlash()
	{
		$loader = new UniversalLoader('./foo/bar/', 'foo');
		$loader->setDefaultPath('./bar/joe');
		$this->assertEquals('./bar/joe/', $loader->getDefaultPath());
	} // end testSetDefaultPathAppendsSlash();

	public function testSetNamespaceSeparator()
	{
		$loader = new UniversalLoader('./foo/bar/', 'foo');
		$this->assertEquals('foo', $loader->getNamespaceSeparator());
		$loader->setNamespaceSeparator('bar');
		$this->assertEquals('bar', $loader->getNamespaceSeparator());
	} // end testSetNamespaceSeparator();

	public function testAddingNamespace()
	{
		$loader = new UniversalLoader('./foo/bar/');

		$this->assertFalse($loader->hasNamespace('Foo'));
		$this->assertFalse($loader->hasNamespace('Bar'));
		$this->assertFalse($loader->hasNamespace('Bar\Joe'));

		$loader->addNamespace('Foo');
		$loader->addNamespace('Foo\Joe');

		$this->assertTrue($loader->hasNamespace('Foo'));
		$this->assertFalse($loader->hasNamespace('Bar'));
		$this->assertTrue($loader->hasNamespace('Foo\Joe'));
	} // end testAddingNamespace();

	public function testAddNamespaceSetsDefaultPathAndExtension()
	{
		$loader = new UniversalLoader('./foo/bar/');
		$loader->addNamespace('Foo');

		$reflection = new \ReflectionObject($loader);
		$namespacesProperty = $reflection->getProperty('namespaces');
		$namespacesProperty->setAccessible(true);
		$extensionsProperty = $reflection->getProperty('extensions');
		$extensionsProperty->setAccessible(true);

		$this->assertEquals(array('Foo' => './foo/bar/'), $namespacesProperty->getValue($loader));
		$this->assertEquals(array('Foo' => '.php'), $extensionsProperty->getValue($loader));
	} // end testAddNamespaceSetsDefaultPathAndExtension();

	public function testAddNamespaceSetsCustomPathAndExtension()
	{
		$loader = new UniversalLoader('./foo/bar/');
		$loader->addNamespace('Foo', './bar/joe/', '.php5');

		$reflection = new \ReflectionObject($loader);
		$namespacesProperty = $reflection->getProperty('namespaces');
		$namespacesProperty->setAccessible(true);
		$extensionsProperty = $reflection->getProperty('extensions');
		$extensionsProperty->setAccessible(true);

		$this->assertEquals(array('Foo' => './bar/joe/'), $namespacesProperty->getValue($loader));
		$this->assertEquals(array('Foo' => '.php5'), $extensionsProperty->getValue($loader));
	} // end testAddNamespaceSetsCustomPathAndExtension();

	public function testAddNamespaceAddsTrailingSlash()
	{
		$loader = new UniversalLoader('./foo/bar');
		$loader->addNamespace('Foo', './bar/joe', '.php5');
		
		$reflection = new \ReflectionObject($loader);
		$namespacesProperty = $reflection->getProperty('namespaces');
		$namespacesProperty->setAccessible(true);
		$extensionsProperty = $reflection->getProperty('extensions');
		$extensionsProperty->setAccessible(true);

		$this->assertEquals(array('Foo' => './bar/joe/'), $namespacesProperty->getValue($loader));
		$this->assertEquals(array('Foo' => '.php5'), $extensionsProperty->getValue($loader));
	} // end testAddNamespaceAddsTrailingSlash();
	
	/**
	 * @expectedException DomainException
	 */
	public function testAddNamespaceThrowsExceptionWhenNamespaceExists()
	{
		$loader = new UniversalLoader('./foo/bar/');
		$loader->addNamespace('Foo');
		$this->assertTrue($loader->hasNamespace('Foo'));
		$loader->addNamespace('Foo');
	} // end testNamespaceThrowsExceptionWhenNamespaceExists();

	public function testRemoveNamespace()
	{
		$loader = new UniversalLoader('./foo/bar/');
		$loader->addNamespace('Foo');
		$this->assertTrue($loader->hasNamespace('Foo'));

		$reflection = new \ReflectionObject($loader);
		$namespacesProperty = $reflection->getProperty('namespaces');
		$namespacesProperty->setAccessible(true);
		$extensionsProperty = $reflection->getProperty('extensions');
		$extensionsProperty->setAccessible(true);

		$this->assertEquals(array('Foo' => './foo/bar/'), $namespacesProperty->getValue($loader));
		$this->assertEquals(array('Foo' => '.php'), $extensionsProperty->getValue($loader));

		$loader->removeNamespace('Foo');
		$this->assertFalse($loader->hasNamespace('Foo'));

		$this->assertEquals(array(), $namespacesProperty->getValue($loader));
		$this->assertEquals(array(), $extensionsProperty->getValue($loader));
	} // end testRemoveLibrary();

	/**
	 * @depends testRemoveNamespace
	 * @expectedException DomainException
	 */
	public function testRemoveNamespaceThrowsExceptionWhenNamespaceDoesNotExist()
	{
		$loader = new UniversalLoader('./foo/bar/');
		$this->assertFalse($loader->hasNamespace('Moo'));
		$loader->removeNamespace('Moo');
	} // end testRemoveNamespaceThrowsExceptionWhenNamespaceDoesNotExist();

	public function testRegisterWorks()
	{
		$loader = new UniversalLoader('./foo/bar/');
		$loader->register();

		$functions = spl_autoload_functions();
		$this->assertContains(array($loader, 'loadClass'), $functions);
	} // end testRegisterWorks();

	public function testUnregisterWorks()
	{
		$loader = new UniversalLoader('./foo/bar/');
		$loader->register();

		$functions = spl_autoload_functions();
		$this->assertContains(array($loader, 'loadClass'), $functions);

		$loader->unregister();

		$functions = spl_autoload_functions();
		$this->assertThat($functions, $this->logicalNot($this->contains(array($loader, 'loadClass'))));
	} // end testUnregisterWorks();

	/**
	 * @depends testAddingNamespace
	 */
	public function testLoaderReplacesNSToSlashes()
	{
		$file = new \vfsStreamFile('Bar.php');
		$file->setContent('<?php echo "FOO\BAR.PHP"; ');
		$topLevelDir = new \vfsStreamDirectory('Foo');
		$topLevelDir->addChild($file);

		\vfsStreamWrapper::register();
		\vfsStreamWrapper::setRoot($topLevelDir);

		$loader = new UniversalLoader(\vfsStream::url(''));
		$loader->addNamespace('Foo');
		$loader->register();

		ob_start();
		spl_autoload_call('Foo\\Bar');
		$this->assertEquals('FOO\\BAR.PHP', ob_get_clean());
	} // end testLoaderReplacesNSToSlashes();

	/**
	 * @depends testAddingNamespace
	 */
	public function testLoaderReplacesUnderscoresToSlashesInClassNames()
	{
		$file = new \vfsStreamFile('Joe.php');
		$file->setContent('<?php echo "FOO\BAR\JOE.PHP"; ');
		$subdir = new \vfsStreamDirectory('Bar');
		$subdir->addChild($file);
		$topLevelDir = new \vfsStreamDirectory('Foo');
		$topLevelDir->addChild($subdir);


		\vfsStreamWrapper::register();
		\vfsStreamWrapper::setRoot($topLevelDir);

		$loader = new UniversalLoader(\vfsStream::url(''));
		$loader->addNamespace('Foo');
		$loader->register();

		ob_start();
		spl_autoload_call('Foo\\Bar_Joe');
		$this->assertEquals('FOO\\BAR\\JOE.PHP', ob_get_clean());
	} // end testLoaderReplacesUnderscoresToSlashesInClassNames();

	/**
	 * @depends testAddingNamespace
	 */
	public function testLoaderDoesNotReplaceUnderscoresToSlashesInNamespace()
	{
		$file = new \vfsStreamFile('Goo.php');
		$file->setContent('<?php echo "FOO\BAR_JOE\GOO.PHP"; ');
		$subdir = new \vfsStreamDirectory('Bar_Joe');
		$subdir->addChild($file);
		$topLevelDir = new \vfsStreamDirectory('Foo');
		$topLevelDir->addChild($subdir);


		\vfsStreamWrapper::register();
		\vfsStreamWrapper::setRoot($topLevelDir);

		$loader = new UniversalLoader(\vfsStream::url(''));
		$loader->addNamespace('Foo');
		$loader->register();

		ob_start();
		spl_autoload_call('Foo\\Bar_Joe\\Goo');
		$this->assertEquals('FOO\\BAR_JOE\\GOO.PHP', ob_get_clean());
	} // end testLoaderDoesNotReplaceUnderscoresToSlashesInNamespace();

	/**
	 * @depends testAddingNamespace
	 */
	public function testSkippingUnknownLibraries()
	{
		$loader = new UniversalLoader('./foo/bar/');
		$loader->addNamespace('Dummy');
		$loader->register();

		spl_autoload_register(function($name){ echo 'yey'; return true; });

		ob_start();
		spl_autoload_call('Foo\\Bar');
		$this->assertEquals('yey', ob_get_clean());
	} // end testSkippingUnknownClasses();
	
	/**
	 * @depends testAddingNamespace
	 */
	public function testLoaderHandlesSubnamespaces()
	{
		$file = new \vfsStreamFile('Joe.php');
		$file->setContent('<?php echo "FOO\BAR\JOE.PHP"; ');
		$subdir = new \vfsStreamDirectory('Bar');
		$subdir->addChild($file);
		$topLevelDir = new \vfsStreamDirectory('Foo');
		$topLevelDir->addChild($subdir);


		\vfsStreamWrapper::register();
		\vfsStreamWrapper::setRoot($topLevelDir);

		$loader = new UniversalLoader(\vfsStream::url(''));
		$loader->addNamespace('Foo\Bar');
		$loader->register();

		ob_start();
		spl_autoload_call('Foo\\Bar\\Joe');
		$this->assertEquals('FOO\\BAR\\JOE.PHP', ob_get_clean());
	} // end testLoaderHandlesSubnamespaces();
} // end UniversalLoaderTest;