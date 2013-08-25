<?php 

class WXR_Converter { //extends WP_CLI_Command {

	/**
	 * Given an outdated WXR file, convert it to version 1.2ish.
	 * 
	 * @synopsis --file=<file> [--outfile=<file>]
	 */
	public function __invoke( $_, $assoc_args ) {

		$this->max_file_size = 1024 * 1024 * 15;
	
		$defaults = array(
			'file'    => false,
			'outfile' => false,
		);

		$args = wp_parse_args( $assoc_args, $defaults );

		$has_errors = false;

		foreach ( $args as $key => $value ) {
			if ( is_callable( array( $this, 'check_' . $key ) ) ) {
				$result = call_user_func( array( $this, 'check_' . $key ), $value );
				if ( false === $result )
					$has_errors = true;
			}
		}

		if ( $has_errors ){
			WP_CLI::error( "Unable to proceed." );
			exit( 1 );
		}
		$this->convert_wxr( $args['file'], $args['outfile'] );
	}

	private function convert_wxr( $file, $outfile = false ) {
		$lines = file_get_contents( $file );

		preg_match( '!<link>(.*)</link>!', $lines, $matches );
		$url = $matches[1];

		$lines = preg_replace( '!xmlns:wp="http://wordpress.org/export/\d+\.\d+/"!', 'xmlns:wp="http://wordpress.org/export/1.2/"' . "\n\t" . 'xmlns:excerpt="http://wordpress.org/export/1.2/excerpt/"', $lines );

		$lines = str_replace( '<channel>', "<channel>\n<wp:wxr_version>1.2</wp:wxr_version>\n", $lines );
		$lines = str_replace( '<channel>', "<channel>\n<wp:base_site_url>$url</wp:base_site_url>\n", $lines );
		$lines = str_replace( '<channel>', "<channel>\n<wp:base_blog_url>$url</wp:base_blog_url>\n", $lines );
		$lines = str_replace( '<content:encoded>', "<excerpt:encoded><![CDATA[]]></excerpt:encoded>\n\t<content:encoded>", $lines );
		$lines = str_replace( '<content:encoded>', "<wp:post_password></wp:post_password>\n\t<content:encoded>", $lines );
		$lines = str_replace( '<content:encoded>', "<wp:is_sticky>0</wp:is_sticky>\n\t<content:encoded>", $lines );
		$lines = preg_replace( '#<category><!\[CDATA\[(.*)\]\]></category>#', '<category domain="category" nicename="$1"><![CDATA[$1]]></category>', $lines );

		if ( false === $outfile )
			print $lines;
		else
			file_put_contents( $outfile, $lines );
	}

	private function check_outfile( $file ) {
		if ( false !== $file && file_exists( $file ) ) {
			WP_CLI::warning( 'The outfile you specified already exists.' );
			return false;
		}

		return true;
	}

	private function check_file( $file ) {

		if ( false === $file ) {
			WP_CLI::warning( 'Pass a file name using the --file argument.' );
			return false;
		}

		$exists = file_exists( $file );
		$file_size_ok = true;

		if ( false === $exists )
			WP_CLI::warning( 'File does not exist.' );

		if ( filesize( $file ) > $this->max_file_size ) {
			$file_size_ok = false;
			WP_CLI::warning( 'The file was too big to reasonably parse.' );
		}

		return $exists && $file_size_ok;
	}
}

WP_CLI::add_command( 'convert-wxr', 'WXR_Converter' );
