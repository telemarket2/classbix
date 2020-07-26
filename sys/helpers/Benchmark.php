<?php

/**
 * ClassiBase Classifieds Script
 *
 * ClassiBase Classifieds Script by Vepa Halliyev is licensed under a Creative Commons Attribution-Share Alike 3.0 License.
 *
 * @package		ClassiBase Classifieds Script
 * @author		Vepa Halliyev
 * @copyright	Copyright (c) 2009, Vepa Halliyev, veppa.com.
 * @license		http://classibase.com
 * @link		http://classibase.com
 * @since		Version 1.0
 * @filesource
 */
class Benchmark
{

	static $counter = 0;
	static $arr = array();
	public static $nocache = '';

	static function setNoCache($set = true)
	{
		if ($set)
		{
			self::$nocache = $_SERVER['REQUEST_TIME'];
		}
		else
		{
			self::$nocache = '';
		}
	}

	static function cp($title = '')
	{

		self::$counter++;

		if (!strlen($title))
		{
			$title = 'CP ' . self::$counter;
		}

		if (!count(self::$arr))
		{
			self::$arr[] = array('t' => $_SERVER['REQUEST_TIME'], 'name' => 'REQUEST_TIME');
			if (defined('FRAMEWORK_STARTING_MICROTIME'))
			{
				self::$arr[] = array('t' => FRAMEWORK_STARTING_MICROTIME, 'name' => 'FRAMEWORK_STARTING_MICROTIME');
			}
		}

		self::$arr[] = array('t' => microtime(true), 'name' => $title, 'memory' => memory_usage());
	}

	static function report()
	{
		self::cp('report');
		$t = microtime(true);
		$num_query = 0;
		$r = '<tr>
				<th class="table_sort table_sort_number">TOTAL TIME</th>
				<th class="table_sort table_sort_number">TIME</th>
				<th class="table_sort table_sort_number">MEMORY</th>
				<th class="table_sort">DESCRIPTION</th>
			</tr>';
		foreach (self::$arr as $val)
		{
			if (!$first)
			{
				$last_key = $first = $val['t'];
			}
			$exec_time = $val['t'] - $last_key;
			$total_time = $val['t'] - $first;
			$r .= '<tr><td>' . number_format($total_time, 6) . '</td>
				<td' . ($exec_time > 1 ? ' class="slow_query"' : '') . '>' . number_format($exec_time, 6) . '</td>
				<td>' . View::escape($val['memory']) . '</td>
				<td class="descr">'
					. View::escape(StringUtf8::strlen($val['name']) > 1000 ? TextTransform::excerpt($val['name']) : $val['name'])
					. '</td>
				</tr>';
			$last_key = $val['t'];
			$_name = strtolower($val['name']);
			$num_query += (strpos($_name, 'select ') !== false || strpos($_name, 'update ') !== false || strpos($_name, 'insert ') !== false) ? 1 : 0;
		}

		if (defined('FRAMEWORK_STARTING_MICROTIME'))
		{
			$php_time = 'php time:<b>' . number_format($last_key - FRAMEWORK_STARTING_MICROTIME, 6) . '</b> | ';
		}
		else
		{
			$php_time = '';
		}
		$summary = 'memory_usage:<b>' . memory_usage() . '</b> | '
				. 'memory_usage(real):<b>' . memory_usage(true) . '</b> | '
				. $php_time
				. 'queries:<b>' . $num_query . '</b>';



		return '<div id="__benchmark_report">'
				. '<h3><a href="#__benchmark_report">'
				. $summary
				. '</a></h3>'
				. '<table class="report">' . $r . '</table>
			</div>
			<style>
			#__benchmark_report{background-color:#fcfcfc; color:#666;font-family:arial,sans-serif;}
			#__benchmark_report h3{font-size:1rem;font-weight:normal;padding:0;margin:0;position:sticky;top:0;bottom:0;}
			#__benchmark_report h3 a{display:block;padding:0.5rem;margin:0;color:black;background:#eee;text-align:center;}
			#__benchmark_report h3 a:hover{background:#f5f5f5;}
			#__benchmark_report table.report{border-collapse: collapse; width: 100%;  border:solid 1px #999; background-color:#fcfcfc; color:#666;}
			#__benchmark_report table.report tr,
			#__benchmark_report .report td,
			#__benchmark_report .report th{ border: solid 1px #ccc; text-align: left;padding:2px; font-size:.8rem; font-family:arial,sans-serif;}
			#__benchmark_report table.report tr:hover,
			#__benchmark_report .report td:hover,
			#__benchmark_report .report th:hover{background-color:#eee;color:#333; }
			#__benchmark_report table.report td.slow_query{background-color:red;color:white;}
			#__benchmark_report table.report td.descr,
			#__benchmark_report table.report th{word-break:break-all;}
			#__benchmark_report table.report_hidden{display:none;}
			</style>
			<script>
			addLoadEvent(function(){
			$(document).on("click","#__benchmark_report h3 a",function(){
				$("#__benchmark_report .report").toggleClass("report_hidden");
			});
			$("#__benchmark_report h3 a").click();
			});
			</script>';
	}

	static public function totalTime()
	{
		$t = microtime(true);
		$start_time = $_SERVER['REQUEST_TIME'];
		if (defined('FRAMEWORK_STARTING_MICROTIME'))
		{
			$start_time = FRAMEWORK_STARTING_MICROTIME;
		}

		return $start_time - $t;
	}

}
