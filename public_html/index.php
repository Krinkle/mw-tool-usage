<?php
/**
 * Main index
 *
 * @copyright 2014-2018 Timo Tijhof
 */

/**
 * Configuration
 * -------------------------------------------------
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../class.php';

$tool = new Usage();
if ( file_exists( __DIR__ . '/../config.php' ) ) {
	$tool->setSettings( require __DIR__ . '/../config.php' );
}

$int = new Intuition( 'usage' );
$int->registerDomain( 'usage', __DIR__ . '/../i18n' );

$kgBaseTool = BaseTool::newFromArray( array(
	'displayTitle' => 'Usage',
	'remoteBasePath' => dirname( $_SERVER['PHP_SELF'] ),
	'I18N' => $int,
	'styles' => array(
		'main.css',
	),
	'scripts' => array(
		'main.js',
	)
) );
$kgBaseTool->setSourceInfoGithub( 'Krinkle', 'mw-tool-usage', dirname( __DIR__ ) );

/**
 * Output
 * -------------------------------------------------
 */

$kgBaseTool->addOut( '<div class="container"><div class="row">' );

switch ( $kgReq->getVal( 'action', 'index' ) ) {
case 'index':

	$kgBaseTool->addOut( '<div class="col-md-9" role="main">' );

	$kgBaseTool->setLayout( 'header', array( 'titleText' => $int->msg( 'index-header-title' ) ) );
	$fileGroups = $tool->getFileGroups();
	$kgBaseTool->addOut( '<ul class="nav nav-pills nav-stacked">' );
	foreach ( $fileGroups as $groupName => $fileGroup ) {
		$kgBaseTool->addOut(
			'<li>'
			. Html::element( 'a',
				[
					'href' => './?' . http_build_query( array( 'action' => 'usage', 'group' => $groupName ) ),
				],
				$int->msg( 'index-entry-label', [
					'variables' => [ $groupName, count( $fileGroup ) ]
				] )
			)
			. '</li>'
		);
	}
	$kgBaseTool->addOut( '</ul>' );

	// Close role=main
	$kgBaseTool->addOut( '</div>' );

	break;
case 'usage':
	$groupName = $kgReq->getVal( 'group' );
	$fileGroup = $tool->getFileGroup( $groupName );
	if ( !$fileGroup ) {
		// TODO: Handle unknown group
		break;
	}
	$hc = 0;

	$info = $tool->getUsage( $groupName );

	$toc = '<ul class="nav usage-sidenav">';

	$kgBaseTool->setHeadTitle( $groupName );
	$kgBaseTool->setLayout( 'header', array(
		'titleText' => $int->msg( 'group-header-title', [
			'variables' => [ $groupName ],
		] ),
		'captionText' => $int->msg( 'group-header-caption', [
			'variables' => [
				count( $fileGroup ), // $1: scripts
				$info['stats']['total'], // $2: links
				$info['usage']['total'], // $3: pages
				count( $info['usage']['wikis'] ), // $4: wikis
			],
		] ),
	) );

	$kgBaseTool->addOut( '<div class="col-md-9" role="main">' );

	// Sort descending, by total use
	$files = $info['stats']['files'];
	uasort( $files, function ( $a, $b ) {
		if ( $a['total'] === $b['total'] ) {
			return 0;
		}
		return ($a['total'] < $b['total']) ? 1 : -1;
	} );
	foreach ( $files as $filename => $file ) {
		$fileLabel = $fileGroup[ $filename ];
		$heading = $fileLabel;
		// Create a simple ID that is safe enough to use in a CSS selector
		// without escaping (jquery.toc and bootstrap/scrollspy depend on that)
		$headingId = $tool->makeSafeCssIdent( "stats-{$filename}" );
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
				$kgBaseTool->addOut(  "$wiki (${total}×)", 'li' );
			}
			$kgBaseTool->addOut( '</ul>' );
		}
	}

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
