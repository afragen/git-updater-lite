<?php

if ( ! class_exists( 'Plugin_Upgrader_Skin' ) ) {
	class Plugin_Upgrader_Skin {
		public function feedback( $message, ...$args ) {}
	}
}

if ( ! class_exists( 'Theme_Upgrader_Skin' ) ) {
	class Theme_Upgrader_Skin {
		public function feedback( $message, ...$args ) {}
	}
}
