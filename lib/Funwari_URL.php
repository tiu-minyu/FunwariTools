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

// URLを管理

class Funwari_URL {

	private $protocol = '';
	private $domain = '';
	// パス インターナルリンク, クエリストリング含まず
	private $path = '';

	// クエリストリング ?は含まず
	private $query_string;

	// インターナルリンク #は含まず
	private $internal_link;

	// コンストラクタ
	function __construct($url_path='') {
		$this->Set($url_path);
	}


	// リセット
	function Reset() {
		$this->protocol = '';
		$this->domain = '';
		$this->path = '';
		$this->query_string = '';
		$this->internal_link = '';
	}


	// 設定
	function Set($url_path) {
		$this->Reset();
		$this->Move($url_path);
	}


	// 完全なURLを設定
	function SetURL($url_path) {

		// プロトコル取得
		$this->protocol = Funwari_URL::GetProtocolFromURL($url_path);

		// プロトコル部分を削除
		$url_path = Funwari_URL::ChopProtocol($url_path);

		$this->SetProtocolRelativeURL($url_path);
	}


	// プロトコル相対URL
	function SetProtocolRelativeURL($url_path) {

		// ドメイン取得
		$this->domain = Funwari_URL::GetDomainFromURL($url_path);

		// ドメインまで削除
		$url_path = Funwari_URL::ChopDomain($url_path);

		$this->Setpath($url_path);
	}


	// パスを設定
	function SetPath($url_path) {
		// クエリストリング
		$this->SetQueryString(Funwari_URL::GetQueryStringFromURL($url_path));

		// インターナルリンク
		$this->SetInternalLink(Funwari_URL::GetInternalLinkFromURL($url_path));

		// あとは相対パスであろうと, 絶対パスであろうと
		// pathに設定するのみ
		$url_path = Funwari_URL::ChopInternalLink($url_path);
		$url_path = Funwari_URL::ChopQueryString($url_path);
		$this->path = $url_path;
	}


	// クエリストリングをセット
	function SetQueryString($query_string) {
		$query_string = str_replace('?', '', $query_string);
		$this->query_string = $query_string;
	}


	// インターナルリンクをセット
	function SetInternalLink($internal_link) {
		$internal_link = str_replace('#', '', $internal_link);
		$this->internal_link = $internal_link;
	}


	// 移動
	// @return 移動後のパス
	function Move($url_path) {

		// プロトコルから存在するようなパスなら設定しなおすだけ
		if( $this->IsURL($url_path) ) {
			$this->SetURL($url_path);
			return $this->GetFullPath();
		}

		// プロトコル抜きドメインなら
		if( $this->IsProtocolRelativeURL($url_path) ) {
			$this->SetProtocolRelativeURL($url_path);
			return $this->GetFullPath();
		}

		// 絶対パスなら, プロトコル, ドメインはそのままにパスのみを
		// 設定する
		if( $this->IsAbsolutePath($url_path) ) {
			$this->SetPath($url_path);
			return $this->GetFullPath();
		}

		// クエリストリングもしくはページ内リンク
		if( $this->IsQueryString($url_path)
			|| $this->IsInternalLink($url_path) ) {
			$query_string = Funwari_URL::ChopInternalLink($url_path);
			$internal_link = Funwari_URL::ChopQueryString($url_path);
			if( $query_string != '' ) {
				$this->SetQueryString($query_string);
			}
			if( $internal_link != '' ) {
				$this->SetInternalLink($internal_link);
			}
			return $this->GetFullPath();
		}

		// 残るは相対パスだが, これが面倒くさい。
		$this->MoveRelative($url_path);
		return $this->GetFullPath();
	}


	// 相対パス移動
	// このクラス, しかもMove, MoveRelative以外から呼ばれることはない。
	private function MoveRelative($path) {
		$this->query_string = '';
		$this->internal_link = '';

		// クエリストリング
		$query_string = Funwari_URL::GetQueryStringFromURL($path);

		// インターナルリンク
		$internal_link = Funwari_URL::GetInternalLinkFromURL($path);

		$path = Funwari_URL::ChopInternalLink($path);
		$path = Funwari_URL::ChopQueryString($path);

		// パス区切りで分解して逐次適用
		$dir_list = preg_split('/[\/\\\\]/', $path);
		foreach($dir_list as $i_dir) {
			$this->MoveRelativeSimple($i_dir);
		}

		// クエリストリング
		if( $query_string != '' ) {
			$this->SetQueryString($query_string);
		}

		// インターナルリンク
		if( $internal_link != '' ) {
			$this->SetInternalLink($internal_link);
		}

		return true;
	}

	// 相対パス移動
	// ただし, こちらは, $pathに区切りやクエリストリングが
	// 含まれていないことが保証されている。
	private function MoveRelativeSimple($path) {

		// 移動しない
		if( $path == '' ) {
			return true;
		}

		// ファイル名削除
		if( $path == '.' ) {
			$this->path = Funwari_URL::ChopFileName($this->path);
			return true;
		}

		// 一つ上に移動
		if( $path == '..' ) {
			// ファイル名削除
			$new_path = Funwari_URL::ChopFileName($this->path);

			// 現在のディレクトリを削除
			$new_path = dirname($new_path);

			$this->path = $new_path;
			return true;
		}

		// 注意。ここでは. 二つ上に移動は実装しない

		// . もしくは .. 以外のピリオドからのみなるディレクトリの場合
		// ここでは何もしないにする。
		if( preg_match('/^\.*$/', $path) ) {
			return true;
		}

		// その他 = サブディレクトリ名指定やファイル名指定

		// ファイル名削除
		$new_path = Funwari_URL::ChopFileName($this->path);

		// 最後にディレクトリ区切り文字がでなければ区切り文字追加
		if( !preg_match('/[\/\\\\]$/', $new_path) ) {
			$new_path .= '/';
		}

		$new_path .= $path;

		$this->path = $new_path;
		return true;
	}


	// プロトコルを取得
	static function GetProtocolFromURL($url_path) {
		if( !preg_match('/^[a-zA-Z]*:/', $url_path) ) {
			return '';
		}

		return preg_replace('/^([a-zA-Z]*):.*$/', '$1', $url_path);
	}


	// プロトコルを切り落とし
	// :までを切り落とす。
	// 完全なURLを与えた場合, 返り値はプロトコル相対URLの形式になる。
	static function ChopProtocol($url_path) {
		return preg_replace('/^[a-zA-Z]*:/', '', $url_path);
	}


	// ドメインを取得
	static function GetDomainFromURL($url_path) {
		if( !preg_match('/^[a-zA-Z]*:?\/\/[^\/]+/', $url_path) ) {
			return '';
		}

		return preg_replace('/^[a-zA-Z]*:?\/\/([^\/]+).*$/', '$1', $url_path);
	}


	// ドメイン部分まで切り落とし
	// URL形式になっている場合にしか対応できていない
	function ChopDomain($url_path) {
		$path = preg_replace('/^[^\/]*\/\/[^\/]*/', '', $url_path);

		// ドメインを削った結果, pathがなくなることはありえる
		if( $path == '' ) {
			$path = '/';
		}

		return $path;
	}


	// パス中からクエリストリングを取り出す
	static function GetQueryStringFromURL($path) {
		if( ! Funwari_URL::FindQueryStringFromURL($path) ) {
			return '';
		}

		return preg_replace('/^.*\?([^#]*).*$/', '$1', $path);
	}


	// パス中からインターナルリンクを取り出す
	static function GetInternalLinkFromURL($path) {
		if( ! Funwari_URL::FindInternalLinkFromURL($path) ) {
			return '';
		}

		return preg_replace('/^.*#([^\?]*).*$/', '$1', $path);
	}


	// クエリストリングを持っているか
	static function FindQueryStringFromURL($path) { 
		if( strpos($path, '?') !== false ) {
			return true;
		}

		return false;
	}


	// インターナルリンクを持っているか
	static function FindInternalLinkFromURL($path) { 
		if( strpos($path, '#') !== false ) {
			return true;
		}

		return false;
	}


	// ページ内リンクを削除
	static function ChopInternalLink($url_path) {
		$url_path = preg_replace('/#[^\?]*\?/', '?', $url_path);
		$url_path = preg_replace('/#.*$/', '', $url_path);

		return $url_path;
	}


	// クエリストリングを削除
	static function ChopQueryString($url_path) {
		$url_path = preg_replace('/\?[^#]*#/', '#', $url_path);
		$url_path = preg_replace('/\?.*$/', '', $url_path);

		return $url_path;
	}


	// ファイル名削除
	//   ここではファイル名とは1つ以上の文字を持つ
	//   拡張子を持つものとする。
	//   実際のところ, *nixでは拡張子を持たない
	//   ファイルも普通に存在するので判定が難しい
	//
	// 単純なファイル名削除ではdirnameがあるが, 
	// こちらは拡張子の有る無しは考えてくれないので
	// 独自に実装した
	static function ChopFileName($path) {
		return preg_replace('/[^\/\\\\]*\.[^\/\\\\]+/', '', $path);
	}


	// 全部のアドレスを取得
	function GetFullPath() {
		$path = '';

		if( $this->protocol != '' ) {
			$path = $this->protocol . '://';
		}

		if( $this->domain != '' ) {
			if( $path == '' ) {
				$path = '//';
			}
			$path .= $this->domain;
		}

		$path .= $this->path;

		if( $this->query_string != '' ) {
			$path .= '?' . $this->query_string;
		}

		if( $this->internal_link != '' ) {
			$path .= '#' . $this->internal_link;
		}

		return $path;
	}


	// プロトコルを返す
	function GetProtocol() {
		return $this->protocol;
	}


	// ドメインを返す
	function GetDomain() {
		return $this->domain;
	}


	// パスを返す
	function GetPath() {
		return $this->path;
	}


	// URLか?
	function IsURL($path) {
		if( preg_match('/^[a-z]+:/', $path) ) {
			return true;
		}

		return false;
	}


	// プロトコル相対URLか
	function IsProtocolRelativeURL($path) {
		if( preg_match('/^\/\//', $path) ) {
			return true;
		}

		return false;
	}


	// 絶対パスか
	function IsAbsolutePath($path) {
		if( preg_match('/^\//', $path) ) {
			return true;
		}

		return false;
	}


	// 相対パスか
	function IsRelativePath($path) {
		return !$this->IsAbsolute($path);
	}


	// ページ内リンクか
	function IsInternalLink($path) {
		if( preg_match('/^#/', $path) ) {
			return true;
		}

		return false;
	}


	// クエリストリングか
	function IsQueryString($path) {
		if( preg_match('/^\?/', $path) ) {
			return true;
		}

		return false;
	}
}

?>