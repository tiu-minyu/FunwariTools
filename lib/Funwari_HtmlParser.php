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

//
// 概要
//   htmlのタグを解析する。
//
// 使い方
//   これはライブラリで, 単独では使えません。
//
//   $htmltags = (new Funwari_HtmlParser)->ParseHtml($htmlcontents);
//

require_once dirname(__FILE__).'/Funwari_Stack.php';

class Funwari_HtmlParser {

	private $status_stack;

	private $tag_list;
	private $tag_list_length;

	private $htmltags;
	private $htmltags_length;
	private $htmltags_node;
	private $htmltags_node_attr;

	private $current_tag_name;
	private $attribute_count;

	const NEW_TAG_ADDED = 1;
	const NO_STACKED = 2;
	const HTML_COMMENT = 3;
	const HTML_TAG = 4;
	const VAR_STRING = 5;
	const VAR_STRING1 = 6;

	// 終了タグのないタグのリスト(ハッシュ)
	private $no_end_tag_list = array('meta', '!doctype');

	//
	// htmlを解析
	// bodyとattribute属性に分けて記録
	// <a href="foo.html">foo</a>
	// a:
	//   -
	//     tag:  a
	//     body: foo
	//     attribute:
	//       -
	//         href: foo.html
	// てな感じ。
	//
	public function Parse($htmlcontents) {
		$s = $lines = preg_split('/[\r\n]+/', $htmlcontents);
		$l = $line_count = count($lines);

		# 大域変数のリセット
		$this->Reset();

		# 一行づつ解析
		for($i=0;$i<$line_count;$i++) {
			$t = $lines[$i];
			// 改行削除
			$t = preg_replace('/[\r\n]*$/', '', $t);
			$this->ParseHtmlLine($t);
			$this->AddParseHtmlStr("\n");
		}

	    return $this->htmltags;

	}

	//
	//
	// htmlを一行づつチェック。
	//   そうしないと, 正規表現などで厳しい場合あり。
	//
	protected function ParseHtmlLine($line){

		$htmlcontentslength = 0;

	    $htmlcontents = $line;

		$p = '';
		$t = '';

		$prehtmlcontents = $htmlcontents;
		$prehtmlcontentslength = strlen($prehtmlcontents);
	    while($htmlcontents != ''){   // 与えられた文字列がなくなるまで
	        # $j++;
	        # exit if($j>100);
			$htmlcontents = $this->ParseHtmlLineCore($htmlcontents);

			# 現在の解析結果に差分を追加
			$htmlcontentslength = strlen($htmlcontents);
			$this->AddParseHtmlStr(substr($prehtmlcontents, 0, 
					$prehtmlcontentslength-$htmlcontentslength));

			$prehtmlcontents = $htmlcontents;
			$prehtmlcontentslength = $htmlcontentslength;

			$p = $this->status_stack->Show();
			if($p == Funwari_HtmlParser::NEW_TAG_ADDED) {
				$this->status_stack->Pop();
				# 開始タグの記述が終わった。
				#   追記リストに追加
				$t = strtolower($this->current_tag_name);
				$this->tag_list[$this->tag_list_length++] = $this->current_tag_name;
				if(isset($this->no_end_tag_list[$t])) {
					# それ自体で終了してしまうタグに
					# 値を追加する必要はない。
					# $tag_listのインデックスは$htmltagsと
					# 同じであるので, ちょっとわかりづらい
					$this->tag_list[$this->tag_list_length-1] = '';
				}

			}
		}

		return 1;
	} # /parse_html_line

	###
	#
	# 行ごとの分析のさらに詳細
	#
	###
	function ParseHtmlLineCore($htmlcontents) {
	    // my ($s $t, $p);
		// my $tagName;
		// my $tagValue;
		// my $attrName;
		// my $attrValue;
		// my $u;
		// my $f; # field
		// my $i;
		// my $v;

		$p = $this->status_stack->Show();
		# print($htmlcontents);

		if($p==Funwari_HtmlParser::NO_STACKED || $p==false){
			# タグの解析中じゃない場合

			# コメントだ。
			if(preg_match('/^<!--/', $htmlcontents)) {
				$htmlcontents = substr($htmlcontents, 4);
	  	        $this->status_stack->Push(Funwari_HtmlParser::HTML_COMMENT);
				return $htmlcontents;
			}

			# タグだ。
			if(preg_match('/^</', $htmlcontents)) {
				# タグに関する変数を初期化
				$this->attribute_count = 0;

				# 先頭の<を削って, スタックにHTML_TAGを積み
				# 以降の処理をそっちに任せる。
				# < a のように< とtagnameの間にスペースや
				# 改行がある場合はHTML_TAG_NO_NAMEていう
				# 状態を作って処理するが, 今は先に進む。
				# タグの名前を取得
				$tagName = substr($htmlcontents,1);
				// $tagName =~ s/[\r\n]*$//;
				// $tagName =~ s/[ \t>].*$//;
				$tagName = preg_replace('/[\r\n]*$/', '', $tagName);
				$tagName = preg_replace('/[ \t>].*$/', '', $tagName);

				# print($tagName."\n");
				# $outHtmltags->{$tagName} = $tagName;
				$this->current_tag_name = $tagName;

				if(preg_match('/\//', $tagName)) {
					# 終了タグ
					$t = substr($tagName, 1);
					# 直近の同じタグをクローズ。
					for($i=$this->tag_list_length-1;$i>=0;$i--) {
						if($this->tag_list[$i] == $t) {
							$this->tag_list[$i] = '';
							break;
						}
					}
				} else {
					# 開始タグ
					$this->CreateHtmltagsNode($tagName);
				}

				# 元文章などを更新
				$s = substr($htmlcontents, 0, 1+strlen($tagName));
				$htmlcontents = substr($htmlcontents, 1+strlen($tagName));

	      	    $this->status_stack->Push(Funwari_HtmlParser::HTML_TAG);

				return $htmlcontents;
			}

			# ただの文字。
			$s = $htmlcontents;
			// $s =~ s/[\r\n]*$//;
			$s = preg_replace('/[\r\n]*$/', '', $s);
	        $s .= '<';      # 番兵
	        // $s =~ s/<.*$//g;      # 最初の<以降をすべて消す。
			$s = preg_replace('/<.*$/', '', $s);
			# foo<bar> -> foo
			# 今のタグの値に$sを追加。


			# 解析対象文字列を更新
			$htmlcontents = substr($htmlcontents, strlen($s));
			// if($htmlcontents =~ /^[\r\n]*$/) {
			if(preg_match('/^[\r\n]*$/', $htmlcontents)) {
				$htmlcontents = '';
			}
			return $htmlcontents;

		} else if ($p == Funwari_HtmlParser::HTML_TAG){
			//  html_tagの場合
	    	// タグの終わりか, 値の設定に入る
			// 考慮すべきことがない。
			//  if($htmlcontents =~ /^[ \t\r\n]*$/) {
			//	$htmlcontents = '';
			//	return $htmlcontents;
			// }

			# タグが終わっている。
			if(preg_match('/^[ \t\r\n]*>/', $htmlcontents)) {
				$this->status_stack->Pop();
				// $htmlcontents =~ s/^[ \r\t\n]*>//;
				$htmlcontents = preg_replace('/^[ \r\t\n]*>/', '', $htmlcontents);
				# 開始タグなら
				if(!(preg_match('/\//', $this->current_tag_name))) {
					$this->status_stack->Push(Funwari_HtmlParser::NEW_TAG_ADDED);
				}
				return $htmlcontents;
			}

			// タグはすぐには終わらない=なんらかの属性がある。
			$u = $htmlcontents;
			// $u =~ s/^[ \t]*//; # ' foo=hoge'->foo=hoge
			$u = preg_replace('/^[ \t]*/', '', $u);
			$attrName = $u;
			// $attrName =~ s/[\r\n]*$//; # foo=hoge
			$attrName = preg_replace('/[\r\n]*$/', '', $attrName);
			// print('org='.$attrName."\n");
			// $attrName =~ s/^([^ \t>]*).*$/$1/;  # foo=hoge var=hoge->foo=hoge
			$attrName = preg_replace('/^([^ \t>]*).*$/', '$1', $attrName);
			// print('attrName: '.$attrName."\n");
			$attrValue = $attrName;
			// $attrName =~ s/=.*$//;
			$attrName = preg_replace('/=.*$/', '', $attrName);

			// ひとまず, ここまでで$tを更新
			$htmlcontents = preg_replace('/^[ \t]*/', '', $htmlcontents);
			$htmlcontents = substr($htmlcontents, strlen($attrName));

			// %{$htmltags_node->{'attr'}->[$attribute_count]}
			// 	= ('name', $attrName, 'value', '');
			$this->htmltags_node['attr'][$this->attribute_count] = array(
				'name' => $attrName,
				'value' => '');
			// $htmltags_node_attr = \%{$htmltags_node->{'attr'}->[$attribute_count]};
			$this->htmltags_node_attr = &$this->htmltags_node['attr'][$this->attribute_count];
			$this->attribute_count++;

			//var_dump($attrName);
			//var_dump($attrValue);

			# nowrapなど値をとらない属性もある。
			if($attrName == $attrValue) {
				# そのときは次へ
				return $htmlcontents;
			}

	        $htmlcontents = preg_replace('/^=/', '', $htmlcontents);
			$attrValue = preg_replace('/^[^=]*=/', '', $attrValue);

			# 値がシングルクォーテーションや
			# ダブルクォーテーションでくくられていなければ
			# 空白, タブ, 改行, >までが値になる。
			if(!(preg_match('/^[\'"]/', $attrValue))) {		// '"
				$this->htmltags_node_attr['value'] = $attrValue;
				// var_dump($this->htmltags_node);
				// var_dump($this->htmltags_node_attr);
				# 解析対象文字列を更新
				$htmlcontents = substr($htmlcontents, strlen($attrValue));
				return $htmlcontents;
			}

	        if(preg_match('/^\'/', $htmlcontents)) {			// '
		        $this->status_stack->Push(Funwari_HtmlParser::VAR_STRING1);
	    	} else if(preg_match('/^"/', $htmlcontents)) {		// "
	            $this->status_stack->Push(Funwari_HtmlParser::VAR_STRING);
	        } else if(preg_match('/^>/', $htmlcontents)) {
	            $this->status_stack->Pop();
	        }
	        // $htmlcontents =~ s/^['">]//; # 判別の要素となったものをすえる	// '
	        $htmlcontents = preg_replace('/^[\'">]/', '', $htmlcontents);	// '" 判別の要素となったものをすえる
			return $htmlcontents;

	    } else if ($p == Funwari_HtmlParser::HTML_COMMENT) { # -------------- commentの場合
	        $htmlcontents .= '-->';   # 番兵
	        // $htmlcontents =~ s/^[\s\S]*?-->//;    # ものぐさマッチ。使えない場合は...?
	        $htmlcontents = preg_replace('/^[\s\S]*?-->/', '', $htmlcontents);    # ものぐさマッチ。使えない場合は...?
	        if($htmlcontents != '') {
	        	$this->status_stack->Pop();   # -->があったということだ
			}
	        // $htmlcontents =~ s/-->$//;   # 番兵を取り去る
	        $htmlcontents = preg_replace('/-->$/', '', $htmlcontents);   # 番兵を取り去る
			return $htmlcontents;

	    } else if ($p == Funwari_HtmlParser::VAR_STRING # ---------------- VAR_STINGの場合
				|| $p == Funwari_HtmlParser::VAR_STRING1) { # ------ VAR_STING1の場合
			if($p == Funwari_HtmlParser::VAR_STRING) {
				$f = '"';
			} else {
				$f = "'";
			}
			# print('VARSTRING='.$htmlcontents."\n");
			# 終わりだ。
			if(preg_match("/^$f/", $htmlcontents)) {
				$this->status_stack->Pop();
				$htmlcontents = substr($htmlcontents, 1);
				return $htmlcontents;
			}
			# 中身の抽出
			$u = $htmlcontents;
	        // $u =~ s/^([^$f]*).*[\r\n]*$/$1/; # [.\r\n]とはまとめられない
			$u = preg_replace('/^([^'.$f.']*).*[\r\n]*$/', '$1', $u);
			# print('$u       ='.$u."\n");
			// $htmltags_node_attr->{'value'} .= $u;
			$this->htmltags_node_attr['value'] .= $u;
	        $htmlcontents = substr($htmlcontents, strlen($u));
			# print('t='.$t."\n");
			return $htmlcontents;

	    } else {
	        die("unknown parameters\n");
	    }

		return $htmlcontents;
	} # /parse_html_line_core

	###
	#
	# htmltagsの新しいノードを作成
	#
	###
	// 正直, ここが何をしてるのかよくわからない。
	// 返り値はない。
	function CreateHtmltagsNode($tagname) {
		$i = $this->htmltags_length;
		$this->htmltags[$i] = array(
			'tag' => $tagname,
			'body' => '',
			'attr' => array()
		);
		$this->htmltags_node = &$this->htmltags[$i];
		$this->htmltags_length++;
	} # /create_htmltags_node

	###
	#
	# 文字を解析結果の生きているタグに追加
	#
	###
	function AddParseHtmlStr($s) {
		for($i=0;$i<$this->tag_list_length;$i++) {
			if($this->tag_list[$i] != '') {
				$this->htmltags[$i]['body'] .= $s;
			}
		}
	}

	###
	#
	# 大域変数のリセット
	#
	###
	function Reset() {
		$this->htmltags = array();
		$this->status_stack = new Funwari_Stack();
		$this->tag_list = array();
		$this->tag_list_length = 0;
		$this->htmltags = array();
		$this->htmltags_length = 0;
		$this->htmltags_node = null;
		$this->htmltags_node_attr = null;
		$this->current_tag_name = '';
		$this->attribute_count = 0;

		$this->status_stack->Push(Funwari_HtmlParser::NO_STACKED);

		# タグで始まらない場合もあるし, 
		# 全体が欲しいときもあるだろうし。
		$this->CreateHtmltagsNode('(all)');
		$this->tag_list[$this->tag_list_length++] = '(all)';
	}
}
?>