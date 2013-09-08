<?php
// phpunit Funwari_URL_test.php
// で確認
require_once 'PHPUnit/Autoload.php';
define('BASE', dirname(dirname(__FILE__)));
require_once BASE.'/lib/Funwari.php';

class Funwari_URL_Test extends PHPUnit_Framework_TestCase {
	public function testSimple() {

		// オブジェクト
		$url = new Funwari_URL('http://www.funwaritools.net');

		$this->assertEquals('http', $url->GetProtocol());
		$this->assertEquals('www.funwaritools.net', $url->GetDomain());

		$url->Move('index.html');
		$this->assertEquals('http://www.funwaritools.net/index.html', $url->GetFullPath());
		
		$url->Move('hoge.html');
		$this->assertEquals('http://www.funwaritools.net/hoge.html', $url->GetFullPath());
		
		$url->Move('hoge/bar.html');
		$this->assertEquals('http://www.funwaritools.net/hoge/bar.html', $url->GetFullPath());

		$url->Move('foo/index.html');
		$this->assertEquals('http://www.funwaritools.net/hoge/foo/index.html', $url->GetFullPath());

		// 絶対パス
		$url->Move('/bar/index.html');
		$this->assertEquals('http://www.funwaritools.net/bar/index.html', $url->GetFullPath());

		$url->Move('/bar/index.html?hoge=foo');
		$this->assertEquals('/bar/index.html', $url->GetPath());
		$this->assertEquals('http://www.funwaritools.net/bar/index.html?hoge=foo', $url->GetFullPath());

		$url->Move('/bar/index.html?hoge=foo#name');
		$this->assertEquals('http://www.funwaritools.net/bar/index.html?hoge=foo#name', $url->GetFullPath());

		$url->Move('/bar/index.html#name?hoge=foo');
		$this->assertEquals('http://www.funwaritools.net/bar/index.html?hoge=foo#name', $url->GetFullPath());


		// 一度仕切り直し
		$url = new Funwari_URL('http://www.funwaritools.net');

		$url->Move('?hoge=bar');
		$this->assertEquals('http://www.funwaritools.net/?hoge=bar', $url->GetFullPath());

		$url->Move('#section1?fuga=aaa');
		$this->assertEquals('http://www.funwaritools.net/?fuga=aaa#section1', $url->GetFullPath());

		$url->Move('//www.funwaritools.com/url');
		$this->assertEquals('http://www.funwaritools.com/url', $url->GetFullPath());

		$url->Move('protocol');
		$this->assertEquals('http://www.funwaritools.com/url/protocol', $url->GetFullPath());

		// もう一度仕切りなおし
		// 一度仕切り直し
		$url = new Funwari_URL('http://www.funwaritools.net/tmp/index.html');

		$url->Move('?hoge=bar');
		$this->assertEquals('http://www.funwaritools.net/tmp/index.html?hoge=bar', $url->GetFullPath());
	}


	public function testStatic()
	{
		// スタティックメソッド
		$path = Funwari_URL::ChopInternalLink('http://funwaritools.net/index.html#name');
		$this->assertEquals($path, 'http://funwaritools.net/index.html');

		$path = Funwari_URL::ChopInternalLink('http://funwaritools.net/index.html');
		$this->assertEquals($path, 'http://funwaritools.net/index.html');

		$path = Funwari_URL::ChopInternalLink('http://funwaritools.net/index.html?hoge=bar#name');
		$this->assertEquals($path, 'http://funwaritools.net/index.html?hoge=bar');

		$path = Funwari_URL::ChopQueryString('http://funwaritools.net/index.html#name');
		$this->assertEquals($path, 'http://funwaritools.net/index.html#name');

		$path = Funwari_URL::ChopQueryString('http://funwaritools.net/index.html');
		$this->assertEquals($path, 'http://funwaritools.net/index.html');

		$path = Funwari_URL::ChopQueryString('http://funwaritools.net/index.html?hoge=bar');
		$this->assertEquals($path, 'http://funwaritools.net/index.html');

		$query_string = Funwari_URL::GetQueryStringFromURL('index.html?hoge=bar');
		$this->assertEquals($query_string, 'hoge=bar');

		$query_string = Funwari_URL::GetQueryStringFromURL('index.html');
		$this->assertEquals($query_string, '');

		$query_string = Funwari_URL::GetQueryStringFromURL('index.html?hoge=bar#name');
		$this->assertEquals($query_string, 'hoge=bar');

		$internal_link = Funwari_URL::GetInternalLinkFromURL('index.html#name');
		$this->assertEquals($internal_link, 'name');

		$internal_link = Funwari_URL::GetInternalLinkFromURL('index.html');
		$this->assertEquals($internal_link, '');

		$internal_link = Funwari_URL::GetInternalLinkFromURL('index.html#name?hoge=bar');
		$this->assertEquals($internal_link, 'name');

	}
}