<?php
$kgTool->setSettings( array(
	'fileGroups' => json_decode( file_get_contents( __DIR__ . '/fileGroups.json' ), /* assoc= */ true )
) );
