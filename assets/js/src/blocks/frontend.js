/**
 * External dependencies
 */
import { registerPaymentMethod } from '@woocommerce/blocks-registry';
import { getSetting } from '@woocommerce/settings';
import { Content, ariaLabel, Label } from './base';

const settings = getSetting( 'korapay_data', {} );
const label = ariaLabel({ title: settings.title });

/**
 * Korapay payment method config object.
 */
const Korapay_Gateway = {
	name: 'korapay',
	label: <Label logoUrls={ settings.logo_urls } title={ label } />,
	content: <Content description={ settings.description } />,
	edit: <Content description={ settings.description } />,
	canMakePayment: () => true,
	ariaLabel: label,
	supports: {
		showSavedCards: settings.allow_saved_cards,
		showSaveOption: settings.allow_saved_cards,
		features: settings.supports,
	},
};

registerPaymentMethod( Korapay_Gateway );