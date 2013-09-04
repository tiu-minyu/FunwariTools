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
 *     * Neither the name of the {organization} nor the
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
	private $path = '';
	private $org_path = '';

	// コンストラクタ
	function __construct($url_path='') {
		$this->Set($url_path);
	}


	// リセット
	function Reset() {
		$this->protocol = '';
		$this->domain = '';
		$this->path = '';
		$this->org_path = '';
	}


	// 設定
	function Set($url_path) {
		$this->Reset();
		$this->org_path = $url_path;

		// URLならプロトコルを取得
		if( $this->IsURL($url_path) ) {

			// プロトコル取得
			$this->protocol = $this->GetProtocolFromURL($url_path);

			// プロトコル部分を削除
			//$url_path = $this->ChopProtocol($url_path, $this->protocol);

			// ドメイン取得
			// 難しい。ここはとても中途半端
			$this->domain = $this->GetDomainFromURL($url_path);

			// ドメインまで削除
			$url_path = $this->ChopDomain($url_path);
		}

		// あとは相対パスであろうと, 絶対パスであろうと
		// pathに設定するのみ
		$this->path = $url_path;

		return true;
	}


	// 移動
	function Move($url_path) {
		// プロトコルから存在するようなパスなら設定しなおすだけ
		if( $this->IsURL($url_path) ) {
			return $this->Set($url_path);
		}

		// 絶対パスなら, プロトコル, ドメインはそのままにパスのみを
		// 設定する
		if( $this->IsAbsolute($url_path) ) {
			$this->path = $url_path;
			return true;
		}

		// 残るは相対パスだが, これが面倒くさい。
		$this->MoveRelative($url_path);
		return true;
	}


	// 相対パス移動
	// このクラス, しかもMove, MoveRelative以外から呼ばれることはない。
	private function MoveRelative($path) {
		// パス区切りを持っているなら, 分解して再帰
		if( preg_match('/[\/\\\\]/', $path) ) {
			$dir_list = preg_split('/[\/\\\\]/', $path);
			foreach($dir_list as $i_dir) {
				$this->MoveRelative($i_dir);
			}
			return true;
		}

		// 区切り文字がない

		// 移動しない
		if( $path == '.' || $path == '' ) {
			return true;
		}

		// 一つ上に移動
		if( $path == '..' ) {
			$new_path = $this->path;

			// 最後にディレクトリ区切り文字があったら除いておく
			$new_path = preg_replace('/[\/\\\\]$/', '', $new_path);

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

		// その他 = サブディレクトリ名指定
		$new_path = $this->path;

		// 最後にディレクトリ区切り文字があったら除いておく
		$new_path = preg_replace('/[\/\\\\]$/', '', $new_path);

		$new_path .= '/' . $path;

		$this->path = $new_path;
		return true;
	}


	// プロトコルを取得
	function GetProtocolFromURL($url_path) {
		if( !preg_match('/:\/\//', $url_path) ) {
			return '';
		}

		$protocol = preg_replace('/:\/\/.*$/', '', $url_path);

		return $protocol;
	}


	// プロトコルを切り落とし
	function ChopProtocol($url_path, $protocol) {
		return substr($url_path, strlen($protocol)+3);		// strlen(://) = 3
	}


	// ドメインを取得
	function GetDomainFromURL($url) {
		// まずパラメータを落とす
		$target_domain = preg_replace('/\?.*$/', '', $url);
		// プロトコル部分を切り落とし
		$target_domain = preg_replace('/^[^:=\/]*:\/\//', '', $target_domain);
		// パス部分を切り落とし
		$target_domain = preg_replace('/\/.*$/', '', $target_domain);

		return $target_domain;
	}


	// ドメイン部分まで切り落とし
	// URL形式になっている場合にしか対応できていない
	function ChopDomain($url_path) {
		$path = preg_replace('/^.*:\/\/[^\/]*/', '', $url_path);

		// ドメインを削った結果, pathがなくなることはありえる
		if( $path == '' ) {
			$path = '/';
		}

		return $path;
	}


	// ページ内リンクを削除
	static function ChopInternalLink($url_path) {
		$url_path = preg_replace('/#.*\?/', '?', $url_path);
		$url_path = preg_replace('/#.*$/', '', $url_path);

		return $url_path;
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
		if(preg_match('/^http:\/\//', $path)
			|| preg_match('/^htts:\/\//', $path)) {
			return true;
		}

		return false;
	}


	// 絶対パスか
	function IsAbsolute($path) {
		if(preg_match('/^\//', $path)) {
			return true;
		}

		return false;
	}


	// 相対パスか
	function IsRelative($path) {
		return !$this->IsAbsolute($path);
	}

}

?>