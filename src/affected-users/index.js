/**
 * WordPress dependencies
 */
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import Edit from './edit';
import metadata from './block.json';
import './style.scss';
import './editor.scss';

/**
 * Register the Affected Users block.
 */
registerBlockType( metadata.name, {
	...metadata,
	edit: Edit,
} );
