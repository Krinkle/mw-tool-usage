<?php
/**
 * Main index
 *
 * @package mw-tool-usage
 * @license http://krinkle.mit-license.org/
 * @author Timo Tijhof, 2010-2014
 */

/**
 * Configuration
 * -------------------------------------------------
 */
// BaseTool & Localization
require_once __DIR__ . '/../lib/basetool/InitTool.php';

// Class for this tool
require_once __DIR__ . '/../class.php';
$kgTool = new KrUsage();

// Local configuration
require_once __DIR__ . '/../config.php';

$toolConfig = array(
	'displayTitle' => 'Usage',
	'remoteBasePath' => dirname( $kgConf->getRemoteBase() ). '/',
	'revisionId' => '0.3.0',
	'styles' => array(
		'main.css',
	),
	'scripts' => array(
		'main.js',
	)
);

$kgBaseTool = BaseTool::newFromArray( $toolConfig );
$kgBaseTool->setSourceInfoGithub( 'Krinkle', 'mw-tool-usage', __DIR__ );

/**
 * Output
 * -------------------------------------------------
 */

$kgBaseTool->doHtmlHead();

$kgBaseTool->addOut( '<div class="container"><div class="row">' );

switch ( $kgReq->getVal( 'action', 'index' ) ) {
case 'index':

	$kgBaseTool->addOut( '<div class="col-md-9" role="main">' );

	$kgBaseTool->setLayout( 'header', array( 'titleText' => 'File groups' ) );
	$fileGroups = $kgTool->getFileGroups();
	$kgBaseTool->addOut( '<ul class="nav nav-pills nav-stacked">' );
	foreach ( $fileGroups as $groupName => $fileGroup ) {
		$kgBaseTool->addOut( '<li>' );
		$kgBaseTool->addOut( $groupName . ' (' . count( $fileGroup ) . ' files)', 'a', array(
			'href' => './?' . http_build_query( array( 'action' => 'usage', 'group' => $groupName ) ),
		) );
		$kgBaseTool->addOut( '</li>' );
	}
	$kgBaseTool->addOut( '</ul>' );

	// Close role=main
	$kgBaseTool->addOut( '</div>' );

	break;
case 'usage':
	$groupName = $kgReq->getVal( 'group' );
	$fileGroup = $kgTool->getFileGroup( $groupName );
	if ( !$fileGroup ) {
		// TODO: Handle unknown group
		break;
	}
	$hc = 0;

	$info = $kgTool->getUsage( $fileGroup );

	$toc = '<ul class="nav usage-sidenav">';

	$kgBaseTool->setHeadTitle( $groupName );
	$kgBaseTool->setLayout( 'header', array(
		'titleText' => 'Statistics for ' . $groupName,
		'captionHtml' => "These " . count( $fileGroup ) . " scripts have a total of <strong>{$info['stats']['total']} links</strong>"
			. " from <strong>{$info['usage']['total']} unique pages</strong>"
			. ' on <strong>' . count( $info['usage']['wikis'] ) . ' different wikis</strong>.'
		)
	);

	$kgBaseTool->addOut( '<div class="col-md-9" role="main">' );

	$toc .= '<li><a href="#header">Statistics</a>';
	$toc .= '<ul class="nav">';
	foreach ( $info['stats']['files'] as $filename => $file ) {
		$fileLabel = $fileGroup[ $filename ];
		$heading = $fileLabel;
		// Use escapeClass instead of escapeId because while escapeId is technically
		// good enough, both jquery.toc and bootstrap/scrollspy rely on the ID
		// being safe to embed in a CSS selector...
		$headingId = Sanitizer::escapeClass( "stats-$filename" );
		$toc .= '<li>' . Html::element( 'a', array( 'href' => "#$headingId" ), $heading ) . '</li>';

		$isEmpty = !reset( $file['wikis'] );
		if ( !$isEmpty ) {
			$heading .= " ({$file['total']} uses)";
		}
		$kgBaseTool->addOut( $heading, 'h3', array( 'id' => $headingId ) );
		if ( $isEmpty ) {
			$kgBaseTool->addOut( '<p class="text-muted">No links found</p>' );
		} else {
			$kgBaseTool->addOut( '<ul>' );
			foreach ( $file['wikis'] as $wiki => $total ) {
				$kgBaseTool->addOut(  "$wiki ($total uses)", 'li' );
			}
			$kgBaseTool->addOut( '</ul>' );
		}
	}
	$toc .= '</ul>';
	$toc .= '</li>';

	$toc .= '<li><a href="#usage">Overview</a>';
	$toc .= '<ul class="nav">';
	$kgBaseTool->addOut( "Overview", 'h2', array( 'id' => 'usage', 'class' => 'page-header' ) );
	foreach ( $info['usage']['wikis'] as $wiki => $pages ) {
		$headingId = Sanitizer::escapeClass( "usage-$wiki" );
		$toc .= '<li>' . Html::element( 'a', array( 'href' => "#$headingId" ), $wiki ) . '</li>';
		$kgBaseTool->addOut( $wiki, 'h3', array( 'id' => $headingId ) );
		$kgBaseTool->addOut( '<ul>' );
		foreach ( $pages as $page => $pageInfo ) {
			$kgBaseTool->addOut( '<li>' );
			$kgBaseTool->addOut( $page, 'a', array( 'href' => $pageInfo['url'] ) );
			$files = array_map( function ( $file ) use ( $fileGroup ) {
				return $fileGroup[ $file ];
			}, $pageInfo['files'] );
			$kgBaseTool->addOut( htmlspecialchars( " (" . implode( ', ', $files ) . ')' ) );
			$kgBaseTool->addOut( '</li>' );
		}
		$kgBaseTool->addOut( '</ul>' );
	}
	$toc .= '</ul>';
	$toc .= '</li>';

	$toc .= '</ul>';
	$toc .= '<a class="back-to-top" href="#top">Back to top</a>';

	// Close role=main
	$kgBaseTool->addOut( '</div>' );

	$kgBaseTool->addOut( '<div class="col-md-3"><div class="usage-sidebar hidden-print">' . $toc . '</div></div>' );

	break;
default:
	// TODO: Handle unknown action
	break;
}

// Close wrapping container/row
$kgBaseTool->addOut( '</div></div>' );

/**
 * Close up
 * -------------------------------------------------
 */
$kgBaseTool->flushMainOutput();
