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

// 任意の文字列をFilePathの禁止文字に抵触しないようにエンコード

class Funwari_FilePathEncoder {

	// エンコード
	//   / \ : * ? | < > & " '
	// を _ charcode - に変換
	// _\d{2}-は_5F-\d{2]-と先に変換される
	static function Encode($s) {
		$s = preg_replace('/_(\d{2})-/', '_5F-$1-', $s);
		$s = str_replace(array('/',    '\\',   ':',    '*',    '?',     '|', 
			                   '<',    '>',    '&',    '"',  "'"),
						 array('_2F-', '_5C-', '_3A-', '_2A-', '_3F-', '_7C-',
			                   '_3C-', '3E_-', '_26-', '_22-', '_27-'),
						$s);

		return $s;
	}
}

?>