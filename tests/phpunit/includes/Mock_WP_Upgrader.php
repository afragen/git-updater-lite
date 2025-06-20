<?php

if ( ! class_exists( 'WP_Upgrader' ) ) {
	class WP_Upgrader {
		/**
		 * Catches method calls.
		 *
		 * @param string $name The method's name.
		 * @param array  $args  The method's arguments.
		 * @return void
		 */
		public function __call( $name, $args ) {}
	}
}
