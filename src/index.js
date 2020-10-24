import { addFilter } from '@wordpress/hooks'
import { useSelect, dispatch } from '@wordpress/data'
import { PanelRow, TextControl, ToggleControl } from '@wordpress/components'
import { __ } from '@wordpress/i18n'

/**
 * Adding OPayO Content
 */
addFilter('wp_travel_payment_gateway_fields_opayo', 'wp-travel', (content) => {
	return [...content, <OPayOContent />]
});

const OPayOContent = () => {

	/**
	 * Get Store Settings.
	 */
	const { updateSettings } = dispatch('WPTravel/Admin');

	const allData = useSelect((select) => {
		return select('WPTravel/Admin').getAllStore()
	}, []);

	const {
		payment_option_opayo,
		opayo_vendor,
		opayo_encryption_key,
		wt_test_mode
	} = allData

	return (
		<>
			{
				wt_test_mode === 'yes' ?
					<strong><p className="howTo" style={{ color: '#ffffff', textAlign: 'center', backgroundColor: 'darkseagreen' }} >{__('Test Mode Active', 'wp-travel-opayo')}</p></strong>
					: <strong><p className="howTo" style={{ color: '#ffffff', textAlign: 'center', backgroundColor: 'darkseagreen' }} >{__('Live Mode Active', 'wp-travel-opayo')}</p></strong>
			}
			<PanelRow>
				<label>{__('Enable OPayO', 'wp-travel-opayo')}</label>
				<div className="wp-travel-field-value">
					<ToggleControl
						checked={payment_option_opayo == 'yes'}
						onChange={() => {
							updateSettings({
								...allData,
								payment_option_opayo: 'yes' == payment_option_opayo ? 'no' : 'yes'
							})
						}}
					/>
					<p className="description">{__('Check to enable OPayO Checkout', 'wp-travel-opayo')}</p>
				</div>
			</PanelRow>

			{
				payment_option_opayo === 'yes' &&
				<>
					<PanelRow>
						<label>{__('Vendor', 'wp-travel-opayo')}</label>
						<div className="wp-travel-field-value">
							<TextControl
								value={opayo_vendor}
								onChange={
									(value) => {
										updateSettings({
											...allData,
											opayo_vendor: value
										})
									}
								}
							/>
						</div>
					</PanelRow>

					<PanelRow>
						<label>{__('Encryption Key', 'wp-travel-opayo')}</label>
						<div className="wp-travel-field-value">
							<TextControl
								value={opayo_encryption_key}
								onChange={
									(value) => {
										updateSettings({
											...allData,
											opayo_encryption_key: value
										})
									}
								}
							/>
						</div>
					</PanelRow>
				</>
			}
		</>
	)
}