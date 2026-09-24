<?php
/**
 * Finding DTO helpers.
 *
 * @package Store_Maintenance_Checklist
 */

declare(strict_types=1);

/**
 * Builds REST Finding-shaped arrays.
 */
final class STMC_Finding {

	/**
	 * @param STMC_Check           $check    Check.
	 * @param string               $status   open|passed|ignored.
	 * @param string               $why      Why copy.
	 * @param array<string, mixed> $evidence Evidence.
	 * @param array<string, mixed> $extra    Extra fields.
	 * @return array<string, mixed>
	 */
	public static function from_check( STMC_Check $check, string $status, string $why = '', array $evidence = array(), array $extra = array() ): array {
		$severity = $check->severity();
		if ( 'passed' === $status ) {
			$severity = 'passed';
		}

		$finding = array(
			'id'             => $check->id(),
			'title'          => $check->title(),
			'severity'       => $severity,
			'area'           => $check->area(),
			'status'         => $status,
			'why'            => $why,
			'evidence'       => array_merge(
				array(
					'summary' => '',
					'count'   => 0,
					'samples' => array(),
				),
				$evidence
			),
			'primary_action' => $extra['primary_action'] ?? array(
				'label' => '',
				'url'   => '',
			),
			'further_tools'  => $extra['further_tools'] ?? array(),
			'ignore_reason'  => null,
			'score_excluded' => ! empty( $extra['score_excluded'] ),
			'change'         => null,
		);

		if ( in_array( $check->id(), STMC_Score::UNSCORED_IDS, true ) ) {
			$finding['score_excluded'] = true;
		}

		return $finding;
	}

	/**
	 * Passed finding shortcut.
	 *
	 * @param STMC_Check $check Check.
	 * @return array<string, mixed>
	 */
	public static function passed( STMC_Check $check ): array {
		return self::from_check( $check, 'passed', '', array() );
	}
}
