<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'sTrack_UA' ) ) :

class sTrack_UA
{
	/**
	 * Get OS Version
	 *
	 * $ua (user agent), $platform
	 * return : array()
	 *
	 */
	public static function get_os_version($ua = '', $platform = '') 
	{
		if(empty($ua) && empty($platform)) 
		{
			return array('unknown', 0);
		}

		if( preg_match( '/(Windows|Win|NT)[0-9;\s\)\/]/', $ua ) > 0 ) 
		{
			return self::get_win_os_version( $ua );
		}
		elseif( strpos( $ua, 'Intel Mac OS X' ) !== false || strpos( $ua, 'PPC Mac OS X' ) !== false ) 
		{
			return array( 'macosx', 0 );
		}
		elseif( stristr( $ua, 'iPhone' ) !== false || stristr( $ua, 'iPad' ) !== false ) 
		{
			return array( 'ios', 2 );
		}
		elseif( strpos( $ua,'Mac OS X' ) !== false ) 
		{
			return array( 'macosx', 0 );
		}
		elseif( preg_match( '/Android\s?([0-9\.]+)?/', $ua ) > 0 ) 
		{
			return array( 'android', 2 );
		}
		elseif( preg_match( '/[^a-z0-9](BeOS|BePC|Zeta)[^a-z0-9]/', $ua ) > 0 ) 
		{
			return array( 'beos', 0 );
		}
		elseif( preg_match( '/[^a-z0-9](Commodore\s?64)[^a-z0-9]/i', $ua ) > 0 ) 
		{
			return array( 'commodore64', 0 );
		}
		elseif( preg_match( '/[^a-z0-9]Darwin\/?([0-9\.]+)/i', $ua ) > 0 || preg_match( '/[^a-z0-9]Darwin[^a-z0-9]/i', $ua ) > 0 ) 
		{
			return array( 'darwin', 0 );
		}
		elseif( preg_match( '/(Mac_PowerPC|Macintosh)/', $ua ) > 0 ) 
		{
			return array( 'macppc', 0 );
		}
		else if ( preg_match( '/Nintendo\s(Wii|DSi?)?/i', $ua ) > 0 ) {
			return array( 'nintendo', 0 );
		}
		elseif( preg_match( '/[^a-z0-9_\-]MS\-?DOS[^a-z]([0-9\.]+)?/i', $ua ) > 0 ) 
		{
			return array( 'ms-dos', 0 );
		}
		elseif( preg_match( '/[^a-z0-9_\-]OS\/2[^a-z0-9_\-].+Warp\s([0-9\.]+)?/i', $ua ) > 0 ) 
		{
			return array( 'os/2', 0 );
		}
		else if ( stristr( $ua, 'PalmOS' ) !== false ) {
			return array( 'palmos', 2 );
		}
		else if ( preg_match( '/PLAYSTATION\s(\d+)/i', $ua ) > 0 ) {
			return array( 'playstation', 0 );
		}
		else if ( preg_match( '/IRIX\s*([0-9\.]+)?/i', $ua ) > 0 ) {
			return array( 'irix', 0 );
		}
		elseif( preg_match( '/SCO_SV\s([0-9\.]+)?/i', $ua ) > 0 ) 
		{
			return array( 'unix', 0 );
		}
		elseif( preg_match( '/Solaris\s?([0-9\.]+)?/i', $ua ) > 0 ) 
		{
			return array( 'solaris', 0 );
		}
		else if ( preg_match( '/SunOS\s?(i?[0-9\.]+)?/i', $ua ) > 0 ) {
			return array( 'sunos', 0 );
		}
		else if ( preg_match( '/SymbianOS\/([0-9\.]+)/i', $ua ) > 0 ) {
			return array( 'symbianos', 2 );
		}
		elseif( preg_match( '/[^a-z]Unixware\s(\d+(?:\.\d+)?)?/i', $ua ) ) 
		{
			return array( 'unix', 0 );
		}
		elseif( preg_match( '/\(PDA(?:.*)\)(.*)Zaurus/i', $ua ) > 0 ) 
		{
			return array( 'zaurus', 2 );
		}
		elseif( preg_match( '/[^a-z]Unix/i', $ua ) ) 
		{
			return array( 'unix', 0 );
		}
		
		return array( $platform, 0 );
	}

	

	protected static function get_win_os_version( $ua = '' ) 
	{
		if( empty( $ua ) ) 
		{
			return array( 'unknown', 0 );
		}

		if( stristr( $ua, 'Windows NT 10.0' ) !== false ) 
		{
			if( stristr( $ua, 'touch' ) !== false ) 
			{
				return array( 'wi10', 2 );
			}
			else 
			{
				return array( 'win10', 0 );
			}
		}
		
		if( stristr( $ua, 'Windows NT 6.3' ) !== false ) 
		{
			if( stristr( $ua, '; ARM' ) !== false ) 
			{
				return array( 'winrt', 0 );
			}
			elseif( stristr( $ua, 'touch' ) !== false ) 
			{
				return array( 'win8.1', 2 );
			}
			else
			{
				return array( 'win8.1', 0 );
			}
		}
		
		if( stristr( $ua, 'Windows NT 6.2' ) !== false ) 
		{
			if( stristr( $ua, 'touch' ) !== false ) 
			{
				return array( 'win8', 2 );
			}
			else 
			{
				return array( 'win8', 0 );
			}
		}
		
		if( stristr( $ua, 'Windows NT 6.1' ) !== false ) 
		{
			return array( 'win7', 0 );
		}
		
		if( stristr( $ua, 'Windows NT 6.0' ) !== false ) 
		{
			return array( 'winvista', 0 );
		}
		
		if( stristr( $ua, 'Windows NT 5.2' ) !== false ) 
		{
			return array( 'win2003', 0 );
		}
		
		if( stristr( $ua, 'Windows NT 5.1' ) !== false ) 
		{
			return array( 'winxp', 0 );
		}
		
		if( stristr( $ua, 'Windows NT 5.0' ) !== false || strstr( $ua, 'Windows 2000' ) !== false ) 
		{
			return array( 'win2000', 0 );
		}
		
		if( stristr( $ua, 'Windows ME' ) !== false ) 
		{
			return array( 'winme', 0 );
		}
		
		if( preg_match( '/Win(?:dows\s)?NT\s?([0-9\.]+)?/', $ua ) > 0 ) 
		{
			return array( 'winnt', 0 );
		}
		
		if( preg_match( '/(?:Windows98|Windows 98|Win98|Win 98|Win 9x)/', $ua ) > 0 ) 
		{
			return array( 'win98', 0 );
		}

		if( preg_match( '/(?:Windows95|Windows 95|Win95|Win 95)/', $ua ) > 0 ) 
		{
			return array( 'win95', 0 );
		}

		if ( preg_match( '/(?:WindowsCE|Windows CE|WinCE|Win CE)[^a-z0-9]+(?:.*Version\s([0-9\.]+))?/i', $ua ) > 0 ) {
			return array( 'wince', 2 );
		}
		
		if( preg_match( '/(Windows|Win)\s?3\.\d[; )\/]/', $ua ) > 0 ) 
		{
			return array( 'win31', 0 );
		}

		return array( 'unknown',  0 );
	}

}
endif;
?>