<?php

if ( ! class_exists( 'WP_Plugins_List_Table' ) ) {
	_get_list_table( 'WP_Plugins_List_Table' );
}


class BF_Product_Plugin_List_Table extends WP_Plugins_List_Table {


	public function prepare_items() {

		global $page, $s;

		$plugins = $this->get_plugins_list();

		if ( ! empty( $_REQUEST['s'] ) ) {
			$s       = wp_unslash( $_REQUEST['s'] );
			$plugins = $this->filter_plugins( $plugins );
		}

		$total_items = bf_count( $plugins );

		$plugins_per_page = 50;
		$this->items      = $plugins;
		$start            = ( $page - 1 ) * $plugins_per_page;

		if ( $total_items > $plugins_per_page ) {
			$this->items = array_slice( $this->items, $start, $plugins_per_page );
		}

		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'per_page'    => $plugins_per_page,
		) );
	}


	protected function filter_plugins( $plugins ) {

		return array_filter( $plugins, array( $this, 'filter_by_title' ) );
	}


	protected function filter_by_title( $plugin ) {

		global $s;

		return isset( $plugin['name'] ) && mb_strpos( strtolower( $plugin['name'] ), strtolower( $s ) ) !== false;
	}


	protected function badge_classes( $plugin_info ) {

		$classes = array();

		if ( ! empty( $plugin_info['is_premium'] ) ) {

			$classes[] = 'premium';
		}

		if ( ! empty( $plugin_info['is_exclusive'] ) ) {

			$classes[] = 'exclusive';
		}

		if ( ! empty( $plugin_info['is_compatible'] ) ) {

			$classes[] = 'compatible';
		}


		return $classes;
	}


	public function single_row( $item ) {

		global $s, $page;

		list( $plugin_ID, $plugin_info ) = $item;

		$actions          = array();
		$status           = $this->active_status();
		$plugin_file      = BF_Product_Plugin_Installer::the_plugin_file( $plugin_ID );
		$plugin_installed = BF_Product_Plugin_Installer::is_plugin_installed( $plugin_ID );
		$plugin_activated = $plugin_installed && BF_Product_Plugin_Installer::is_plugin_active( $plugin_ID );

		if ( $plugin_data = get_plugins( "/$plugin_ID" ) ) {

			$plugin_data = array_shift( $plugin_data );
		}

		// Plugin Update Available
		$update_available = $plugin_activated && $this->is_update_available( $plugin_ID, $plugin_file, $plugin_data );
		$page_slug        = isset( $_GET['page'] ) ? $_GET['page'] : '';
		$classes          = $this->badge_classes( $plugin_info );

		if ( empty( $plugin_info['have_access'] ) ) {
			$classes[] = 'plugin-not-available';
			$classes[] = 'plugin-not-installed';
		}


		if ( $update_available ) {
			$classes[] = 'plugin-update-available';
		}

		if ( $plugin_installed ) {

			$classes[] = 'plugin-installed';

		} elseif ( ! empty( $plugin_info['have_access'] ) ) {

			$actions['install'] = '<a href="' . wp_nonce_url( 'admin.php?page=' . $page_slug . '&action=install&amp;plugin=' . urlencode( $plugin_ID ) . '&amp;plugin_status=' . $status . '&amp;paged=' . $page . '&amp;s=' . $s, 'install-plugin_' . $plugin_ID ) . '" aria-label="' . esc_attr( sprintf( _x( 'Download & Activate %s', 'publisher' ), $plugin_info['name'] ) ) . '">' . __( 'Download & Activate', 'publisher' ) . '</a>';

			$classes[] = 'plugin-not-installed';
		}

		if ( $plugin_activated ) {

			$actions['deactivate'] = '<a href="' . wp_nonce_url( 'admin.php?page=' . $page_slug . '&action=deactivate&amp;plugin=' . urlencode( $plugin_ID ) . '&amp;plugin_status=' . $status . '&amp;paged=' . $page . '&amp;s=' . $s, 'deactivate-plugin_' . $plugin_ID ) . '" aria-label="' . esc_attr( sprintf( _x( 'Deactivate %s', 'publisher' ), $plugin_info['name'] ) ) . '">' . __( 'Deactivate', 'publisher' ) . '</a>';
			$classes[]             = 'active';

		} elseif ( $plugin_installed ) {

			$actions['activate'] = '<a href="' . wp_nonce_url( 'admin.php?page=' . $page_slug . '&action=activate&amp;plugin=' . urlencode( $plugin_ID ) . '&amp;plugin_status=' . $status . '&amp;paged=' . $page . '&amp;s=' . $s, 'activate-plugin_' . $plugin_ID ) . '" class="edit" aria-label="' . esc_attr( sprintf( _x( 'Activate %s', 'publisher' ), $plugin_info['name'] ) ) . '">' . __( 'Activate', 'publisher' ) . '</a>';
			$actions['delete']   = '<a href="' . wp_nonce_url( 'admin.php?page=' . $page_slug . '&action=delete&amp;plugin=' . urlencode( $plugin_ID ) . '&amp;plugin_status=' . $status . '&amp;paged=' . $page . '&amp;s=' . $s, 'delete-plugin_' . $plugin_ID ) . '" aria-label="' . esc_attr( sprintf( _x( 'Delete %s', 'publisher' ), $plugin_info['name'] ) ) . '">' . __( 'Delete', 'publisher' ) . '</a>';
			$classes[]           = 'inactive';
		}

		$plugin_desc_actions = array();

		if ( ! empty( $plugin_data['Version'] ) ) {

			$plugin_desc_actions[] = sprintf(
				__( 'Version %s', 'publisher' ),
				$plugin_data['Version']
			);
		}

		$plugin_desc_actions[] = sprintf(
			__( 'By %s', 'publisher' ),
			'<a href="' . $plugin_info['author_uri'] . '">' . $plugin_info['author'] . '</a>'
		);
		?>

        <tr class="<?php echo implode( ' ', $classes ) ?>" data-slug="<?php echo $plugin_ID ?>" data-plugin="<?php echo esc_attr($plugin_file) ?>">

			<?php if ( bf_is_product_registered() ) { ?>
				<th scope="row" class="check-column"
				    rowspan="<?php echo $update_available ? 2 : 1 ?>">
					<label class="screen-reader-text"><?php echo $plugin_info['name'] ?></label>
					<input type="checkbox" name="checked[]" value="<?php echo $plugin_ID ?>">
				</th>

			<?php } ?>

			<td class="plugin-thumbnail"
			    rowspan="<?php echo $update_available ? 2 : 1 ?>">
				<img src="<?php echo $plugin_info['thumbnail'] ?>" width="94" height="94"
				     class="plugin-table-screenshot">
			</td>

			<td class="plugin-title column-primary">

				<strong><?php
					echo $plugin_info['name'];

					if ( ! empty( $plugin_info['is_new'] ) ) {
						echo '<span class="bs-pages-badge bs-pages-badge-new">', __( 'New', 'publisher' ), '</span>';
					}

					?></strong>

				<?php

				if ( empty( $plugin_info['have_access'] ) && empty( $actions ) ) {
					?>
					<span class="bs-upgrade-notice">

					<?php

					if ( bf_is_product_registered() ) {

						if ( ! empty( $plugin_info['plans'] ) ) {
							$plans_list = implode( __( ' or ', 'publisher' ), $plugin_info['plans'] );
						} else {

							$plans_list = __( 'higher', 'publisher' );
						}

						printf(
							__( '<strong>This plugin is not available for your license.</strong> <a href="%s" target="_blank">Upgrade to %s plan</a> for accessing this plugin.', 'publisher' ),
							'https://betterstudio.com/account/license-manager/',
							$plans_list
						);
					}
					?>
					</span>

				<?php } ?>
				<?php

				if ( bf_is_product_registered() ) {

					echo $this->row_actions( $actions, true );

				} else {

					echo '<div class="bs-upgrade-notice">';
					_e( '<strong>Please register your theme.</strong>', 'publisher' );
					echo '</div>';
				}

				?>

				<button type="button" class="toggle-row">
					<span class="screen-reader-text"><?php _e( 'Show more details', 'publisher' ) ?></span>
				</button>
			</td>
			<td class="column-description desc">
				<div class="plugin-description">
					<p><?php echo $plugin_info['description'] ?></p>
				</div>
				<div class="second plugin-version-author-uri">
					<?php

					if ( ! empty( $plugin_info['is_premium'] ) ) {

						echo '<span class="bs-pages-badge bs-pages-badge-premium">', __( 'Premium', 'publisher' ), '</span>';
					}
					if ( ! empty( $plugin_info['is_exclusive'] ) ) {

						echo '<span class="bs-pages-badge bs-pages-badge-exclusive">', __( 'Exclusive', 'publisher' ), '</span>';
					}
					if ( ! empty( $plugin_info['is_compatible'] ) ) {

						echo '<span class="bs-pages-badge bs-pages-badge-compatible">', __( 'Compatible', 'publisher' ), '</span>';
					}
					?>
					<?php echo implode( ' | ', $plugin_desc_actions ); ?>
                </div>
            </td>
            <td class="column-auto-updates"><?php $this->update_column( $plugin_data, $plugin_file ) ?></td>
        </tr>
		<?php

		if ( $plugin_file && $plugin_activated && $update_available ) {

			$this->wp_plugin_update_row( $plugin_ID, $plugin_file, $plugin_data );
		}
	}

	/**
     * Print auto-update html.
     *
	 * @param array $plugin_data
	 * @param string $plugin_file
     *
     * @since 3.11.11
	 */
	protected function update_column( $plugin_data, $plugin_file ) {
		global $page, $status;

		static $auto_updates;

		if ( ! isset( $auto_updates ) ) {
			$auto_updates = (array) get_site_option( 'auto_update_plugins', array() );
		}

		if ( isset( $plugin_data['auto-update-forced'] ) ) {
			if ( $plugin_data['auto-update-forced'] ) {
				// Forced on.
				$text = __( 'Auto-updates enabled' );
			} else {
				$text = __( 'Auto-updates disabled' );
			}
			$action     = 'unavailable';
			$time_class = ' hidden';

		} elseif ( in_array( $plugin_file, $auto_updates, true ) ) {
			$text       = __( 'Disable auto-updates' );
			$action     = 'disable';
			$time_class = '';
		} else {
			$text       = __( 'Enable auto-updates' );
			$action     = 'enable';
			$time_class = ' hidden';
		}

		$query_args = array(
			'action'        => "{$action}-auto-update",
			'plugin'        => $plugin_file,
			'paged'         => $page,
			'plugin_status' => $status,
		);

		$html = [];
		$url  = add_query_arg( $query_args, 'plugins.php' );

		if ( 'unavailable' == $action ) {
			$html[] = '<span class="label">' . $text . '</span>';
		} else {
			$html[] = sprintf(
				'<a href="%s" class="toggle-auto-update aria-button-if-js" data-wp-action="%s">',
				wp_nonce_url( $url, 'updates' ),
				$action
			);

			$html[] = '<span class="dashicons dashicons-update spin hidden" aria-hidden="true"></span>';
			$html[] = '<span class="label">' . $text . '</span>';
			$html[] = '</a>';
		}

		if ( ! empty( $plugin_data['update'] ) ) {
			$html[] = sprintf(
				'<div class="auto-update-time%s">%s</div>',
				$time_class,
				wp_get_auto_update_message()
			);
		}

		echo implode( '', $html );
	}


	/**
	 * Check if plugin update is available.
	 *
	 * @param string $plugin_ID
	 * @param string $plugin_file
	 * @param array  $plugin_data
	 *
	 * @return bool true if available.
	 */
	protected function is_update_available( $plugin_ID, $plugin_file, $plugin_data ) {

		if ( BF_Product_Plugin_Installer::is_plugin_latest_version( $plugin_ID ) ) {
			return false;
		}

		// Check local plugin version with remote one version.
		if ( ! empty( $plugin_data['Version'] ) ) {

			$status = get_option( 'bs-product-plugins-status' );

			if ( isset( $status->remote_plugins[ $plugin_file ]['new_version'] ) ) {

				return version_compare(
					$status->remote_plugins[ $plugin_file ]['new_version'], // remote version
					$plugin_data['Version'], // local version
					'>'
				);
			}
		}

		return true;
	}

	/**
	 *
	 * @global string $status
	 * @return array
	 */
	protected function get_views() {

		$page_slug    = isset( $_GET['page'] ) ? $_GET['page'] : '';
		$status_links = array();

		$status = $this->active_status();

		foreach ( $this->get_plugins_stat() as $type => $count ) {
			if ( ! $count ) {
				continue;
			}

			switch ( $type ) {
				case 'all':
					$text = _nx( 'All <span class="count">(%s)</span>', 'All <span class="count">(%s)</span>', $count, 'publisher' );
					break;

				case 'active':
					$text = _n( 'Active <span class="count">(%s)</span>', 'Active <span class="count">(%s)</span>', $count, 'publisher' );
					break;

				case 'inactive':
					$text = _n( 'Inactive <span class="count">(%s)</span>', 'Inactive <span class="count">(%s)</span>', $count, 'publisher' );
					break;

				case 'premium':
					$text = _n( 'Inactive <span class="count">(%s)</span>', 'Premium <span class="count">(%s)</span>', $count, 'publisher' );
					break;

				case 'exclusive':
					$text = _n( 'Inactive <span class="count">(%s)</span>', 'Exclusive <span class="count">(%s)</span>', $count, 'publisher' );
					break;

				case 'compatible':
					$text = _n( 'Inactive <span class="count">(%s)</span>', 'Compatible <span class="count">(%s)</span>', $count, 'publisher' );
					break;
			}

			if ( 'search' !== $type ) {

				$status_links[ $type ] = sprintf( "<a href='%s' %s>%s</a>",
					add_query_arg( 'plugin_status', $type, 'admin.php?page=' . $page_slug ),
					( $type === $status ) ? ' class="current"' : '',
					sprintf( $text, number_format_i18n( $count ) )
				);
			}
		}

		return $status_links;
	}


	/**
	 * @param array $plugin
	 *
	 * @access internal
	 *
	 * @return bool
	 */
	protected function is_plugin_active( $plugin ) {

		return BF_Product_Plugin_Installer::is_plugin_installed( $plugin['slug'] )
		       &&
		       BF_Product_Plugin_Installer::is_plugin_active( $plugin['slug'] );
	}


	/**
	 * @param array $plugin
	 *
	 * @access internal
	 *
	 * @return bool
	 */
	protected function is_plugin_inactive( $plugin ) {

		return ! BF_Product_Plugin_Installer::is_plugin_installed( $plugin['slug'] )
		       ||
		       ! BF_Product_Plugin_Installer::is_plugin_active( $plugin['slug'] );
	}


	/**
	 * Prepare plugins list to view
	 *
	 * @access internal usage
	 * @return array
	 */
	protected function get_plugins_list() {

		$status  = $this->active_status();
		$plugins = bf_get_plugins_config();

		if ( 'active' === $status ) {

			$plugins = array_filter( $plugins, array( $this, 'is_plugin_active' ) );

		} elseif ( 'inactive' === $status ) {

			$plugins = array_filter( $plugins, array( $this, 'is_plugin_inactive' ) );

		} elseif ( 'premium' === $status ) {

			$plugins = array_filter( $plugins, array( $this, 'is_plugin_premium' ) );

		} elseif ( 'exclusive' === $status ) {

			$plugins = array_filter( $plugins, array( $this, 'is_plugin_exclusive' ) );

		} elseif ( 'compatible' === $status ) {

			$plugins = array_filter( $plugins, array( $this, 'is_plugin_compatible' ) );
		}

		return $plugins;
	}


	/**
	 * @param string $file
	 *
	 * @return bool
	 */
	protected function is_wp_plugin_update_available( $file ) {

		static $current;

		if ( ! isset( $current ) ) {
			$current = get_site_transient( 'update_plugins' );
		}

		return isset( $current->response[ $file ] );
	}


	/**
	 * @param string $plugin_slug
	 * @param string $file
	 * @param array  $plugin_data
	 *
	 * @return bool
	 */
	function wp_plugin_update_row( $plugin_slug, $file, $plugin_data ) {

		$plugins_allowedtags = array(
			'a'       => array( 'href' => array(), 'title' => array() ),
			'abbr'    => array( 'title' => array() ),
			'acronym' => array( 'title' => array() ),
			'code'    => array(),
			'em'      => array(),
			'strong'  => array(),
		);

		$page_slug   = isset( $_GET['page'] ) ? $_GET['page'] : '';
		$plugin_name = wp_kses( $plugin_data['Name'], $plugins_allowedtags );
		$details_url = '';

		if ( 'betterstudio' !== strtolower( $plugin_data['Author'] ) ) {
			$details_url = self_admin_url( 'plugin-install.php?tab=plugin-information&plugin=' . $plugin_slug . '&section=changelog&TB_iframe=true&width=600&height=800' );
		}

		//		if ( ! is_network_admin() && is_multisite() ) {
		//			return;
		//		}

		if ( is_network_admin() ) {
			$active_class = is_plugin_active_for_network( $file ) ? ' active' : '';
		} else {
			$active_class = is_plugin_active( $file ) ? ' active' : '';
		}

		echo '<tr class="plugin-update-tr' . $active_class . '" id="' . esc_attr( $plugin_slug . '-update' ) . '" data-slug="' . esc_attr( $plugin_slug ) . '" data-plugin="' . esc_attr( $file ) . '"><td colspan="3" class="plugin-update colspanchange"><div class="update-message notice inline notice-warning notice-alt"><p>';

		if ( ! current_user_can( 'update_plugins' ) ) {

			_e( 'There is a new version available.', 'publisher' );

		} else {


			printf( __( 'There is a new version available. <a href="%1$s" %2$s>update now</a>.', 'publisher' ),
				wp_nonce_url( self_admin_url( 'admin.php?page=' . $page_slug . '&action=upgrade&plugin=' ) . $plugin_slug, 'upgrade-plugin_' . $plugin_slug ),
				sprintf( 'aria-label="%s"',
					/* translators: %s: plugin name */
					esc_attr( sprintf( __( 'Update %s now', 'publisher' ), $plugin_name ) )
				)
			);
		}

		echo '</p></div></td></tr>';
	}


	public function print_column_headers( $with_id = true ) {

		ob_start();
		parent::print_column_headers( $with_id );
		$html = ob_get_clean();

		echo str_replace(
			"class='manage-column column-name",
			"colspan='2' class='manage-column column-name",
			$html
		);
	}


	protected function get_bulk_actions() {

		return bf_is_product_registered() ? parent::get_bulk_actions() : array();
	}


	protected function is_plugin_premium( $plugin ) {

		return ! empty( $plugin['is_premium'] );
	}


	protected function is_plugin_exclusive( $plugin ) {

		return ! empty( $plugin['is_exclusive'] );
	}


	protected function is_plugin_compatible( $plugin ) {

		return ! empty( $plugin['is_compatible'] );
	}


	public function get_plugins_stat() {

		$all_plugins = bf_get_plugins_config();

		return array(
			'all'        => bf_count( $all_plugins ),
			'active'     => bf_count( array_filter( $all_plugins, array( $this, 'is_plugin_active' ) ) ),
			'premium'    => bf_count( array_filter( $all_plugins, array( $this, 'is_plugin_premium' ) ) ),
			'inactive'   => bf_count( array_filter( $all_plugins, array( $this, 'is_plugin_inactive' ) ) ),
			'exclusive'  => bf_count( array_filter( $all_plugins, array( $this, 'is_plugin_exclusive' ) ) ),
			'compatible' => bf_count( array_filter( $all_plugins, array( $this, 'is_plugin_compatible' ) ) ),
		);
	}


	public function active_status() {

		if ( isset( $_REQUEST['plugin_status'] )
		     && in_array( $_REQUEST['plugin_status'], array(
				'active',
				'premium',
				'inactive',
				'exclusive',
				'compatible'
			) )
		) {

			return $_REQUEST['plugin_status'];
		}

		return 'all';
	}


	public function get_columns() {

		$columns = parent::get_columns();

		if ( ! bf_is_product_registered() ) {
			unset( $columns['cb'] );
		}

		return $columns;
	}


	protected function get_table_classes() {

		$classes = parent::get_table_classes();

		if ( ! bf_is_product_registered() ) {
			$classes[] = 'bf-product-inactive';
		}

		return $classes;
	}
}
