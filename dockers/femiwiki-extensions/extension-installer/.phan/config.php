<?php

return [
	'target_php_version' => '8.4',
	'directory_list' => [
		'.',
		'vendor/symfony/process',
	],
	'exclude_analysis_directory_list' => [
		'vendor/',
	],
	'exclude_file_regex' => '@^vendor/(?!symfony/process/)@',
];
