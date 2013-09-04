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

# スタック
class Funwari_Stack {

	private $stack_index = -1;
	private $stack;

	# コンストラクタ
	function __construct() {
		// スタック配列を初期化
		$this->stack = array();
	}


	# ポップ
	function Pop(){

		if( $this->stack_index >= 0 ) {
			$value = $this->stack[$this->stack_index];
		}

		$this->stack_index--;

	    # こんな場合はないはずだが, 念のため。
	    # エラーにした方がよいかもしれない。
		if($this->stack_index<-1) {
			$this->stack_index = -1;
			return false;
		}

		return $value;

	}


	# プッシュ
	function Push( $item ){
		$this->stack_index++;
		$this->stack[$this->stack_index] = $item;
	}


	# スタックのてっぺんを覗く
	#   普通はpopしたものを見て, pushする。
	function Show(){
		if( $this->stack_index < 0 ) {
			return false;
		}

		return $this->stack[$this->stack_index];
	}
}
?>