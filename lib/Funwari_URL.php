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

// URL���Ǘ�

class Funwari_URL {

	private $protocol = '';
	private $domain = '';
	private $path = '';
	private $org_path = '';

	// �R���X�g���N�^
	function __construct($url_path='') {
		$this->Set($url_path);
	}


	// ���Z�b�g
	function Reset() {
		$this->protocol = '';
		$this->domain = '';
		$this->path = '';
		$this->org_path = '';
	}


	// �ݒ�
	function Set($url_path) {
		$this->Reset();
		$this->org_path = $url_path;

		// URL�Ȃ�v���g�R�����擾
		if( $this->IsURL($url_path) ) {

			// �v���g�R���擾
			$this->protocol = $this->GetProtocolFromURL($url_path);

			// �v���g�R���������폜
			//$url_path = $this->ChopProtocol($url_path, $this->protocol);

			// �h���C���擾
			// ����B�����͂ƂĂ����r���[
			$this->domain = $this->GetDomainFromURL($url_path);

			// �h���C���܂ō폜
			$url_path = $this->ChopDomain($url_path);
		}

		// ���Ƃ͑��΃p�X�ł��낤��, ��΃p�X�ł��낤��
		// path�ɐݒ肷��̂�
		$this->path = $url_path;

		return true;
	}


	// �ړ�
	function Move($url_path) {
		// �v���g�R�����瑶�݂���悤�ȃp�X�Ȃ�ݒ肵�Ȃ�������
		if( $this->IsURL($url_path) ) {
			return $this->Set($url_path);
		}

		// ��΃p�X�Ȃ�, �v���g�R��, �h���C���͂��̂܂܂Ƀp�X�݂̂�
		// �ݒ肷��
		if( $this->IsAbsolute($url_path) ) {
			$this->path = $url_path;
			return true;
		}

		// �c��͑��΃p�X����, ���ꂪ�ʓ|�������B
		$this->MoveRelative($url_path);
		return true;
	}


	// ���΃p�X�ړ�
	// ���̃N���X, ������Move, MoveRelative�ȊO����Ă΂�邱�Ƃ͂Ȃ��B
	private function MoveRelative($path) {
		// �p�X��؂�������Ă���Ȃ�, �������čċA
		if( preg_match('/[\/\\\\]/', $path) ) {
			$dir_list = preg_split('/[\/\\\\]/', $path);
			foreach($dir_list as $i_dir) {
				$this->MoveRelative($i_dir);
			}
			return true;
		}

		// ��؂蕶�����Ȃ�

		// �ړ����Ȃ�
		if( $path == '.' || $path == '' ) {
			return true;
		}

		// ���Ɉړ�
		if( $path == '..' ) {
			$new_path = $this->path;

			// �Ō�Ƀf�B���N�g����؂蕶�����������珜���Ă���
			$new_path = preg_replace('/[\/\\\\]$/', '', $new_path);

			// ���݂̃f�B���N�g�����폜
			$new_path = dirname($new_path);

			$this->path = $new_path;
			return true;
		}

		// ���ӁB�����ł�. ���Ɉړ��͎������Ȃ�

		// . �������� .. �ȊO�̃s���I�h����݂̂Ȃ�f�B���N�g���̏ꍇ
		// �����ł͉������Ȃ��ɂ���B
		if( preg_match('/^\.*$/', $path) ) {
			return true;
		}

		// ���̑� = �T�u�f�B���N�g�����w��
		$new_path = $this->path;

		// �Ō�Ƀf�B���N�g����؂蕶�����������珜���Ă���
		$new_path = preg_replace('/[\/\\\\]$/', '', $new_path);

		$new_path .= '/' . $path;

		$this->path = $new_path;
		return true;
	}


	// �v���g�R�����擾
	function GetProtocolFromURL($url_path) {
		if( !preg_match('/:\/\//', $url_path) ) {
			return '';
		}

		$protocol = preg_replace('/:\/\/.*$/', '', $url_path);

		return $protocol;
	}


	// �v���g�R����؂藎�Ƃ�
	function ChopProtocol($url_path, $protocol) {
		return substr($url_path, strlen($protocol)+3);		// strlen(://) = 3
	}


	// �h���C�����擾
	function GetDomainFromURL($url) {
		// �܂��p�����[�^�𗎂Ƃ�
		$target_domain = preg_replace('/\?.*$/', '', $url);
		// �v���g�R��������؂藎�Ƃ�
		$target_domain = preg_replace('/^[^:=\/]*:\/\//', '', $target_domain);
		// �p�X������؂藎�Ƃ�
		$target_domain = preg_replace('/\/.*$/', '', $target_domain);

		return $target_domain;
	}


	// �h���C�������܂Ő؂藎�Ƃ�
	// URL�`���ɂȂ��Ă���ꍇ�ɂ����Ή��ł��Ă��Ȃ�
	function ChopDomain($url_path) {
		$path = preg_replace('/^.*:\/\/[^\/]*/', '', $url_path);

		// �h���C�������������, path���Ȃ��Ȃ邱�Ƃ͂��肦��
		if( $path == '' ) {
			$path = '/';
		}

		return $path;
	}


	// �y�[�W�������N���폜
	static function ChopInternalLink($url_path) {
		$url_path = preg_replace('/#.*\?/', '?', $url_path);
		$url_path = preg_replace('/#.*$/', '', $url_path);

		return $url_path;
	}


	// �S���̃A�h���X���擾
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


	// �v���g�R����Ԃ�
	function GetProtocol() {
		return $this->protocol;
	}


	// �h���C����Ԃ�
	function GetDomain() {
		return $this->domain;
	}


	// �p�X��Ԃ�
	function GetPath() {
		return $this->path;
	}


	// URL��?
	function IsURL($path) {
		if(preg_match('/^http:\/\//', $path)
			|| preg_match('/^htts:\/\//', $path)) {
			return true;
		}

		return false;
	}


	// ��΃p�X��
	function IsAbsolute($path) {
		if(preg_match('/^\//', $path)) {
			return true;
		}

		return false;
	}


	// ���΃p�X��
	function IsRelative($path) {
		return !$this->IsAbsolute($path);
	}

}

?>