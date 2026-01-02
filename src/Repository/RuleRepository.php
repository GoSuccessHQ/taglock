<?php

declare(strict_types=1);

namespace GoSuccess\TagLock\Repository;

use GoSuccess\TagLock\Service\LoggerService;

use function array_filter;
use function array_map;
use function count;
use function current_time;
use function intval;
use function is_array;
use function is_string;
use function sanitize_text_field;

/**
 * Persists and queries TagLock rules.
 */
final class RuleRepository {

	public function __construct(
		private readonly LoggerService $logger
	) {}

	/**
	 * @return array{items: array<int, array<string, mixed>>, total: int}
	 */
	public function listRules( string $search, int $page, int $perPage ): array {
		global $wpdb;

		$page = $page < 1 ? 1 : $page;
		$perPage = $perPage < 1 ? 20 : $perPage;
		$perPage = $perPage > 100 ? 100 : $perPage;
		$offset = ( $page - 1 ) * $perPage;

		$table = $wpdb->prefix . 'taglock_rule';
		$whereSql = '1=1';
		$whereArgs = [];

		$search = trim( $search );
		if ( $search !== '' ) {
			$like = '%' . $wpdb->esc_like( $search ) . '%';
			$whereSql .= ' AND name LIKE %s';
			$whereArgs[] = $like;
		}

		$countSql = "SELECT COUNT(*) FROM {$table} WHERE {$whereSql}";
		$total = (int) $wpdb->get_var( $wpdb->prepare( $countSql, ...$whereArgs ) );

		$listSql = "SELECT id, name, is_active, access_mode, deny_mode, redirect_post_id, admin_bypass_enabled, engagement_tagging_enabled, updated_at
			FROM {$table}
			WHERE {$whereSql}
			ORDER BY updated_at DESC
			LIMIT %d OFFSET %d";

		$listArgs = [ ...$whereArgs, $perPage, $offset ];
		$rows = $wpdb->get_results( $wpdb->prepare( $listSql, ...$listArgs ), ARRAY_A );
		$items = is_array( $rows ) ? $rows : [];

		$items = array_map( static function ( array $row ): array {
			$row['id'] = (int) $row['id'];
			$row['is_active'] = (bool) $row['is_active'];
			$row['redirect_post_id'] = $row['redirect_post_id'] !== null ? (int) $row['redirect_post_id'] : null;
			$row['admin_bypass_enabled'] = (bool) $row['admin_bypass_enabled'];
			$row['engagement_tagging_enabled'] = (bool) $row['engagement_tagging_enabled'];
			return $row;
		}, $items );

		return [
			'items' => $items,
			'total' => $total,
		];
	}

	/**
	 * @return array<string, mixed>|null
	 */
	public function getRule( int $id ): ?array {
		global $wpdb;

		$table = $wpdb->prefix . 'taglock_rule';
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ), ARRAY_A );
		if ( ! is_array( $row ) ) {
			return null;
		}

		$rule = $this->normalizeRuleRow( $row );
		$rule['required_tag_ids'] = $this->getTagIds( $id, 'taglock_rule_required_tag' );
		$rule['engagement_tag_ids'] = $this->getTagIds( $id, 'taglock_rule_engagement_tag' );

		return $rule;
	}

	public function createRule( array $data ): int {
		global $wpdb;

		$table = $wpdb->prefix . 'taglock_rule';
		$now = current_time( 'mysql' );

		$insert = $this->sanitizeRuleWrite( $data );
		$insert['created_at'] = $now;
		$insert['updated_at'] = $now;

		$ok = (bool) $wpdb->insert( $table, $insert );
		if ( ! $ok ) {
			$this->logger->error( __( 'Failed to create rule', 'taglock' ), [ 'error' => $wpdb->last_error ] );
			return 0;
		}

		$ruleId = (int) $wpdb->insert_id;

		$this->syncTagIds( $ruleId, $data['required_tag_ids'] ?? [], 'taglock_rule_required_tag' );
		$this->syncTagIds( $ruleId, $data['engagement_tag_ids'] ?? [], 'taglock_rule_engagement_tag' );

		return $ruleId;
	}

	public function updateRule( int $id, array $data ): bool {
		global $wpdb;

		$table = $wpdb->prefix . 'taglock_rule';
		$update = $this->sanitizeRuleWrite( $data );
		$update['updated_at'] = current_time( 'mysql' );

		$ok = $wpdb->update( $table, $update, [ 'id' => $id ] );
		if ( $ok === false ) {
			$this->logger->error( __( 'Failed to update rule', 'taglock' ), [ 'id' => $id, 'error' => $wpdb->last_error ] );
			return false;
		}

		$this->syncTagIds( $id, $data['required_tag_ids'] ?? [], 'taglock_rule_required_tag' );
		$this->syncTagIds( $id, $data['engagement_tag_ids'] ?? [], 'taglock_rule_engagement_tag' );

		return true;
	}

	public function deleteRule( int $id ): bool {
		global $wpdb;

		$this->deleteTags( $id, 'taglock_rule_required_tag' );
		$this->deleteTags( $id, 'taglock_rule_engagement_tag' );

		$table = $wpdb->prefix . 'taglock_rule';
		$ok = $wpdb->delete( $table, [ 'id' => $id ] );
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

		return $row;
	}

	/**
	 * @return array<int, int>
	 */
	private function getTagIds( int $ruleId, string $suffix ): array {
		global $wpdb;

		$table = $wpdb->prefix . $suffix;
		$rows = $wpdb->get_col( $wpdb->prepare( "SELECT tag_id FROM {$table} WHERE rule_id = %d ORDER BY tag_id ASC", $ruleId ) );
		if ( ! is_array( $rows ) ) {
			return [];
		}

		$ids = array_map( 'intval', $rows );
		$ids = array_values( array_filter( $ids, static fn( int $v ) => $v > 0 ) );
		return $ids;
	}

	private function deleteTags( int $ruleId, string $suffix ): void {
		global $wpdb;
		$table = $wpdb->prefix . $suffix;
		$wpdb->delete( $table, [ 'rule_id' => $ruleId ] );
	}

	/**
	 * @param array<int, mixed> $tagIds
	 */
	private function syncTagIds( int $ruleId, array $tagIds, string $suffix ): void {
		global $wpdb;

		$this->deleteTags( $ruleId, $suffix );

		$table = $wpdb->prefix . $suffix;
		$tagIds = array_map( 'intval', $tagIds );
		$tagIds = array_values( array_filter( $tagIds, static fn( int $v ) => $v > 0 ) );

		foreach ( $tagIds as $tagId ) {
			$wpdb->insert( $table, [
				'rule_id' => $ruleId,
				'tag_id'  => $tagId,
			] );
		}
	}

	/**
	 * @return array<string, mixed>
	 */
	private function sanitizeRuleWrite( array $data ): array {
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

		return [
			'name'                     => $name,
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
	}
}
