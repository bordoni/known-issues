/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	InspectorControls,
	useBlockProps,
} from '@wordpress/block-editor';
import {
	PanelBody,
	ToggleControl,
	TextControl,
} from '@wordpress/components';

/**
 * Block edit function.
 *
 * @param {Object}   props               Block properties.
 * @param {Object}   props.attributes    Block attributes.
 * @param {Function} props.setAttributes Function to set attributes.
 * @return {Element} Element to render.
 */
export default function Edit( { attributes, setAttributes } ) {
	const {
		showAffectedCount,
		allowSignup,
		buttonText,
		unsubscribeButtonText,
	} = attributes;

	const blockProps = useBlockProps( {
		className: 'known-issues-affected-users',
	} );

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Affected Users Settings', 'known-issues' ) }>
					<ToggleControl
						label={ __( 'Show affected user count', 'known-issues' ) }
						help={ __(
							'Display the number of users affected by this issue',
							'known-issues'
						) }
						checked={ showAffectedCount }
						onChange={ ( value ) =>
							setAttributes( { showAffectedCount: value } )
						}
					/>
					<ToggleControl
						label={ __( 'Allow users to sign up', 'known-issues' ) }
						help={ __(
							'Allow logged-in users to register as affected',
							'known-issues'
						) }
						checked={ allowSignup }
						onChange={ ( value ) =>
							setAttributes( { allowSignup: value } )
						}
					/>
					<TextControl
						label={ __( 'Sign up button text', 'known-issues' ) }
						value={ buttonText }
						onChange={ ( value ) =>
							setAttributes( { buttonText: value } )
						}
					/>
					<TextControl
						label={ __( 'Unsubscribe button text', 'known-issues' ) }
						value={ unsubscribeButtonText }
						onChange={ ( value ) =>
							setAttributes( { unsubscribeButtonText: value } )
						}
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<div className="known-issues-affected-users__content">
					{ showAffectedCount && (
						<p className="known-issues-affected-users__count">
							{ __( 'Affected users: 0', 'known-issues' ) }
						</p>
					) }
					{ allowSignup && (
						<button
							type="button"
							className="known-issues-affected-users__button"
							disabled
						>
							{ buttonText }
						</button>
					) }
					<p className="known-issues-affected-users__help">
						{ __(
							'This block displays affected user count and signup options on the frontend.',
							'known-issues'
						) }
					</p>
				</div>
			</div>
		</>
	);
}
