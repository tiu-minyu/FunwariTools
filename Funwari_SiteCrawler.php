<?php
/* Copyright (c) 2013, tiu.minyu(@gmail.com)
 * All rights reserved.
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *        notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *     * Neither the name of the FunwariTools nor the
 *       names of its contributors may be used to endorse or promote products
 *       derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE REGENTS AND CONTRIBUTORS "AS IS" AND ANY
 * EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE REGENTS AND CONTRIBUTORS BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

//
// 概要
//   サイトのページを全て取得する
//   http->httpsは行かない。
//   違うドメインには行かない。
//
// 使い方
//   php Funwari_SiteCrawler url(httpから)
//
//
// 保存はhttp_host_domain/somepathという感じで。
//
//
// 最初に指定されたディレクトリから上に行かない
// オプションとか欲しくなるな

require_once 'HTTP/Request2.php';
require_once dirname(__FILE__).'/lib/Funwari.php';

// クロール用URL管理クラス
class CrawlUrl {
	var $url;
	var $removed_number_parameter_url;
	
	public function __construct($a_url='') {
		$this->url = $a_url;

		// 無限ループ含め多すぎるループを回避するため
		// 数字のみのディレクトリやパラメータは省略したもので
		// 読み込み済みかチェックする
		// もうちょっとうまくできるとも思うが, 技工を凝らしすぎてもと思う
		$rnp_url = $a_url;
		$rnp_url = preg_replace('/\/\d+$/', '/', $rnp_url);
		$rnp_url = preg_replace('/\/\d+\//', '/', $rnp_url);
		$rnp_url = preg_replace('/\/\d+\//', '/', $rnp_url);	// /1/2/3のように連続している場合への対応
		$rnp_url = preg_replace('/=\d+&/', '=&', $rnp_url);
		$rnp_url = preg_replace('/=\d+$/', '=', $rnp_url);
		$this->removed_number_parameter_url = $rnp_url;
	}
}


// サイトクローラークラス
class Funwari_SiteCrawler {

	//クロールするベース
	private $protocol;
	private $domain;
	private $top_path;

	//読み込むリンクのリスト
	private $link_list;

	//除外するパス(正規表現)
	private $exclude_path_reg_list;

	// コンストラクタ
	function  __construct($url_path) {
		$url_path = $this->NormalizeUrl($url_path);
		$url = new Funwari_URL($url_path);

		// プロトコル
		$this->protocol = $url->GetProtocol();

		// ドメインを取得
		$this->domain = $url->GetDomain();

		// トップのパス
		$this->top_path = Funwari_URL::ChopFileName($url->GetPath());

		// 読み込むべきリンクの一覧
		$base_url_str = $url->GetFullPath();
		$this->link_list = array(new CrawlUrl($base_url_str));
	}


	// 実行
	public function Run() {

		$link_index = 0;

		// ファイルに逐次保存
		$fp = fopen('link_list.txt', 'w');

		// リストから次にクロールするURLを取得
		while($link_index<count($this->link_list)) {
			// ウェイト(0.5sec);
			// usleep(0.5*1000*1000);

			$url = $this->link_list[$link_index]->url;

			// ファイルに保存
			fwrite($fp, $url . "\n");

			$this->CrawlAUrl($url);
			$link_index++;

			// おまけ機能進捗
			if( $link_index % 10 == 0 ) {
				print('.');
			}
		}

		fclose($fp);
	}


	// 一つのURLをクロール
	// @param url ここは完全なURLパスであるはず
	function CrawlAUrl($url) {

		// コンテンツ取得
		$request = new HTTP_Request2($url, HTTP_Request2::METHOD_GET);
		$response = $request->send();
		$result_code = $response->getStatus();

		// リダイレクトの処理
		// $request->setConfig('follow_redirects'...)などでも
		// 設定できるが, レスポンスコードが欲しいこともあるので
		// あえて自前で処理する
		if( $result_code == 301 ) {
			$redirect_url_str = $response->getHeader('Location');
			if( $redirect_url_str != '') {
				$redirect_url = new Funwari_URL($redirect_url_str);
	 			$this->AddUrl($redirect_url);
	 		}

			return;
		}

		//リダイレクト以外で, 成功しなかった
		if( $result_code != 200 ) {
			return;
		}

		$contents = $response->getBody();

		// 相対パスの基準パス
		$base_url = $url;

		// 保存先
		$output_dir = $this->domain;

		if( !file_exists($output_dir) ) {
			mkdir($output_dir, 0777, true);
		}

		$output_filepath = $output_dir.'/'.Funwari_FilePathEncoder::Encode($url);

		// 内容を保存
		file_put_contents($output_filepath, $contents);

		// 内容を解析して, 次のリンクを探す
		$htmltags = Funwari_Me(new Funwari_HtmlParser())->Parse($contents);

		$bound = count($htmltags);

		for( $i=0; $i<$bound; $i++ ) {

			$linkto = '';
			if(strtolower($htmltags[$i]['tag']) != 'a') {
				continue;
			}

			// nameの場合もあることに注意。
			$attr_count = count($htmltags[$i]['attr']);
			for( $j=0; $j<$attr_count; $j++ ) {
				if( strtolower($htmltags[$i]['attr'][$j]['name']) == 'href') {
					$linkto = $htmltags[$i]['attr'][$j]['value'];
					break;
					// hrefを二つ書くこともできるが, そこは今はまぁ...
				}
			}

			if( $linkto == '' ) {
				continue;
			}

			$link_url = new Funwari_URL($base_url);
			$link_url->Move($linkto);
	 
			// ..などを正規化したものをリンクリストに追加
			// AddUrl($link_url.GetNormalizedURL());
			// テスト中
			$this->AddUrl($link_url);
		}
	}


	// リンクリストにURLを追加
	// @param Funwari_URL $url 
	function AddUrl($url) {

		$protocol = $url->GetProtocol();
		$domain = $url->GetDomain();
		$path   = $url->GetPath();

		// 異なるプロトコルなら追加しない(今は)
		if( $protocol != $this->protocol ) {
			return;
		}

		// 異なるドメインなら追加しない
		if( $domain != $this->domain ) {
			return;
		}

		// 最初に指定されたパスより上には行かない(今は)
		if( strpos($path, $this->top_path)===false ) {
			return;
		}

		// 丸め
		$url_path = $this->NormalizeUrl($url->GetFullPath());

		$crawl_url = new CrawlUrl($url_path);

		// 既存のパスに存在するか
		foreach( $this->link_list as $i_url ) {
			if( $i_url->removed_number_parameter_url == $crawl_url->removed_number_parameter_url ) {
				// 追加しない
				return false;
			}
		}

		// リンクリストに追加
		array_push($this->link_list, $crawl_url);
	}


	// このアプリケーションに関してのノーマライズを行う
	function NormalizeUrl($url) {
		// ページ内リンクは削除
		$url = Funwari_URL::ChopInternalLink($url);

		return $url;
	}


	//ファイルから読み込む
	public function AddUrlFromFile($filepath) {
		if( !file_exists($filepath) ) {
			return false;
		}

		$records = file($filepath);
		foreach($records as $record) {
			$url = preg_replace('/\s.*/', '', $record);
			if( $url == '' ) {
				continue;
			}

			$this->AddUrl(new Funwari_URL($url));
		}

		return true;
	}
}


if( $argv[1]=='') {
	print('php Funwari_SiteCrawler.php url'."\n");
	exit;
}

$site_crawler = new Funwari_SiteCrawler($argv[1]);
if( $argv[2] != '') {
	$site_crawler->AddUrlFromFile($argv[2]);
}
$site_crawler->run();

?>