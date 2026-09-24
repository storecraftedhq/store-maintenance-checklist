/**
 * @jest-environment jsdom
 */

import { formatEvidenceText, normalizeEvidenceSample } from './evidence';

describe( 'formatEvidenceText', () => {
	it( 'does not duplicate count already present in the summary', () => {
		expect(
			formatEvidenceText( {
				count: 7,
				summary: '7 on-hold orders older than 7 days.',
			} )
		).toEqual( {
			lead: '7',
			summary: 'on-hold orders older than 7 days.',
		} );
	} );

	it( 'keeps count lead when summary has no leading number', () => {
		expect(
			formatEvidenceText( {
				count: 7,
				summary: 'on-hold orders older than 7 days.',
			} )
		).toEqual( {
			lead: '7',
			summary: 'on-hold orders older than 7 days.',
		} );
	} );
} );

describe( 'normalizeEvidenceSample', () => {
	it( 'turns bare order ids into #labels', () => {
		expect( normalizeEvidenceSample( 101 ) ).toEqual( {
			label: '#101',
			url: '',
		} );
	} );

	it( 'drops empty samples that would render blank boxes', () => {
		expect( normalizeEvidenceSample( {} ) ).toBeNull();
		expect( normalizeEvidenceSample( { label: '' } ) ).toBeNull();
	} );

	it( 'keeps label/url samples', () => {
		expect(
			normalizeEvidenceSample( {
				label: '#101',
				url: 'https://example.test/wp-admin/admin.php?page=wc-orders&action=edit&id=101',
			} )
		).toEqual( {
			label: '#101',
			url: 'https://example.test/wp-admin/admin.php?page=wc-orders&action=edit&id=101',
		} );
	} );
} );
