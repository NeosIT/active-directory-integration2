<?php
// more functions
if (!function_exists('next_ad_int_hash_equals')) {
	function next_ad_int_hash_equals($a, $b)
	{
		$ret = strlen($a) ^ strlen($b);
		$ret |= array_sum(unpack("C*", $a ^ $b));

		return !$ret;
	}
}

if (!function_exists('next_ad_int_angular_ajax_params_to_post')) {
	function next_ad_int_angular_ajax_params_to_post()
	{
		$isAjax = (defined('DOING_AJAX') && DOING_AJAX);

		if ($isAjax) {
			$params = json_decode(file_get_contents('php://input'), true);

			if (empty($params)) {
				return;
			}

			foreach ($params as $paramKey => $paramVal) {
				$_POST[$paramKey] = $paramVal;
			}
		}
	}
}