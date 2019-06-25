<?php

/**
 * Copyright (C) 2019  NicheWork, LLC
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Mark A. Hershberger <mah@nichework.com>
 */
namespace Fduch\Netrc;

use Psr\Http\Message\RequestInterface;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use Hexmode\HTTPBasicAuth\Client;

class HTTPBasicAuth extends Client {
	/** @var string $originalFile */
	protected $originalFile;
	/**
	 * @param string $url
	 * @param string $netrcFile
	 */
	public function __construct( string $netrcFile = null ) {
		if ( $netrcFile ) {
			$this->originalFile = $netrcFile;
		} else {
			$this->originalFile = Netrc::getDefaultPath();
		}
		$this->cred = Netrc::parse( $netrcFile );
	}

	public function getRequestMapper() :callable {
		return Middleware::mapRequest(
			function ( Request $request ) {
				$uri = $request->getUri();
				$host = $uri->getHost();
				if ( isset( $this->cred[$host] ) ) {
					return $request->withUri(
						$uri->withUserInfo(
							$this->getUsername( (string)$uri ),
							$this->getPassword( (string)$uri )
						)
					);
				}
				return $request;
			}
		);
	}

	protected function getHost( string $url ) :?string {
		$parts = parse_url( $url );
		if ( $parts && isset( $parts['host'] ) ) {
			return $parts['host'];
		}
		return null;
	}

	public function getUsername( string $url ) :string {
		return $this->cred[$this->getHost( $url )]['login'] ?? '';
	}

	public function getPassword( string $url ) :string {
		return $this->cred[$this->getHost( $url )]['password'] ?? '';
	}
}
