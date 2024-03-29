<?php
use Krinkle\Toolbase\Cache;
use Krinkle\Toolbase\KrToolBaseClass;
use Krinkle\Toolbase\Logger;

if (!function_exists( 'array_is_list' )) {
	function array_is_list( array $array ) {
        $i = 0;
        foreach ( $array as $key => $v ) {
            if ( $key !== $i++ ) {
                return false;
            }
        }
        return true;
    }
}

class UsageTool extends KrToolBaseClass {

	const MAX_API_QUERY_CONTINUE = 10;

	protected $settings = array(
		'fileGroups' => __DIR__ . '/fileGroups.json',
	);

	protected $settingsKeys = array(
		'fileGroups',
	);

	/**
	 * Version of array_merge_recursive without overwriting numeric keys
	 *
	 * Based on http://php.net/array_merge_recursive#106985 by Martyniuk Vasyl <martyniuk.vasyl@gmail.com>.
	 *
	 * When using array_merge_recursive, the following goes wrong:
	 *
	 *     data1 = {
	 *       pages: {
	 *         // key is page id in an associative array.
	 *         // not supposed to be treated as numerical index.
	 *         1000: {
	 *           foo: [ 'a', 'b' ]
	 *         },
	 *         1001: {
	 *           foo: [ 'x' ]
	 *         }
	 *     }
	 *     data2 = {
	 *       pages: {
	 *         1000: {
	 *           foo: [ 'c' ]
	 *         },
	 *         1001: {
	 *           foo: [ 'y', 'z' ]
	 *         }
	 *     }
	 *     merged = {
	 *       pages: {
	 *         0: {
	 *           foo: [ 'a', 'b' ]
	 *         },
	 *         1: {
	 *           foo: [ 'x' ]
	 *         },
	 *         2: {
	 *           foo: [ 'c' ]
	 *         },
	 *         3: {
	 *           foo: [ 'y', 'z' ]
	 *         }
	 *     }
	 *     mergedFixed = {
	 *       pages: {
	 *         1000: {
	 *           foo: [ 'a', 'b', 'c' ]
	 *         },
	 *         1001: {
	 *           foo: [ 'x', 'y', 'z' ]
	 *         }
	 *     }
	 *
	 * @param array $base Initial query data array to merge
	 * @param array ...$arrays List of arrays to recursively merge
	 * @return array
	 */
	private static function mergeArrays( $base,...$arrays ) {
		foreach ( $arrays as $array ) {
			foreach ( $array as $key => $value ) {
				if ( isset( $base[$key] ) ) {
					if ( is_array( $value ) && is_array( $base[$key] ) ) {
						if ( array_is_list( $value ) && array_is_list( $base[$key] ) ) {
							$base[$key] = array_merge( $base[$key], $value );
						} else {
							$base[$key] = self::mergeArrays( $base[$key], $value );
						}
					} else {
						$base[$key] = $value;
					}
				} else {
					$base[$key] = $value;
				}
			}
		}

		return $base;
	}

	/**
	 * @param string $apiUrl
	 * @param array $query
	 * @param int $requests Internal parameter
	 * @return array Query data
	 */
	protected function getApiQuery( $apiUrl, array $query, $requests = 0 ) {
		// Request data
		// TODO: Silently fails if no response or unable to decode. Handle error.
		$requests++;

		$data = kfApiRequest( $apiUrl, $query );
		if ( !isset( $data['query'] ) ) {
			return array();
		}
		unset( $data['query']['normalized'] );

		// https://www.mediawiki.org/wiki/API:Query#Continuing_queries
		if ( isset( $data['continue'] ) ) {
			if ( $requests >= self::MAX_API_QUERY_CONTINUE ) {
				Logger::debug( "Reached maximum continue depth ($requests requests)" );
				return $data['query'];
			}
			$nextQuery = array_merge( $query, $data['continue'] );
			$nextQueryData = $this->getApiQuery( $apiUrl, $nextQuery, $requests );
			return self::mergeArrays( $data['query'], $nextQueryData );
		}

		return $data['query'];
	}

	/**
	 * @param string[] $filenames
	 * @return array
	 */
	protected function fetchGlobalUsageForFiles( array $filenames ) {
		// API error 'toomanyvalue': Too many values for "titles". The limit is 50.
		$chunks = array_chunk( $filenames, 50 );
		$pages = array();
		foreach ( $chunks as $chunk ) {
			$query = array(
				'format' => 'json',
				'action' => 'query',
				'prop' => 'globalusage',
				'gulimit' => '500',
				'titles' => implode( '|', $chunk ),
				'continue' => ''
			);

			// GlobalUsage API responses contain urls (e.g. to a local wiki page using the file),
			// that include a protocol. They're not protocol-relative unfortunately.
			$queryData = $this->getApiQuery( 'https://commons.wikimedia.org', $query );
			$pages += $queryData['pages'];
		}

		$stats = array(
			'total' => 0,
			// Keyed by file name, then wiki
			'files' => array(),
		);

		$usage = array(
			'total' => 0,
			// Keyed by wiki, then page, then files used on that page
			'wikis' => array(),
		);

		foreach ( $pages as $pageId => $page ) {
			$statTotal = 0;
			$statWikis = array();

			foreach ( $page['globalusage'] as $use ) {
				$statTotal++;

				if ( isset( $statWikis[ $use['wiki'] ] ) ) {
					$statWikis[ $use['wiki'] ]++;
				} else {
					$statWikis[ $use['wiki'] ] = 1;
				}

				// Fix-up protocol
				$usage['wikis'][ $use['wiki'] ][ $use['title'] ]['url'] = preg_replace( '/^http:\/\//', '//', $use['url'] );
				$usage['wikis'][ $use['wiki'] ][ $use['title'] ]['files'][] = $page['title'];
			}

			arsort( $statWikis );

			$stats['files'][ $page['title'] ] = array(
				'total' => $statTotal,
				'wikis' => $statWikis,
			);
			$stats['total'] += $statTotal;
		}

		ksort( $usage['wikis'] );
		foreach ( $usage['wikis'] as &$pages ) {
			ksort( $pages );
			$usage['total'] += count( $pages );
		}

		return array(
			'stats' => $stats,
			'usage' => $usage,
		);
	}

	/**
	 * @param string $groupName
	 * @return array
	 */
	public function getUsage( $groupName ) {
		global $kgCache;

		$fileGroup = $this->getFileGroup( $groupName );
		// Include hash of fileGroup itself in the cache key to automatically
		// roll-over in case of changes to the configuration.
		$groupHash = sha1( json_encode( $fileGroup ) );

		$key = Cache::makeKey( 'usagetool-globalusageapi-filegroup', $groupName, $groupHash );
		$value = $kgCache->get( $key );
		if ( $value === false ) {
			$value =  $this->fetchGlobalUsageForFiles( array_keys( $fileGroup ) );
			$kgCache->set( $key, $value, 3600 );
		}

		return $value;
	}

	/**
	 * @param string $groupName
	 * @return array|false File names
	 */
	public function getFileGroup( $groupName ) {
		$fileGroups = $this->getFileGroups();
		if ( !isset( $fileGroups[ $groupName ] ) ) {
			return false;
		}
		return $fileGroups[ $groupName ];
	}

	public function makeSafeCssIdent( $text ) {
		// Convert special chars to underscores, and trim remaining underscores
		$safe = preg_replace(
			[
				'/(^[0-9\\-])|[\\x00-\\x20!"#$%&\'()*+,.\\/:;<=>?@[\\]^`{|}~]|\\xC2\\xA0/',
				'/_+/'
			],
			'_',
			$text
		);
		return rtrim( $safe, '_' );
	}

	/**
	 * @return array File groups
	 */
	public function getFileGroups() {
		return json_decode(
			file_get_contents( $this->getSetting( 'fileGroups' ) ),
			/* assoc= */ true
		);
	}
}
