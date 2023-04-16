<?php
use Krinkle\Toolbase\BaseTool;
use Krinkle\Toolbase\Html;
use Krinkle\Intuition\Intuition;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../class.php';

global $kgReq;

$tool = new UsageTool();
$int = new Intuition( 'usage' );
$int->registerDomain( 'usage', __DIR__ . '/../i18n' );

$base = BaseTool::newFromArray( array(
	'displayTitle' => 'Usage',
	'remoteBasePath' => dirname( $_SERVER['PHP_SELF'] ),
	'I18N' => $int,
	'styles' => array(
		'main.css',
	),
	'scripts' => array(
		'main.js',
	),
	'sourceInfo' => array(
		'issueTrackerUrl' => 'https://phabricator.wikimedia.org/tag/usage-tool/',
	),
) );
$base->setSourceInfoGerrit( 'labs/tools/usage', dirname( __DIR__ ) );

/**
 * Output
 * -------------------------------------------------
 */

$base->addOut( '<div class="container"><div class="row">' );

switch ( $kgReq->getVal( 'action', 'index' ) ) {
case 'index':

	$base->addOut( '<div class="col-md-9" role="main">' );

	$base->setLayout( 'header', array( 'titleText' => $int->msg( 'index-header-title' ) ) );
	$fileGroups = $tool->getFileGroups();
	$base->addOut( '<ul class="nav nav-pills nav-stacked">' );
	foreach ( $fileGroups as $groupName => $fileGroup ) {
		$base->addOut(
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
	$base->addOut( '</ul>' );

	// Close role=main
	$base->addOut( '</div>' );

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

	$base->setHeadTitle( $groupName );
	$base->setLayout( 'header', array(
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

	$base->addOut( '<div class="col-md-9" role="main">' );

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
		// Create a simple ID that is safe enough to use in a CSS selector
		// without escaping (jquery.toc and bootstrap/scrollspy depend on that)
		$headingId = $tool->makeSafeCssIdent( "stats-{$filename}" );
		$toc .= '<li>' . Html::element( 'a', array( 'href' => "#$headingId" ), $fileLabel ) . '</li>';

		$isEmpty = !reset( $file['wikis'] );

		$heading = htmlspecialchars( $fileLabel );
		if ( !$isEmpty ) {
			// HACK: The GlobalUsage API takes full page name (incl "File:"),
			// but the GlobalUsage Special page takes page title without namespace prefix.
			$filetitle = explode( ':', $filename, 2 )[1] ?? $filename;
			$heading .= ' ('
				. Html::element(
					'a', array(
						'href' => 'https://commons.wikimedia.org/wiki/Special:GlobalUsage/' . rawurlencode($filetitle)
					),
					"{$file['total']} uses"
				)
				. ')';
		}
		$base->addOut( Html::rawElement( 'h3', array( 'id' => $headingId ), $heading ) );
		if ( $isEmpty ) {
			$base->addOut( '<p class="text-muted">No links found</p>' );
		} else {
			$base->addOut( '<ul>' );
			foreach ( $file['wikis'] as $wiki => $total ) {
				$base->addOut(  "$wiki ({$total}×)", 'li' );
			}
			$base->addOut( '</ul>' );
		}
	}

	$toc .= '</ul>';
	$toc .= '<a class="back-to-top" href="#top">Back to top</a>';

	// Close role=main
	$base->addOut( '</div>' );

	$base->addOut( '<div class="col-md-3"><div class="usage-sidebar hidden-print">' . $toc . '</div></div>' );

	break;
default:
	// TODO: Handle unknown action
	break;
}

// Close wrapping container/row
$base->addOut( '</div></div>' );

/**
 * Close up
 * -------------------------------------------------
 */
$base->flushMainOutput();
