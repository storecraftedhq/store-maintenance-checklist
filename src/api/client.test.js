/**
 * @jest-environment jsdom
 */

jest.mock( '@wordpress/api-fetch', () => {
	const fn = jest.fn();
	fn.use = jest.fn();
	fn.createNonceMiddleware = jest.fn( () => ( {} ) );
	return fn;
} );

import apiFetch from '@wordpress/api-fetch';
import { downloadCsv } from './client';

describe( 'downloadCsv', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		window.stmcAdmin = { nonce: 'test-nonce' };
		global.URL.createObjectURL = jest.fn( () => 'blob:mock' );
		global.URL.revokeObjectURL = jest.fn();
	} );

	it( 'requests /stmc/v1/export/csv and downloads a blob', async () => {
		const blob = new Blob( [ 'a,b\n' ], { type: 'text/csv' } );
		const response = {
			ok: true,
			blob: jest.fn( async () => blob ),
		};
		apiFetch.mockResolvedValue( response );

		const click = jest.fn();
		const remove = jest.fn();
		jest.spyOn( document, 'createElement' ).mockImplementation( ( tag ) => {
			if ( tag === 'a' ) {
				return { href: '', download: '', click, remove };
			}
			return document.createElement.bind( document )( tag );
		} );
		jest.spyOn( document.body, 'appendChild' ).mockImplementation(
			() => null
		);

		await downloadCsv();

		expect( apiFetch ).toHaveBeenCalledWith(
			expect.objectContaining( {
				path: '/stmc/v1/export/csv',
				parse: false,
			} )
		);
		expect( response.blob ).toHaveBeenCalled();
		expect( click ).toHaveBeenCalled();
	} );

	it( 'throws a readable Error when the Response is not ok', async () => {
		apiFetch.mockResolvedValue( {
			ok: false,
			status: 404,
			json: async () => ( { message: 'No route was found.' } ),
		} );

		await expect( downloadCsv() ).rejects.toThrow( 'No route was found.' );
	} );

	it( 'maps apiFetch Response rejections to a readable Error', async () => {
		apiFetch.mockRejectedValue( {
			ok: false,
			status: 404,
			blob: jest.fn(),
			json: async () => ( {
				message:
					'No route was found matching the URL and request method.',
			} ),
		} );

		await expect( downloadCsv() ).rejects.toThrow(
			'No route was found matching the URL and request method.'
		);
	} );
} );
