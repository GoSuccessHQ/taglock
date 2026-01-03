<?php

declare(strict_types=1);

namespace GoSuccess\TagLock\Repository;

use GoSuccess\TagLock\Enum\CrmProvider;
use GoSuccess\TagLock\Service\LoggerService;
use GoSuccess\TagLock\Util\ArrayUtil;

use function count;
use function current_time;
use function intval;
use function is_array;
use function is_numeric;
use function is_string;
use function sanitize_text_field;
use function strtolower;
use function time;
use function trim;
use function wp_cache_get;
use function wp_cache_set;
use function wp_json_encode;

/**
 * Persists and queries TagLock rules.
 */
final class RuleRepository {
	private const string CACHE_GROUP = 'taglock';
	private const int CACHE_TTL_SECONDS = 60;

	public function __construct(
		private readonly LoggerService $logger
	) {}

	private function getRulesCacheVersion(): int {
		$version = wp_cache_get( 'rules_cache_version', self::CACHE_GROUP );
		$version = is_numeric( $version ) ? (int) $version : 0;
		if ( $version < 1 ) {
			$version = 1;
			wp_cache_set( 'rules_cache_version', $version, self::CACHE_GROUP, self::CACHE_TTL_SECONDS );
		}
		return $version;
	}

	private function bumpRulesCacheVersion(): void {
		$version = $this->getRulesCacheVersion();
		$version++;
		wp_cache_set( 'rules_cache_version', $version, self::CACHE_GROUP, self::CACHE_TTL_SECONDS );
	}

	private function getTagTableName( string $tableName ): string {
		global $wpdb;

		return match ( $tableName ) {
			'taglock_rule_required_tag' => $wpdb->prefix . 'taglock_rule_required_tag',
			'taglock_rule_engagement_tag' => $wpdb->prefix . 'taglock_rule_engagement_tag',
			default => $wpdb->prefix . 'taglock_rule_required_tag',
		};
	}

	/**
	 * @return array{items: array<int, array<string, mixed>>, total: int}
	 */
	public function listRules( string $search, int $page, int $perPage ): array {
		global $wpdb;

		$page = $page < 1 ? 1 : $page;
		$perPage = $perPage < 1 ? 20 : $perPage;
		$perPage = $perPage > 100 ? 100 : $perPage;
		$offset = ( $page - 1 ) * $perPage;

		$search = trim( $search );
		$table = $wpdb->prefix . 'taglock_rule';

		$cacheVersion = $this->getRulesCacheVersion();
		$cacheKey = 'rules_list_' . md5( wp_json_encode( [
			'v' => $cacheVersion,
			'search' => $search,
			'page' => $page,
			'per_page' => $perPage,
		] ) ?: (string) time() );

		$cached = wp_cache_get( $cacheKey, self::CACHE_GROUP );
		if ( is_array( $cached ) && isset( $cached['items'], $cached['total'] ) ) {
			return $cached;
		}

		if ( $search !== '' ) {
			$like = '%' . $wpdb->esc_like( $search ) . '%';
			$total = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare( 'SELECT COUNT(*) FROM %i WHERE name LIKE %s', $table, $like )
			);
			$rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					'SELECT id, name, provider, is_active, access_mode, deny_mode, redirect_post_id, admin_bypass_enabled, engagement_tagging_enabled, updated_at
					FROM %i
					WHERE name LIKE %s
					ORDER BY updated_at DESC
					LIMIT %d OFFSET %d',
					$table,
					$like,
					$perPage,
					$offset
				),
				ARRAY_A
			);
		} else {
			$total = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %i', $table ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					'SELECT id, name, provider, is_active, access_mode, deny_mode, redirect_post_id, admin_bypass_enabled, engagement_tagging_enabled, updated_at
					FROM %i
					ORDER BY updated_at DESC
					LIMIT %d OFFSET %d',
					$table,
					$perPage,
					$offset
				),
				ARRAY_A
			);
		}

		$items = is_array( $rows ) ? $rows : [];

		$items = array_map( static function ( array $row ): array {
			$row['id'] = (int) $row['id'];
			$row['is_active'] = (bool) $row['is_active'];
			$row['provider'] = isset( $row['provider'] ) && is_string( $row['provider'] ) && $row['provider'] !== ''
				? $row['provider']
				: CrmProvider::default()->value;
			$row['redirect_post_id'] = $row['redirect_post_id'] !== null ? (int) $row['redirect_post_id'] : null;
			$row['admin_bypass_enabled'] = (bool) $row['admin_bypass_enabled'];
			$row['engagement_tagging_enabled'] = (bool) $row['engagement_tagging_enabled'];
			return $row;
		}, $items );

		$result = [
			'items' => $items,
			'total' => $total,
		];

		wp_cache_set( $cacheKey, $result, self::CACHE_GROUP, self::CACHE_TTL_SECONDS );
		return $result;
	}

	/**
	 * @return array<string, mixed>|null
	 */
	public function getRule( int $id ): ?array {
		global $wpdb;

		$cacheVersion = $this->getRulesCacheVersion();
		$cacheKey = 'rule_' . $id . '_' . $cacheVersion;
		$cached = wp_cache_get( $cacheKey, self::CACHE_GROUP );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$table = $wpdb->prefix . 'taglock_rule';
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM %i WHERE id = %d', $table, $id ), ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( ! is_array( $row ) ) {
			return null;
		}

		$rule = $this->normalizeRuleRow( $row );
		$rule['required_tag_ids'] = $this->getTagIds( $id, 'taglock_rule_required_tag' );
		$rule['engagement_tag_ids'] = $this->getTagIds( $id, 'taglock_rule_engagement_tag' );

		wp_cache_set( $cacheKey, $rule, self::CACHE_GROUP, self::CACHE_TTL_SECONDS );

		return $rule;
	}

	public function createRule( array $data ): int {
		global $wpdb;

		$table = $wpdb->prefix . 'taglock_rule';
		$now = current_time( 'mysql' );

		$insert = $this->sanitizeRuleWrite( $data, true );
		$insert['created_at'] = $now;
		$insert['updated_at'] = $now;

		$ok = (bool) $wpdb->insert( $table, $insert ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		if ( ! $ok ) {
			$this->logger->error( __( 'Failed to create rule', 'taglock' ), [ 'error' => $wpdb->last_error ] );
			return 0;
		}

		$ruleId = (int) $wpdb->insert_id;

		$this->syncTagIds( $ruleId, $data['required_tag_ids'] ?? [], 'taglock_rule_required_tag' );
		$this->syncTagIds( $ruleId, $data['engagement_tag_ids'] ?? [], 'taglock_rule_engagement_tag' );
		$this->bumpRulesCacheVersion();

		return $ruleId;
	}

	public function updateRule( int $id, array $data ): bool {
		global $wpdb;

		$table = $wpdb->prefix . 'taglock_rule';
		$update = $this->sanitizeRuleWrite( $data, false );
		$update['updated_at'] = current_time( 'mysql' );

		$ok = $wpdb->update( $table, $update, [ 'id' => $id ] ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( $ok === false ) {
			$this->logger->error( __( 'Failed to update rule', 'taglock' ), [ 'id' => $id, 'error' => $wpdb->last_error ] );
			return false;
		}

		$this->syncTagIds( $id, $data['required_tag_ids'] ?? [], 'taglock_rule_required_tag' );
		$this->syncTagIds( $id, $data['engagement_tag_ids'] ?? [], 'taglock_rule_engagement_tag' );
		$this->bumpRulesCacheVersion();

		return true;
	}

	public function deleteRule( int $id ): bool {
		global $wpdb;

		$this->deleteTags( $id, 'taglock_rule_required_tag' );
		$this->deleteTags( $id, 'taglock_rule_engagement_tag' );

		$table = $wpdb->prefix . 'taglock_rule';
		$ok = $wpdb->delete( $table, [ 'id' => $id ] ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$this->bumpRulesCacheVersion();
		return $ok !== false;
	}

	private function normalizeRuleRow( array $row ): array {
		$row['id'] = (int) $row['id'];
		$row['is_active'] = (bool) $row['is_active'];
		$row['redirect_post_id'] = $row['redirect_post_id'] !== null ? (int) $row['redirect_post_id'] : null;
		$row['admin_bypass_enabled'] = (bool) $row['admin_bypass_enabled'];
		$row['engagement_tagging_enabled'] = (bool) $row['engagement_tagging_enabled'];
		$row['access_mode'] = is_string( $row['access_mode'] ) ? $row['access_mode'] : 'tag_any';
		$row['deny_mode'] = is_string( $row['deny_mode'] ) ? $row['deny_mode'] : 'message';
		$row['name'] = is_string( $row['name'] ) ? $row['name'] : '';
		$row['deny_message'] = isset( $row['deny_message'] ) && is_string( $row['deny_message'] ) ? $row['deny_message'] : '';
		$row['teaser_html'] = isset( $row['teaser_html'] ) && is_string( $row['teaser_html'] ) ? $row['teaser_html'] : '';
		$row['redirect_post_type'] = isset( $row['redirect_post_type'] ) && is_string( $row['redirect_post_type'] ) ? $row['redirect_post_type'] : null;
		$row['provider'] = isset( $row['provider'] ) && is_string( $row['provider'] ) && $row['provider'] !== ''
			? $row['provider']
			: CrmProvider::default()->value;

		return $row;
	}

	/**
	 * @return array<int, int>
	 */
	private function getTagIds( int $ruleId, string $tableName ): array {
		global $wpdb;

		$cacheVersion = $this->getRulesCacheVersion();
		$cacheKey = 'rule_tag_ids_' . md5( $tableName . '_' . $ruleId . '_' . $cacheVersion );
		$cached = wp_cache_get( $cacheKey, self::CACHE_GROUP );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$table = $this->getTagTableName( $tableName );
		$rows = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare( 'SELECT tag_id FROM %i WHERE rule_id = %d ORDER BY tag_id ASC', $table, $ruleId )
		);
		if ( ! is_array( $rows ) ) {
			return [];
		}

		$ids = ArrayUtil::normalizePositiveIntegers( $rows );
		wp_cache_set( $cacheKey, $ids, self::CACHE_GROUP, self::CACHE_TTL_SECONDS );
		return $ids;
	}

	private function deleteTags( int $ruleId, string $tableName ): void {
		global $wpdb;
		$table = $this->getTagTableName( $tableName );
		$wpdb->delete( $table, [ 'rule_id' => $ruleId ] ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	/**
	 * @param array<int, mixed> $tagIds
	 */
	private function syncTagIds( int $ruleId, array $tagIds, string $tableName ): void {
		global $wpdb;

		$this->deleteTags( $ruleId, $tableName );

		$table = $this->getTagTableName( $tableName );
		$tagIds = ArrayUtil::normalizePositiveIntegers( $tagIds );

		foreach ( $tagIds as $tagId ) {
			$wpdb->insert( $table, [ // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				'rule_id' => $ruleId,
				'tag_id'  => $tagId,
			] );
		}
	}

	/**
	 * @return array<string, mixed>
	 */
	private function sanitizeRuleWrite( array $data, bool $includeProviderDefault ): array {
		$provider = null;
		if ( isset( $data['provider'] ) && is_string( $data['provider'] ) ) {
			$providerKey = sanitize_text_field( $data['provider'] );
			$providerKey = strtolower( $providerKey );
			$providerEnum = CrmProvider::tryFrom( $providerKey );
			$provider = $providerEnum?->value;
		}

		if ( $provider === null && $includeProviderDefault ) {
			$provider = CrmProvider::default()->value;
		}

		$name = isset( $data['name'] ) && is_string( $data['name'] ) ? sanitize_text_field( $data['name'] ) : '';
		$accessMode = isset( $data['access_mode'] ) && is_string( $data['access_mode'] ) ? sanitize_text_field( $data['access_mode'] ) : 'tag_any';
		$denyMode = isset( $data['deny_mode'] ) && is_string( $data['deny_mode'] ) ? sanitize_text_field( $data['deny_mode'] ) : 'message';
		$denyMessage = isset( $data['deny_message'] ) && is_string( $data['deny_message'] ) ? $data['deny_message'] : '';
		$teaserHtml = isset( $data['teaser_html'] ) && is_string( $data['teaser_html'] ) ? $data['teaser_html'] : '';
		$redirectPostId = isset( $data['redirect_post_id'] ) ? intval( $data['redirect_post_id'] ) : 0;
		$redirectPostId = $redirectPostId > 0 ? $redirectPostId : null;
		$redirectPostType = isset( $data['redirect_post_type'] ) && is_string( $data['redirect_post_type'] ) ? sanitize_text_field( $data['redirect_post_type'] ) : null;
		$redirectPostType = $redirectPostId === null ? null : $redirectPostType;

		$isActive = ! empty( $data['is_active'] ) ? 1 : 0;
		$adminBypassEnabled = ! empty( $data['admin_bypass_enabled'] ) ? 1 : 0;
		$engagementEnabled = ! empty( $data['engagement_tagging_enabled'] ) ? 1 : 0;

		$payload = [
			'name'                     => $name,
			'provider'                 => $provider,
			'is_active'                => $isActive,
			'access_mode'              => $accessMode,
			'deny_mode'                => $denyMode,
			'deny_message'             => $denyMessage,
			'teaser_html'              => $teaserHtml,
			'redirect_post_id'         => $redirectPostId,
			'redirect_post_type'       => $redirectPostType,
			'admin_bypass_enabled'      => $adminBypassEnabled,
			'engagement_tagging_enabled' => $engagementEnabled,
		];

		if ( $payload['provider'] === null ) {
			unset( $payload['provider'] );
		}

		return $payload;
	}
}
