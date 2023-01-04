<?php

use GraphQLRelay\Relay;
use WPGraphQL\Type\WPEnumType;

class MenuQueriesBugTest extends \Tests\WPGraphQL\TestCase\WPGraphQLTestCase {

	public $admin;
	public $location_name;
	public $menu_id;
	public $menu_slug;
	public $menu_item_id;

	public function setUp(): void {
		parent::setUp();
		$this->admin         = $this->factory()->user->create([
			'role' => 'administrator',
		]);
		$this->location_name = 'test-location';
		// register_nav_menu( $this->location_name, 'test menu...' );

		$this->menu_slug = 'my-test-menu';
		$this->menu_id   = wp_create_nav_menu( $this->menu_slug );

		$menu_item_args = [
			'menu-item-title'     => 'Parent Item',
			'menu-item-parent-id' => 0,
			'menu-item-url'       => 'http://example.com/',
			'menu-item-status'    => 'publish',
			'menu-item-type'      => 'custom',
		];

		$this->menu_item_id = wp_update_nav_menu_item( $this->menu_id, 0, $menu_item_args );
	}

	public function tearDown(): void {
		remove_theme_support( 'nav_menus' );
		wp_delete_nav_menu( $this->menu_id );
		// unregister_nav_menu( $this->location_name );

		WPGraphQL::clear_schema();
		parent::tearDown();
	}

	public function testGetMenus() {
		set_theme_mod( 'nav_menu_locations', [ $this->location_name => $this->menu_id ] );

		$query = $this->get_query();

		$actual = $this->graphql( compact( 'query' ) );
		$this->assertArrayNotHasKey( 'errors', $actual, wp_json_encode($actual) );
		$this->assertEquals( $this->menu_id, $actual['data']['menu']['databaseId'] );

		$locations = get_nav_menu_locations();
		$this->assertEquals( WPEnumType::get_safe_name( array_search( $this->menu_id, $locations, true ) ), $actual['data']['menu']['locations'][0] );
	}

	public function get_query() {
		return '
			query menus {
				menus {
					nodes {
						count
						databaseId
						id
						locations
						name
						slug
						menuItems {
							nodes {
								databaseId
							}
						}
					}
				}
			}
		';
	}

}
