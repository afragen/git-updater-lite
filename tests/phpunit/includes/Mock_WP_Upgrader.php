<?php

if ( ! class_exists( 'WP_Upgrader' ) ) {
	class WP_Upgrader {
		/** @var object */
		public $skin;

		/**
		 * Constructor.
		 *
		 * @param object $skin Upgrader skin.
		 */
		public function __construct( $skin = null ) {
			$this->skin = $skin;
		}

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
