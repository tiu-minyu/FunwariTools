<?php
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

require_once dirname(__FILE__).'/lib/Funwari.php';

// クロール用URL管理クラス
class CrawlUrl {
	var $url;
	var $removed_number_parameter_url;
	
	public function __construct($a_url='') {
		$this->url = $a_url;

		// もうちょっとうまくできるとも思うが, 技工を凝らしすぎてもと思う
		$rnp_url = preg_replace('/\/\d+\//', '//', $a_url);
		$rnp_url = preg_replace('/\/\d+$/', '//', $rnp_url);
		$rnp_url = preg_replace('/=\d+&/', '=&', $rnp_url);
		$rnp_url = preg_replace('/=\d+$/', '=', $rnp_url);
		$this->removed_number_parameter_url = $rnp_url;
	}
}


$target_url_str = NormalizeUrl($argv[1]);

if( $target_url_str=='') {
	print('php Funwari_SiteCrawler.php url'."\n");
	exit;
}

$target_url = new Funwari_URL($target_url_str);

// urlを解析
// URLとかいうクラスでも作ります。

// プロトコル
$protocol = $target_url->GetProtocol();

// ドメインを取得
$target_domain = $target_url->GetDomain();

// トップのパス
$target_top_path = $target_url->GetPath();

// 読み込むべきリンクの一覧
$base_url_str = $target_url->GetFullPath();
$link_list = array(new CrawlUrl($base_url_str));
$link_index = 0;

while($link_index<count($link_list)) {
	CrawlAUrl($link_list[$link_index]->url);
	$link_index++;

	// おまけ機能進捗
	if( $link_index % 10 == 0 ) {
		print('.');
	}
}

// これで$link_listに全URLが入っているはず

//ファイルに保存
//指定したurlをパス化して保存できればいいのだが, 
//それはまた別のお話かな
$fp = fopen('link_list.txt', 'w');
foreach( $link_list as $url ) {
	fwrite($fp, $url->url . "\n");
}
fclose($fp);


// 一つのURLをクロール
// @param url ここは完全なURLパスであるはず
function CrawlAUrl($url) {

	global $target_domain;

	// コンテンツ取得
	//   HTTP_Request2でも使えるようにしたほうが
	//   素直だとも思うけど
	// return;

	$contents = file_get_contents($url);
	if( $contents === false ) {
		// 404 Not Foundなど
		return false;
	}

	// 相対パスの基準パス
	$base_url = $url;

	// 保存先
	$output_dir = $target_domain;

	if( !file_exists($target_domain) ) {
		mkdir($target_domain, 0777, true);
	}

	$output_filepath = $output_dir.'/'.Funwari_FilePathEncoder::Encode($url);

	// 内容を保存
	file_put_contents($output_filepath, $contents);

	// 内容を解析して, 次のリンクを探す
	$htmltags = Funwari_Me(new Funwari_HtmlParser())->Parse($contents);
	// var_dump($htmltags);

	$bound = count($htmltags);

	for( $i=0; $i<$bound; $i++ ) {

		$linkto = '';
		if(strtolower($htmltags[$i]['tag']) != 'a') {
			continue;
		}

		# nameの場合もあることに注意。
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
		AddUrl($link_url->GetFullPath());
	}
}


// リンクリストにURLを追加
function AddUrl($url_path) {
	global $link_list;
	global $target_domain;
	global $target_top_path;

	// デバッグ
	// print($url_path . "\n");
	$url = new Funwari_URL($url_path);

	$domain = $url->GetDomain();
	$path   = $url->GetPath();

	// 異なるドメインなら追加しない(今は)
	if( $domain != $target_domain ) {
		return;
	}

	// 最初に指定されたパスより上には行かない(今は)
	if( strpos($path, $target_top_path)===false ) {
		return;
	}

	// 丸め
	$url_path = NormalizeUrl($url_path);

	$url = new CrawlUrl($url_path);

	// 既存のパスに存在するか
	foreach( $link_list as $i_url ) {
		if( $i_url->removed_number_parameter_url == $url->removed_number_parameter_url ) {
			// 追加しない
			return false;
		}
	}

	// リンクリストに追加
	array_push($link_list, new CrawlUrl($url_path));

}


// このアプリケーションに関してのノーマライズを行う
function NormalizeUrl($url) {
	// ページ内リンクは削除
	$url = Funwari_URL::ChopInternalLink($url);

	// 最後スラッシュは削除
	$url = preg_replace('/\/$/', '', $url);

	return $url;
}


?>