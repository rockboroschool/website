<?php
namespace AIOSEO\Plugin\Common\Utils;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DynamicBackup {
	/**
	 * A the name of the option to save dynamic backups to.
	 *
	 * @since 4.1.3
	 *
	 * @var string
	 */
	protected $optionsName = 'aioseo_dynamic_settings_backup';

	/**
	 * The dynamic backup.
	 *
	 * @since 4.1.3
	 *
	 * @var array
	 */
	protected $backup = [];

	/**
	 * Whether the backup data needs to updated after it has been restored.
	 *
	 * @since 4.1.3
	 *
	 * @var boolean
	 */
	protected $needsUpdate = false;

	/**
	 * Whether the backup should be updated.
	 *
	 * @since 4.1.3
	 *
	 * @var boolean
	 */
	protected $shouldBackup = false;

	/**
	 * The options from the DB.
	 *
	 * @since 4.1.3
	 *
	 * @var array
	 */
	protected $options = [];

	/**
	 * Class constructor.
	 *
	 * @since 4.1.3
	 */
	public function __construct() {
		add_action( 'wp_loaded', [ $this, 'init' ], 99999 );
		add_action( 'shutdown', [ $this, 'updateBackup' ] );
	}

	/**
	 * Updates the backup after restoring options.
	 *
	 * @since 4.1.3
	 *
	 * @return void
	 */
	public function updateBackup() {
		if ( $this->needsUpdate ) {
			$this->needsUpdate = false;
			update_option( $this->optionsName, wp_json_encode( $this->backup ) );
		}
	}

	/**
	 * Checks whether data from the backup has to be restored.
	 *
	 * @since 4.1.3
	 *
	 * @return void
	 */
	public function init() {
		$backup = json_decode( get_option( $this->optionsName ), true );
		if ( empty( $backup ) ) {
			update_option( $this->optionsName, '{}' );
			return;
		}

		$this->backup  = $backup;
		$this->options = aioseo()->options->getDefaults();

		$this->restorePostTypes();
		$this->restoreTaxonomies();
		$this->restoreArchives();
	}

	/**
	 * Restores the dynamic Post Types options.
	 *
	 * @since 4.1.3
	 *
	 * @return void
	 */
	private function restorePostTypes() {
		$postTypes = aioseo()->helpers->getPublicPostTypes();
		foreach ( $postTypes as $postType ) {
			$name = $postType['name'];
			if ( 'type' === $name ) {
				$name = '_aioseo_type';
			}

			if ( ! empty( $this->backup['postTypes'][ $name ]['searchAppearance'] ) ) {
				$this->restoreOptions( $this->backup['postTypes'][ $name ]['searchAppearance'], [ 'searchAppearance', 'dynamic', 'postTypes', $name ] );
				unset( $this->backup['postTypes'][ $name ]['searchAppearance'] );
				$this->needsUpdate = true;
			}

			if ( ! empty( $this->backup['postTypes'][ $name ]['social']['facebook'] ) ) {
				$this->restoreOptions( $this->backup['postTypes'][ $name ]['social']['facebook'], [ 'social', 'facebook', 'general', 'dynamic', 'postTypes', $name ] );
				unset( $this->backup['postTypes'][ $name ]['social']['facebook'] );
				$this->needsUpdate = true;
			}
		}
	}

	/**
	 * Restores the dynamic Taxonomies options.
	 *
	 * @since 4.1.3
	 *
	 * @return void
	 */
	private function restoreTaxonomies() {
		$taxonomies = aioseo()->helpers->getPublicTaxonomies();
		foreach ( $taxonomies as $taxonomy ) {
			$name = $taxonomy['name'];
			if ( 'type' === $name ) {
				$name = '_aioseo_type';
			}

			if ( ! empty( $this->backup['taxonomies'][ $name ]['searchAppearance'] ) ) {
				$this->restoreOptions( $this->backup['taxonomies'][ $name ]['searchAppearance'], [ 'searchAppearance', 'dynamic', 'taxonomies', $name ] );
				unset( $this->backup['taxonomies'][ $name ]['searchAppearance'] );
				$this->needsUpdate = true;
			}

			if ( ! empty( $this->backup['taxonomies'][ $name ]['social']['facebook'] ) ) {
				$this->restoreOptions( $this->backup['taxonomies'][ $name ]['social']['facebook'], [ 'social', 'facebook', 'general', 'dynamic', 'taxonomies', $name ] );
				unset( $this->backup['taxonomies'][ $name ]['social']['facebook'] );
				$this->needsUpdate = true;
			}
		}
	}

	/**
	 * Restores the dynamic Archives options.
	 *
	 * @since 4.1.3
	 *
	 * @return void
	 */
	private function restoreArchives() {
		$postTypes = aioseo()->helpers->getPublicPostTypes();
		foreach ( $postTypes as $postType ) {
			$name = $postType['name'];
			if ( 'type' === $name ) {
				$name = '_aioseo_type';
			}

			if ( ! empty( $this->backup['archives'][ $name ]['searchAppearance'] ) ) {
				$this->restoreOptions( $this->backup['archives'][ $name ]['searchAppearance'], [ 'searchAppearance', 'dynamic', 'archives', $name ] );
				unset( $this->backup['archives'][ $name ]['searchAppearance'] );
				$this->needsUpdate = true;
			}
		}
	}

	/**
	 * Restores the backuped options.
	 *
	 * @since 4.1.3
	 *
	 * @return void
	 * @param  array $backupOptions The options to be restored.
	 * @param  array $groups        The group that the option should be restored.
	 */
	protected function restoreOptions( $backupOptions, $groups ) {
		$groupPath = $this->options;
		foreach ( $groups as $group ) {
			if ( ! isset( $groupPath[ $group ] ) ) {
				return false;
			}
			$groupPath = $groupPath[ $group ];
		}

		$options = aioseo()->options->noConflict();
		foreach ( $backupOptions as $setting => $value ) {
			// Check if the option exists by checking if the type is defined.
			$type = ! empty( $groupPath[ $setting ]['type'] ) ? $groupPath[ $setting ]['type'] : '';
			if ( ! $type ) {
				continue;
			}

			foreach ( $groups as $group ) {
				$options = $options->$group;
			}

			$options->$setting = $value['value']['value'];
		}
		aioseo()->options->refresh();
	}

	/**
	 * Maybe backup the options if it has disappeared.
	 *
	 * @since 4.1.3
	 *
	 * @param  array $newOptions An array of options to check.
	 * @return void
	 */
	public function maybeBackup( $newOptions ) {
		$this->maybeBackupPostType( $newOptions['searchAppearance']['dynamic']['postTypes'], $newOptions['social']['facebook']['general']['dynamic']['postTypes'] );
		$this->maybeBackupTaxonomy( $newOptions['searchAppearance']['dynamic']['taxonomies'], $newOptions['social']['facebook']['general']['dynamic']['taxonomies'] );
		$this->maybeBackupArchives( $newOptions['searchAppearance']['dynamic']['archives'] );

		if ( $this->shouldBackup ) {
			$this->shouldBackup = false;
			update_option( $this->optionsName, wp_json_encode( $this->backup ) );
		}
	}

	/**
	 * Maybe backup the Post Types.
	 *
	 * @since 4.1.3
	 *
	 * @param  array $dynamicPostTypes  An array of dynamic post types from Search Appearance to check.
	 * @param  array $dynamicPostTypeOG An array of dynamic post types from Social Facebook to check.
	 * @return void
	 */
	private function maybeBackupPostType( $dynamicPostTypes, $dynamicPostTypesOG ) {
		$postTypes = aioseo()->helpers->getPublicPostTypes();
		$postTypes = aioseo()->helpers->normalizePostTypeTaxonomyNames( $postTypes );

		foreach ( $dynamicPostTypes as $dynamicPostTypeName => $dynamicPostTypeSettings ) {
			$found = wp_list_filter( $postTypes, [ 'name' => $dynamicPostTypeName ] );
			if ( count( $found ) === 0 ) {
				$this->backup['postTypes'][ $dynamicPostTypeName ]['searchAppearance'] = $dynamicPostTypeSettings;
				$this->shouldBackup = true;
			}
		}

		foreach ( $dynamicPostTypesOG as $dynamicPostTypeNameOG => $dynamicPostTypeSettingsOG ) {
			$found = wp_list_filter( $postTypes, [ 'name' => $dynamicPostTypeNameOG ] );
			if ( count( $found ) === 0 ) {
				$this->backup['postTypes'][ $dynamicPostTypeNameOG ]['social']['facebook'] = $dynamicPostTypeSettingsOG;
				$this->shouldBackup = true;
			}
		}
	}

	/**
	 * Maybe backup the Taxonomies.
	 *
	 * @since 4.1.3
	 *
	 * @param  array $dynamicTaxonomies   An array of dynamic taxonomy from Search Appearance to check.
	 * @param  array $dynamicTaxonomiesOG An array of dynamic taxonomy from Social Facebook to check.
	 * @return void
	 */
	private function maybeBackupTaxonomy( $dynamicTaxonomies, $dynamicTaxonomiesOG ) {
		$taxonomies = aioseo()->helpers->getPublicTaxonomies();
		$taxonomies = aioseo()->helpers->normalizePostTypeTaxonomyNames( $taxonomies );

		foreach ( $dynamicTaxonomies as $dynamicTaxonomyName => $dynamicTaxonomySettings ) {
			$found = wp_list_filter( $taxonomies, [ 'name' => $dynamicTaxonomyName ] );
			if ( count( $found ) === 0 ) {
				$this->backup['taxonomies'][ $dynamicTaxonomyName ]['searchAppearance'] = $dynamicTaxonomySettings;
				$this->shouldBackup = true;
			}
		}

		foreach ( $dynamicTaxonomiesOG as $dynamicTaxonomyNameOG => $dynamicTaxonomySettingsOG ) {
			$found = wp_list_filter( $taxonomies, [ 'name' => $dynamicTaxonomyNameOG ] );
			if ( count( $found ) === 0 ) {
				$this->backup['taxonomies'][ $dynamicTaxonomyNameOG ]['social']['facebook'] = $dynamicTaxonomySettingsOG;
				$this->shouldBackup = true;
			}
		}
	}

	/**
	 * Maybe backup the Archives.
	 *
	 * @since 4.1.3
	 *
	 * @param  array $dynamicArchives An array of dynamic archives to check.
	 * @return void
	 */
	private function maybeBackupArchives( $dynamicArchives ) {
		$postTypes = aioseo()->helpers->getPublicPostTypes( false, true );
		$postTypes = aioseo()->helpers->normalizePostTypeTaxonomyNames( $postTypes );

		foreach ( $dynamicArchives as $archiveName => $archiveSettings ) {
			$found = wp_list_filter( $postTypes, [ 'name' => $archiveName ] );
			if ( count( $found ) === 0 ) {
				$this->backup['archives'][ $archiveName ]['searchAppearance'] = $archiveSettings;
				$this->shouldBackup = true;
			}
		}
	}
}