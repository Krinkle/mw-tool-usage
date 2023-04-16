<?php
return [
	'target_php_version' => '7.4',

	'file_list' => [
		'class.php'
	],

	'directory_list' => [
		'vendor/krinkle/',
		'public_html/',
	],

	'exclude_analysis_directory_list' => [
		'vendor/'
	],

	// A list of plugin files to execute.
	//
	// Documentation about available bundled plugins can be found
	// at https://github.com/phan/phan/tree/v3/.phan/plugins
	//
	'plugins' => [
		// Recommended set from mediawiki-phan-config
		'AddNeverReturnTypePlugin',
		'DuplicateArrayKeyPlugin',
		'DuplicateExpressionPlugin',
		'LoopVariableReusePlugin',
		'PregRegexCheckerPlugin',
		'RedundantAssignmentPlugin',
		'SimplifyExpressionPlugin',
		'UnreachableCodePlugin',
		'UnusedSuppressionPlugin',
		'UseReturnValuePlugin',

		// Extra ones:
		'AlwaysReturnPlugin',
		'DollarDollarPlugin',
		'EmptyStatementListPlugin',
		'PrintfCheckerPlugin',
		'SleepCheckerPlugin',
	],

	'suppress_issue_types' => [
		// Warns on PHP 8.1 for conditional.
		// Warns on PHP 7.4 for unused suppression. Suppress here instead.
		'PhanRedefineFunctionInternal',
	],
];
