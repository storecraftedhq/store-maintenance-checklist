module.exports = {
	extends: [ 'plugin:@wordpress/eslint-plugin/recommended' ],
	overrides: [
		{
			files: [
				'**/__tests__/**/*.[jt]s?(x)',
				'**/?(*.)+(spec|test).[jt]s?(x)',
			],
			extends: [ 'plugin:@wordpress/eslint-plugin/test-unit' ],
			rules: {
				'jsdoc/check-tag-names': 'off',
			},
		},
	],
};
