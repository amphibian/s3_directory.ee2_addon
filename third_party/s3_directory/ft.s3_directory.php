<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
    This file is part of S3 Directory add-on for ExpressionEngine.

    3 Directory is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    3 Directory is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    Read the terms of the GNU General Public License
    at <http://www.gnu.org/licenses/>.
    
    Copyright 2012 Derek Hogue
*/

class S3_directory_ft extends EE_Fieldtype {

	var $info = array(
		'name'		=> 'S3 Directory',
		'version'	=> '1.0.2'
	);
 
 			
	function __construct()
	{
		ee()->lang->loadfile('s3_directory');	
	}


	function display_settings($settings)
	{
		foreach($this->_get_settings_fields() as $setting)
		{
			ee()->table->add_row(
				ee()->lang->line('s3_directory_'.$setting),
				form_input($setting, (isset($settings[$setting])) ? $settings[$setting] : '', 'id="'.$setting.'"')
			);
		}
	}
	
	
	function display_cell_settings($settings)
	{
		$r = array();
		foreach($this->_get_settings_fields() as $setting)
		{
			$r[] = array(
				ee()->lang->line('s3_directory_'.$setting),
				form_input($setting, (isset($settings[$setting])) ? $settings[$setting] : '')
			);
		}
		return $r;		
	}

	
	function _get_settings_fields()
	{		
		return array(
			'access_key', 'secret_key', 'bucket', 'cdn'
		);
	}
		
			
	
	function save_settings($data)
	{
		$cdn = trim(ee()->input->post('cdn'));
		$cdn = str_replace(array('http://','https://'),'',$cdn); // Make sure they didn't enter http or https
		$cdn = rtrim($cdn,'/'); // Trim the trailing slash
		return array(
			'access_key' => trim(ee()->input->post('access_key')),
			'secret_key' => trim(ee()->input->post('secret_key')),
			'bucket' => trim(ee()->input->post('bucket')),
			'cdn' => $cdn,
		);
	}
	
	function save($data)
	{
		if( !empty($data) )
		{
			return $data;
		}
		return false;
	}

	
	function save_cell($data)
	{
		return $this->save($data);
	}


	function display_field($data)
	{
		return $this->display($data, $this->field_name);
	}
	
	
	function display_cell($data)
	{
		return $this->display($data, $this->cell_name);
	}	
	
	
	function display($data, $name)
	{
		if(
			!empty($this->settings['access_key']) &&
			!empty($this->settings['secret_key']) &&
			!empty($this->settings['bucket'])
		)
		{
			if(!class_exists('S3'))
			{
				require_once PATH_THIRD.'s3_directory/libraries/S3.php';
			}
			$s3 = new S3($this->settings['access_key'], $this->settings['secret_key']);
			$files_array = $s3->getBucket($this->settings['bucket']);
			$files = array('' => '--');
			
			foreach($files_array as $k => $array)
			{
				$files[base64_encode(serialize($array))] = $k;
			}
				
			$r = form_dropdown($name, $files, $data);
		}
		else
		{
			$r = ee()->lang->line('s3_directory_not_setup');
		}
		return $r;
	}
	
	
	function replace_tag($data, $params = array(), $tagdata = FALSE)
	{
		return $this->replace_url($data);
	}
	
	
	function replace_url($data, $params = array(), $tagdata = FALSE)
	{
		$data = $this->_prepare_data($data);
		$r = (isset($params['ssl'])) ? 'https://' : 'http://';
		if ($this->settings['cdn'] != '')
		{ // Are we using a CDN?
			$r .= $this->settings['cdn'].'/'.rawurlencode($data['name']);
		}
		else
		{
			$r .= $this->settings['bucket'].'.s3.amazonaws.com/'.rawurlencode($data['name']);
		}
		return $r;
	}


	function replace_name($data, $params = array(), $tagdata = FALSE)
	{
		$data = $this->_prepare_data($data);
		return $data['name'];
	}
		
	
	function replace_size($data, $params = array(), $tagdata = FALSE)
	{
		$data = $this->_prepare_data($data);
		return $data['size'];		
	}


	function replace_human_size($data, $params = array(), $tagdata = FALSE)
	{
		$data = $this->_prepare_data($data);
		return $this->_bytesToSize($data['size']);
	}
	

	function replace_date($data, $params = array(), $tagdata = FALSE)
	{
		$data = $this->_prepare_data($data);
		if(isset($params['format']))
		{
			$r = ee()->localize->format_date($params['format'], $data['time']);
		}
		else
		{
			$r = $data['time'];
		}
		return $r;		
	}


	function replace_gmt_date($data, $params = array(), $tagdata = FALSE)
	{
		$data = $this->_prepare_data($data);
		if(isset($params['format']))
		{
			$r = ee()->localize->format_date($params['format'], $data['time'], FALSE);
		}
		else
		{
			$r = $data['time'];
		}
		return $r;		
	}


	function zenbu_display($entry_id, $channel_id, $data, $table_data = array(), $field_id, $settings, $rules = array())
	{
		$data = $this->_prepare_data($data);
		return $data['name'];
	}
	
	
	function _prepare_data($data)
	{
		return unserialize(base64_decode($data));
	}
	
	
	function _bytesToSize($bytes, $precision = 2)
	{	
		$kilobyte = 1024;
		$megabyte = $kilobyte * 1024;
		$gigabyte = $megabyte * 1024;
		$terabyte = $gigabyte * 1024;
		
		if (($bytes >= 0) && ($bytes < $kilobyte)) {
			return $bytes . ' B';
	
		} elseif (($bytes >= $kilobyte) && ($bytes < $megabyte)) {
			return round($bytes / $kilobyte, $precision) . ' KB';
	
		} elseif (($bytes >= $megabyte) && ($bytes < $gigabyte)) {
			return round($bytes / $megabyte, $precision) . ' MB';
	
		} elseif (($bytes >= $gigabyte) && ($bytes < $terabyte)) {
			return round($bytes / $gigabyte, $precision) . ' GB';
	
		} elseif ($bytes >= $terabyte) {
			return round($bytes / $terabyte, $precision) . ' TB';
		} else {
			return $bytes . ' B';
		}
	}	
	

}