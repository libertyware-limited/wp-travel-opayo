<?php

declare(strict_types=1);

/**
 * @package Liberty-Travel-System-Wordpress
 */

namespace Libertyware\TravelSystem\Wordpress\API;

class SettingsAPI {

	public array $admin_pages   = array();
	public array $admin_subpages = array();

	public function register() {
		if ( ! empty( $this->admin_pages ) ) {
			add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		}
	}

	public function add_pages( array $pages ): SettingsAPI {
		$this->admin_pages = $pages;

		return $this;
	}

	public function add_subpages( array $pages ): SettingsAPI {
		$this->admin_subpages = array_merge( $this->admin_subpages, $pages );

		return $this;
	}

	public function with_subpage( string $tile = null ): SettingsAPI {
		if ( empty( $this->admin_pages ) ) {
			return $this;
		}

		$admin_page = $this->admin_pages[0];
		$subpages    = array(
			array(
				'parent_slug' => $admin_page['menu_slug'],
				'page_title'  => $admin_page['page_title'],
				'menu_title'  => $tile ? $tile : $admin_page['menu_title'],
				'capability'  => $admin_page['capability'],
				'menu_slug'   => $admin_page['menu_slug'],
				'callback'    => $admin_page['callback'],
			),
		);

		$this->admin_subpages = $subpages;

		return $this;
	}

	public function add_admin_menu() {
		foreach ( $this->admin_pages as $page ) {
			add_menu_page(
				$page['page_title'],
				$page['menu_title'],
				$page['capability'],
				$page['menu_slug'],
				$page['callback'],
				$page['icon_url'],
				$page['position'],
			);
		}

		foreach ( $this->admin_subpages as $page ) {
			add_submenu_page(
				$page['parent_slug'],
				$page['page_title'],
				$page['menu_title'],
				$page['capability'],
				$page['menu_slug'],
				$page['callback'],
			);
		}
	}

}
